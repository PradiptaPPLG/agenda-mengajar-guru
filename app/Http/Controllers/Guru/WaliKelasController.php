<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\Setting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WaliKelasController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $user = auth()->user();

        $kelasBinaan = Kelas::where('wali_kelas_id', $userId)->get();

        if ($kelasBinaan->isEmpty()) {
            if (in_array($user->role, ['admin', 'super_admin']) || $user->hasAnyRole(['admin', 'super_admin'])) {
                $kelasBinaan = Kelas::orderBy('tingkat')->orderBy('nama')->get();
            } else {
                return redirect()->route('guru.dashboard')->with('error', 'Anda belum ditugaskan sebagai Wali Kelas.');
            }
        }

        $kelasIds = $kelasBinaan->pluck('id');
        $tab = $request->get('tab', 'harian');
        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());
        $date = Carbon::parse($tanggal);

        $periodeType = $request->get('periode_type', 'bulanan');
        $bulan = $request->get('bulan', Carbon::today()->format('Y-m'));
        $tahunAjaran = $request->get('tahun_ajaran', Setting::getTahunAjaranAktif());
        $semester = $request->get('semester', Setting::getSemesterAktif());
        $selectedKelasId = (int) $request->get('kelas_id', $kelasIds->first());

        if (! $kelasIds->contains($selectedKelasId)) {
            $selectedKelasId = $kelasIds->first();
        }

        // 1. Data Presensi Harian
        $kehadiran = KehadiranSiswa::with(['siswa.siswaProfile.kelas', 'pertemuan.jadwal.mataPelajaran'])
            ->whereHas('siswa.siswaProfile', function ($q) use ($kelasIds) {
                $q->whereIn('kelas_id', $kelasIds);
            })
            ->whereHas('pertemuan', function ($q) use ($tanggal) {
                $q->whereDate('tanggal', $tanggal);
            })
            ->get();

        $rekapKelas = [];
        foreach ($kelasBinaan as $k) {
            $kehadiranKelas = $kehadiran->filter(function ($item) use ($k) {
                return $item->siswa?->siswaProfile?->kelas_id === $k->id;
            });

            $rekapKelas[$k->id] = [
                'kelas' => $k,
                'hadir' => $kehadiranKelas->where('status', 'hadir')->count(),
                'terlambat' => $kehadiranKelas->where('status', 'terlambat')->count(),
                'sakit' => $kehadiranKelas->where('status', 'sakit')->count(),
                'izin' => $kehadiranKelas->where('status', 'izin')->count(),
                'alpa' => $kehadiranKelas->where('status', 'alpa')->count(),
                'dispensasi' => $kehadiranKelas->where('status', 'dispensasi')->count(),
            ];
        }

        // 2. Data Rekap Bulanan / Per Semester
        $rekapBulanan = null;
        if (in_array($tab, ['bulanan', 'rekap']) && $selectedKelasId) {
            $rekapBulanan = $this->getRekapData(
                $selectedKelasId,
                $periodeType,
                $bulan,
                $tahunAjaran,
                $semester
            );
        }

        // 3. Data Akun Siswa Kelas Binaan (Pengaturan Siswa Aktif yang Bisa Absen)
        $daftarSiswa = null;
        $totalSiswaKelas = 0;
        $totalSiswaAktifKelas = 0;
        $searchSiswa = $request->get('q', '');
        if ($tab === 'siswa' && $selectedKelasId) {
            $siswaQuery = User::where('role', 'siswa')
                ->whereHas('siswaProfile', function ($q) use ($selectedKelasId) {
                    $q->where('kelas_id', $selectedKelasId);
                })
                ->with('siswaProfile');

            $totalSiswaKelas = (clone $siswaQuery)->count();
            $totalSiswaAktifKelas = (clone $siswaQuery)->where('is_active', true)->count();

            if (! empty($searchSiswa)) {
                $siswaQuery->where(function ($q) use ($searchSiswa) {
                    $q->where('name', 'like', "%{$searchSiswa}%")
                        ->orWhereHas('siswaProfile', function ($sq) use ($searchSiswa) {
                            $sq->where('nis', 'like', "%{$searchSiswa}%");
                        });
                });
            }

            $daftarSiswa = $siswaQuery->orderBy('name')->get();
        }

        $daftarTahunAjaran = Setting::getDaftarTahunAjaran();
        $daftarSemester = Setting::getDaftarSemester();

        return view('guru.wali-kelas.index', compact(
            'kelasBinaan',
            'rekapKelas',
            'tanggal',
            'date',
            'kehadiran',
            'tab',
            'periodeType',
            'bulan',
            'tahunAjaran',
            'semester',
            'selectedKelasId',
            'rekapBulanan',
            'daftarTahunAjaran',
            'daftarSemester',
            'daftarSiswa',
            'totalSiswaKelas',
            'totalSiswaAktifKelas',
            'searchSiswa'
        ));
    }

    public function toggleSiswaActive(User $user): RedirectResponse
    {
        abort_unless($user->role === 'siswa', 403);
        $kelas = $user->siswaProfile?->kelas;
        abort_unless($kelas, 404, 'Siswa belum memiliki kelas binaan.');
        $this->authorizeKelas($kelas);

        $user->is_active = ! $user->is_active;
        $user->save();

        $statusStr = $user->is_active ? 'diaktifkan (dapat melakukan absen)' : 'dinonaktifkan (tidak dapat melakukan absen)';

        return back()->with('success', "Akun siswa {$user->name} berhasil {$statusStr}.");
    }

    public function bulkSiswaActive(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'action' => ['required', 'in:aktifkan_semua,nonaktifkan_semua'],
        ]);

        $kelas = Kelas::findOrFail($validated['kelas_id']);
        $this->authorizeKelas($kelas);

        $isActive = $validated['action'] === 'aktifkan_semua';
        $count = User::where('role', 'siswa')
            ->whereHas('siswaProfile', fn ($q) => $q->where('kelas_id', $kelas->id))
            ->update(['is_active' => $isActive]);

        $statusStr = $isActive ? 'diaktifkan (dapat absen)' : 'dinonaktifkan (tidak dapat absen)';

        return back()->with('success', "Seluruh {$count} akun siswa di kelas {$kelas->nama} berhasil {$statusStr}.");
    }

    public function exportPdf(Request $request): Response|RedirectResponse
    {
        $user = auth()->user();
        $kelasBinaan = Kelas::where('wali_kelas_id', $user->id)->get();
        if ($kelasBinaan->isEmpty() && (in_array($user->role, ['admin', 'super_admin']) || $user->hasAnyRole(['admin', 'super_admin']))) {
            $kelasBinaan = Kelas::orderBy('tingkat')->orderBy('nama')->get();
        }

        $kelasId = (int) $request->get('kelas_id', $kelasBinaan->first()?->id ?? 0);
        if (! $kelasId) {
            return redirect()->route('guru.wali-kelas.index')->with('error', 'Kelas binaan tidak ditemukan.');
        }

        $kelas = Kelas::find($kelasId);
        if (! $kelas) {
            return redirect()->route('guru.wali-kelas.index')->with('error', 'Kelas tidak ditemukan.');
        }
        $this->authorizeKelas($kelas);

        $periodeType = $request->get('periode_type', 'bulanan');
        $bulan = $request->get('bulan', Carbon::today()->format('Y-m'));
        $tahunAjaran = $request->get('tahun_ajaran', Setting::getTahunAjaranAktif());
        $semester = $request->get('semester', Setting::getSemesterAktif());

        $data = $this->getRekapData($kelasId, $periodeType, $bulan, $tahunAjaran, $semester);

        $schoolName = Setting::get('school_name', 'Nama Sekolah');
        $semesterLabel = Setting::getSemesterLabel($semester);

        $pdf = Pdf::loadView('guru.wali-kelas.pdf-bulanan', array_merge($data, [
            'schoolName' => $schoolName,
            'tahunAjaran' => $tahunAjaran,
            'semesterLabel' => $semesterLabel,
            'waliKelas' => $user,
        ]))->setPaper('a4', 'landscape');

        $periodLabel = $periodeType === 'bulanan' ? $bulan : $semester.'-'.$tahunAjaran;
        $cleanPeriod = str_replace(['/', ' '], '-', $periodLabel);
        $filename = 'rekap-kehadiran-wali-'.$kelas->nama.'-'.$cleanPeriod.'.pdf';

        return $pdf->download($filename);
    }

    public function exportExcel(Request $request): BinaryFileResponse|RedirectResponse
    {
        $user = auth()->user();
        $kelasBinaan = Kelas::where('wali_kelas_id', $user->id)->get();
        if ($kelasBinaan->isEmpty() && (in_array($user->role, ['admin', 'super_admin']) || $user->hasAnyRole(['admin', 'super_admin']))) {
            $kelasBinaan = Kelas::orderBy('tingkat')->orderBy('nama')->get();
        }

        $kelasId = (int) $request->get('kelas_id', $kelasBinaan->first()?->id ?? 0);
        if (! $kelasId) {
            return redirect()->route('guru.wali-kelas.index')->with('error', 'Kelas binaan tidak ditemukan.');
        }

        $kelas = Kelas::find($kelasId);
        if (! $kelas) {
            return redirect()->route('guru.wali-kelas.index')->with('error', 'Kelas tidak ditemukan.');
        }
        $this->authorizeKelas($kelas);

        $periodeType = $request->get('periode_type', 'bulanan');
        $bulan = $request->get('bulan', Carbon::today()->format('Y-m'));
        $tahunAjaran = $request->get('tahun_ajaran', Setting::getTahunAjaranAktif());
        $semester = $request->get('semester', Setting::getSemesterAktif());

        $data = $this->getRekapData($kelasId, $periodeType, $bulan, $tahunAjaran, $semester);

        $periodInfo = $periodeType === 'bulanan'
            ? Carbon::parse($bulan.'-01')->translatedFormat('F Y')
            : Setting::getSemesterLabel($semester).' TA '.$tahunAjaran;

        $tempPath = tempnam(sys_get_temp_dir(), 'rekap_wali_').'.xlsx';
        $writer = SimpleExcelWriter::create($tempPath);

        $no = 1;
        foreach ($data['rekapSiswa'] as $row) {
            $writer->addRow([
                'No' => $no++,
                'Nama Siswa' => $row['siswa']->name,
                'NIS' => $row['nis'],
                'NISN' => $row['nisn'],
                'Kelas' => $kelas->nama,
                'Wali Kelas' => $user->name,
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
        $filename = 'rekap-kehadiran-wali-'.$kelas->nama.'-'.$cleanPeriod.'.xlsx';

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    public function getRekapData(
        int $kelasId,
        string $periodeType = 'bulanan',
        string $bulan = '',
        string $tahunAjaran = '',
        string $semester = ''
    ): array {
        if ($periodeType === 'semester') {
            $tahunAjaran = $tahunAjaran ?: Setting::getTahunAjaranAktif();
            $semester = $semester ?: Setting::getSemesterAktif();
            $range = Setting::getPeriodeSemesterRange($tahunAjaran, $semester);
            $startDate = $range['start'];
            $endDate = $range['end'];
        } else {
            try {
                $bulan = $bulan ?: Carbon::now()->format('Y-m');
                $startDate = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
            } catch (\Throwable $e) {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                $bulan = Carbon::now()->format('Y-m');
            }
        }

        $kelas = Kelas::with(['siswaProfiles.user'])->findOrFail($kelasId);

        $pertemuanIds = Pertemuan::whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereHas('jadwal', fn ($j) => $j->where('kelas_id', $kelasId))
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

            // Persentase kehadiran: siswa hadir tepat waktu, hadir terlambat, dan dispensasi dihitung hadir
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

    private function getRekapBulananData(int $kelasId, string $bulan): array
    {
        return $this->getRekapData($kelasId, 'bulanan', $bulan);
    }

    private function authorizeKelas(Kelas $kelas): void
    {
        $user = auth()->user();
        if (in_array($user->role, ['admin', 'super_admin']) || $user->hasAnyRole(['admin', 'super_admin'])) {
            return;
        }
        abort_unless($kelas->wali_kelas_id === $user->id, 403, 'Anda bukan wali kelas dari kelas ini.');
    }
}
