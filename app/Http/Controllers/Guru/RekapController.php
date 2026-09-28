<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RekapController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $jadwals = JadwalPelajaran::where('guru_id', $user->id)
            ->with(['kelas', 'mataPelajaran'])
            ->get();

        $kelasList = $jadwals->pluck('kelas')->filter()->unique('id')->sortBy('nama')->values();
        $mapelList = $jadwals->pluck('mataPelajaran')->filter()->unique('id')->sortBy('nama')->values();

        $selectedKelasId = (int) $request->get('kelas_id', $kelasList->first()?->id ?? 0);
        $selectedMapelId = (int) $request->get('mata_pelajaran_id', $mapelList->first()?->id ?? 0);
        $periodeType = $request->get('periode_type', 'bulanan');
        $bulan = $request->get('bulan', Carbon::today()->format('Y-m'));
        $tahunAjaran = $request->get('tahun_ajaran', Setting::getTahunAjaranAktif());
        $semester = $request->get('semester', Setting::getSemesterAktif());

        $rekapData = null;
        if ($selectedKelasId && $selectedMapelId) {
            $rekapData = $this->getRekapData(
                $user->id,
                $selectedKelasId,
                $selectedMapelId,
                $periodeType,
                $bulan,
                $tahunAjaran,
                $semester
            );
        }

        $daftarTahunAjaran = Setting::getDaftarTahunAjaran();
        $daftarSemester = Setting::getDaftarSemester();

        return view('guru.rekap.index', compact(
            'kelasList',
            'mapelList',
            'selectedKelasId',
            'selectedMapelId',
            'periodeType',
            'bulan',
            'tahunAjaran',
            'semester',
            'rekapData',
            'daftarTahunAjaran',
            'daftarSemester'
        ));
    }

    public function exportPdf(Request $request): Response|RedirectResponse
    {
        $user = auth()->user();
        $kelasId = (int) $request->get('kelas_id');
        $mapelId = (int) $request->get('mata_pelajaran_id');

        if (! $kelasId || ! $mapelId) {
            return redirect()->route('guru.rekap.index')->with('error', 'Silakan pilih kelas dan mata pelajaran terlebih dahulu.');
        }

        $periodeType = $request->get('periode_type', 'bulanan');
        $bulan = $request->get('bulan', Carbon::today()->format('Y-m'));
        $tahunAjaran = $request->get('tahun_ajaran', Setting::getTahunAjaranAktif());
        $semester = $request->get('semester', Setting::getSemesterAktif());

        $this->authorizeGuruJadwal($user->id, $kelasId, $mapelId);

        $data = $this->getRekapData($user->id, $kelasId, $mapelId, $periodeType, $bulan, $tahunAjaran, $semester);

        $schoolName = Setting::get('school_name', 'Nama Sekolah');
        $semesterLabel = Setting::getSemesterLabel($semester);

        $pdf = Pdf::loadView('guru.rekap.pdf', array_merge($data, [
            'schoolName' => $schoolName,
            'tahunAjaran' => $tahunAjaran,
            'semesterLabel' => $semesterLabel,
            'guru' => $user,
        ]))->setPaper('a4', 'landscape');

        $periodLabel = $periodeType === 'bulanan' ? $bulan : $semester.'-'.$tahunAjaran;
        $cleanPeriod = str_replace(['/', ' '], '-', $periodLabel);
        $filename = 'rekap-kehadiran-'.$data['kelas']->nama.'-'.$cleanPeriod.'.pdf';

        return $pdf->download($filename);
    }

    public function exportExcel(Request $request): BinaryFileResponse|RedirectResponse
    {
        $user = auth()->user();
        $kelasId = (int) $request->get('kelas_id');
        $mapelId = (int) $request->get('mata_pelajaran_id');

        if (! $kelasId || ! $mapelId) {
            return redirect()->route('guru.rekap.index')->with('error', 'Silakan pilih kelas dan mata pelajaran terlebih dahulu.');
        }

        $periodeType = $request->get('periode_type', 'bulanan');
        $bulan = $request->get('bulan', Carbon::today()->format('Y-m'));
        $tahunAjaran = $request->get('tahun_ajaran', Setting::getTahunAjaranAktif());
        $semester = $request->get('semester', Setting::getSemesterAktif());

        $this->authorizeGuruJadwal($user->id, $kelasId, $mapelId);

        $data = $this->getRekapData($user->id, $kelasId, $mapelId, $periodeType, $bulan, $tahunAjaran, $semester);

        $tempPath = tempnam(sys_get_temp_dir(), 'rekap_guru_').'.xlsx';
        $writer = SimpleExcelWriter::create($tempPath);

        $periodInfo = $periodeType === 'bulanan'
            ? Carbon::parse($bulan.'-01')->translatedFormat('F Y')
            : Setting::getSemesterLabel($semester).' TA '.$tahunAjaran;

        $no = 1;
        foreach ($data['rekapSiswa'] as $row) {
            $writer->addRow([
                'No' => $no++,
                'Nama Siswa' => $row['siswa']->name,
                'NIS' => $row['nis'],
                'NISN' => $row['nisn'],
                'Kelas' => $data['kelas']->nama,
                'Mata Pelajaran' => $data['mataPelajaran']->nama,
                'Guru Pengampu' => $user->name,
                'Periode' => $periodInfo,
                'Hadir' => $row['hadir'],
                'Terlambat' => $row['terlambat'],
                'Sakit' => $row['sakit'],
                'Izin' => $row['izin'],
                'Dispensasi' => $row['dispensasi'],
                'Alpa' => $row['alpa'],
                'Total Pertemuan' => $row['total'],
                'Persentase Kehadiran' => $row['persentase'].'%',
            ]);
        }

        $writer->close();

        $periodLabel = $periodeType === 'bulanan' ? $bulan : $semester.'-'.$tahunAjaran;
        $cleanPeriod = str_replace(['/', ' '], '-', $periodLabel);
        $filename = 'rekap-kehadiran-'.$data['kelas']->nama.'-'.$cleanPeriod.'.xlsx';

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    private function getRekapData(
        int $guruId,
        int $kelasId,
        int $mapelId,
        string $periodeType,
        string $bulan,
        string $tahunAjaran,
        string $semester
    ): array {
        if ($periodeType === 'semester') {
            $range = Setting::getPeriodeSemesterRange($tahunAjaran, $semester);
            $startDate = $range['start'];
            $endDate = $range['end'];
        } else {
            try {
                $startDate = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
            } catch (\Throwable $e) {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                $bulan = Carbon::now()->format('Y-m');
            }
        }

        $kelas = Kelas::with(['siswaProfiles.user'])->findOrFail($kelasId);
        $mataPelajaran = MataPelajaran::findOrFail($mapelId);

        // Pertemuan guru untuk kelas & mapel ini pada rentang waktu
        $pertemuanIds = Pertemuan::whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereHas('jadwal', function ($q) use ($guruId, $kelasId, $mapelId) {
                $q->where('guru_id', $guruId)
                    ->where('kelas_id', $kelasId)
                    ->where('mata_pelajaran_id', $mapelId);
            })
            ->pluck('id');

        $kehadiranData = KehadiranSiswa::whereIn('pertemuan_id', $pertemuanIds)
            ->whereHas('siswa.siswaProfile', fn ($q) => $q->where('kelas_id', $kelasId))
            ->get();

        $rekapSiswa = [];
        $totalHadirSemua = 0;
        $totalTerlambatSemua = 0;
        $totalSakitSemua = 0;
        $totalIzinSemua = 0;
        $totalAlpaSemua = 0;
        $totalDispensasiSemua = 0;

        foreach ($kelas->siswaProfiles as $profile) {
            $siswaUser = $profile->user;
            if (! $siswaUser) {
                continue;
            }

            $items = $kehadiranData->where('siswa_id', $siswaUser->id);
            $hadir = $items->where('status', 'hadir')->count();
            $terlambat = $items->where('status', 'terlambat')->count();
            $sakit = $items->where('status', 'sakit')->count();
            $izin = $items->where('status', 'izin')->count();
            $alpa = $items->where('status', 'alpa')->count();
            $dispensasi = $items->where('status', 'dispensasi')->count();
            $totalPertemuan = $items->count();

            // Siswa hadir tepat waktu, terlambat, dan dispensasi dihitung hadir secara sah
            $persentase = $totalPertemuan > 0
                ? round((($hadir + $terlambat + $dispensasi) / $totalPertemuan) * 100, 1)
                : 0;

            $totalHadirSemua += $hadir;
            $totalTerlambatSemua += $terlambat;
            $totalSakitSemua += $sakit;
            $totalIzinSemua += $izin;
            $totalAlpaSemua += $alpa;
            $totalDispensasiSemua += $dispensasi;

            $rekapSiswa[] = [
                'siswa' => $siswaUser,
                'nis' => $profile->nis ?? '-',
                'nisn' => $profile->nisn ?? '-',
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'dispensasi' => $dispensasi,
                'total' => $totalPertemuan,
                'persentase' => $persentase,
            ];
        }

        // Sort by student name
        usort($rekapSiswa, fn ($a, $b) => strcmp($a['siswa']->name, $b['siswa']->name));

        return [
            'kelas' => $kelas,
            'mataPelajaran' => $mataPelajaran,
            'periodeType' => $periodeType,
            'bulan' => $bulan,
            'tahunAjaran' => $tahunAjaran,
            'semester' => $semester,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalPertemuanKelas' => $pertemuanIds->count(),
            'rekapSiswa' => $rekapSiswa,
            'totalHadir' => $totalHadirSemua,
            'totalTerlambat' => $totalTerlambatSemua,
            'totalSakit' => $totalSakitSemua,
            'totalIzin' => $totalIzinSemua,
            'totalAlpa' => $totalAlpaSemua,
            'totalDispensasi' => $totalDispensasiSemua,
        ];
    }

    private function authorizeGuruJadwal(int $guruId, int $kelasId, int $mapelId): void
    {
        $user = auth()->user();
        if (in_array($user->role, ['admin', 'super_admin']) || $user->hasAnyRole(['admin', 'super_admin'])) {
            return;
        }

        $allowed = JadwalPelajaran::where('guru_id', $guruId)
            ->where('kelas_id', $kelasId)
            ->where('mata_pelajaran_id', $mapelId)
            ->exists();

        abort_unless($allowed, 403, 'Anda tidak memiliki jadwal mengajar untuk kelas dan mata pelajaran ini.');
    }
}
