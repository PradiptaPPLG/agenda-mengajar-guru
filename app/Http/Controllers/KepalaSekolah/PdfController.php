<?php

namespace App\Http\Controllers\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuru;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\RekapKehadiranSiswa;
use App\Models\Setting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PdfController extends Controller
{
    public function guru(Request $request): Response
    {
        $selectedTahunAjaran = $request->input('tahun_ajaran', Setting::getTahunAjaranAktif());
        $selectedSemester = $request->input('semester', Setting::getSemesterAktif());
        $filterMode = $request->input('filter_mode', 'semester');

        if ($filterMode === 'semester' && $selectedTahunAjaran !== 'all' && $selectedSemester !== 'all') {
            $range = Setting::getPeriodeSemesterRange($selectedTahunAjaran, $selectedSemester);
            $startDate = $range['start'];
            $endDate = $range['end'];
        } else {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->input('start_date'))->startOfDay()
                : Carbon::now()->startOfMonth();

            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->input('end_date'))->endOfDay()
                : Carbon::now()->endOfMonth();
        }

        $selectedGuruId = $request->input('guru_id');
        $selectedKelasId = $request->input('kelas_id');
        $selectedTingkat = $request->input('tingkat', 'all');
        $selectedJurusan = $request->input('jurusan', 'all');

        $kelasQuery = Kelas::query();
        if ($selectedTingkat !== 'all') {
            $kelasQuery->where(function ($q) use ($selectedTingkat) {
                if ($selectedTingkat === '10') {
                    $q->whereIn('tingkat', ['10', 'X'])->orWhere('nama', 'like', '10%')->orWhere('nama', 'like', 'X %');
                } elseif ($selectedTingkat === '11') {
                    $q->whereIn('tingkat', ['11', 'XI'])->orWhere('nama', 'like', '11%')->orWhere('nama', 'like', 'XI %');
                } elseif ($selectedTingkat === '12') {
                    $q->whereIn('tingkat', ['12', 'XII'])->orWhere('nama', 'like', '12%')->orWhere('nama', 'like', 'XII %');
                }
            });
        }

        if ($selectedJurusan !== 'all') {
            $jUpper = strtoupper(trim($selectedJurusan));
            $kelasQuery->where(function ($q) use ($jUpper) {
                match ($jUpper) {
                    'PPLG' => $q->where('nama', 'like', '%PPLG%')->orWhere('nama', 'like', '%RPL%'),
                    'AKL' => $q->where('nama', 'like', '%AKL%')->orWhere('nama', 'like', '%AK%'),
                    'MPLB' => $q->where('nama', 'like', '%MPLB%')->orWhere('nama', 'like', '%MP%'),
                    'PM' => $q->where('nama', 'like', '%PM%')->orWhere('nama', 'like', '%PBR%')->orWhere('nama', 'like', '%PBD%'),
                    'DKV' => $q->where('nama', 'like', '%DKV%'),
                    'KLN' => $q->where('nama', 'like', '%KLN%')->orWhere('nama', 'like', '%KULINER%'),
                    'HTL' => $q->where('nama', 'like', '%HTL%')->orWhere('nama', 'like', '%HOTEL%'),
                    'PBS' => $q->where('nama', 'like', '%PBS%'),
                    default => $q->where('nama', 'like', "%{$jUpper}%"),
                };
            });
        }

        if ($selectedKelasId) {
            $targetKelasIds = collect([$selectedKelasId]);
        } elseif ($selectedTingkat !== 'all' || $selectedJurusan !== 'all') {
            $targetKelasIds = $kelasQuery->pluck('id');
        } else {
            $targetKelasIds = null;
        }

        $baseQuery = KehadiranGuru::whereHas('pertemuan', function ($q) use ($startDate, $endDate, $targetKelasIds, $selectedTahunAjaran, $selectedSemester, $filterMode) {
            $q->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()]);

            if ($filterMode === 'semester') {
                if ($selectedTahunAjaran !== 'all') {
                    $q->where(fn ($sub) => $sub->where('tahun_ajaran', $selectedTahunAjaran)->orWhereNull('tahun_ajaran'));
                }
                if ($selectedSemester !== 'all') {
                    $q->where(fn ($sub) => $sub->where('semester', $selectedSemester)->orWhereNull('semester'));
                }
            }

            if ($targetKelasIds !== null) {
                $q->whereHas('jadwal', fn ($j) => $j->whereIn('kelas_id', $targetKelasIds));
            }
        });

        if ($selectedGuruId) {
            $baseQuery->where('guru_id', $selectedGuruId);
        }

        // Hitung ringkasan per guru secara langsung di level database (hemat RAM)
        $teacherStats = (clone $baseQuery)
            ->selectRaw("
                guru_id,
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = 'sakit' OR alasan_tidak_hadir = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status IN ('dispensasi', 'izin') OR alasan_tidak_hadir IN ('izin', 'cuti', 'rapat_dinas', 'dinas_luar', 'tugas_luar', 'dispensasi') THEN 1 ELSE 0 END) as dispensasi
            ")
            ->groupBy('guru_id')
            ->get();

        $guruIds = $teacherStats->pluck('guru_id');
        $guruModels = User::whereIn('id', $guruIds)->get()->keyBy('id');

        $summary = $teacherStats->map(function ($stat) use ($guruModels) {
            $hadir = (int) $stat->hadir;
            $sakit = (int) $stat->sakit;
            $dispensasi = (int) $stat->dispensasi;
            $total = (int) $stat->total;
            $alpa = max(0, $total - $hadir - $sakit - $dispensasi);

            return [
                'guru' => $guruModels->get($stat->guru_id),
                'hadir' => $hadir,
                'sakit' => $sakit,
                'dispensasi' => $dispensasi,
                'alpa' => $alpa,
                'total' => $total,
            ];
        })->sortBy(fn ($item) => $item['guru']?->name ?? '')->values();

        // Ambil detail catatan kehadiran dengan seleksi kolom esensial dan batasan keamanan render PDF
        $kehadiran = (clone $baseQuery)
            ->select([
                'kehadiran_gurus.id',
                'kehadiran_gurus.pertemuan_id',
                'kehadiran_gurus.guru_id',
                'kehadiran_gurus.status',
                'kehadiran_gurus.alasan_tidak_hadir',
                'kehadiran_gurus.jenis_alpa',
                'kehadiran_gurus.guru_pengganti_nama',
                'kehadiran_gurus.keterangan',
            ])
            ->with([
                'guru:id,name',
                'pertemuan:id,jadwal_id,tanggal',
                'pertemuan.jadwal:id,kelas_id,mata_pelajaran_id',
                'pertemuan.jadwal.kelas:id,nama',
                'pertemuan.jadwal.mataPelajaran:id,nama',
            ])
            ->orderBy(
                Pertemuan::select('tanggal')
                    ->whereColumn('pertemuans.id', 'kehadiran_gurus.pertemuan_id')
                    ->limit(1)
            )
            ->limit(1000)
            ->get();

        $schoolName = Setting::get('school_name', 'Nama Sekolah');
        $selectedGuru = $selectedGuruId ? User::find($selectedGuruId) : null;
        $selectedKelas = $selectedKelasId ? Kelas::find($selectedKelasId) : null;
        $tahunAjaran = $selectedTahunAjaran;
        $semesterLabel = Setting::getSemesterLabel($selectedSemester);

        $pdf = Pdf::loadView('kepala-sekolah.pdf.guru', compact(
            'kehadiran', 'summary', 'startDate', 'endDate',
            'schoolName', 'selectedGuru', 'selectedKelas',
            'tahunAjaran', 'semesterLabel'
        ))->setPaper('a4', 'landscape');

        $filename = 'laporan-kehadiran-guru-'.$startDate->format('Y-m-d').'-sd-'.$endDate->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function siswa(Request $request): Response
    {
        $selectedTahunAjaran = $request->input('tahun_ajaran', Setting::getTahunAjaranAktif());
        $selectedSemester = $request->input('semester', Setting::getSemesterAktif());
        $filterMode = $request->input('filter_mode', 'semester');

        if ($filterMode === 'semester' && $selectedTahunAjaran !== 'all' && $selectedSemester !== 'all') {
            $range = Setting::getPeriodeSemesterRange($selectedTahunAjaran, $selectedSemester);
            $startDate = $range['start'];
            $endDate = $range['end'];
        } else {
            try {
                $startDate = $request->filled('start_date')
                    ? Carbon::parse($request->input('start_date'))->startOfDay()
                    : Carbon::now()->startOfMonth();

                $endDate = $request->filled('end_date')
                    ? Carbon::parse($request->input('end_date'))->endOfDay()
                    : Carbon::now()->endOfMonth();
            } catch (\Throwable) {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            }
        }

        $selectedKelasId = $request->input('kelas_id');
        $selectedTingkat = $request->input('tingkat', 'all');
        $selectedJurusan = $request->input('jurusan', 'all');

        $kelasQuery = Kelas::query();
        if ($selectedTingkat !== 'all') {
            $kelasQuery->where(function ($q) use ($selectedTingkat) {
                if ($selectedTingkat === '10') {
                    $q->whereIn('tingkat', ['10', 'X'])->orWhere('nama', 'like', '10%')->orWhere('nama', 'like', 'X %');
                } elseif ($selectedTingkat === '11') {
                    $q->whereIn('tingkat', ['11', 'XI'])->orWhere('nama', 'like', '11%')->orWhere('nama', 'like', 'XI %');
                } elseif ($selectedTingkat === '12') {
                    $q->whereIn('tingkat', ['12', 'XII'])->orWhere('nama', 'like', '12%')->orWhere('nama', 'like', 'XII %');
                }
            });
        }

        if ($selectedJurusan !== 'all') {
            $jUpper = strtoupper(trim($selectedJurusan));
            $kelasQuery->where(function ($q) use ($jUpper) {
                match ($jUpper) {
                    'PPLG' => $q->where('nama', 'like', '%PPLG%')->orWhere('nama', 'like', '%RPL%'),
                    'AKL' => $q->where('nama', 'like', '%AKL%')->orWhere('nama', 'like', '%AK%'),
                    'MPLB' => $q->where('nama', 'like', '%MPLB%')->orWhere('nama', 'like', '%MP%'),
                    'PM' => $q->where('nama', 'like', '%PM%')->orWhere('nama', 'like', '%PBR%')->orWhere('nama', 'like', '%PBD%'),
                    'DKV' => $q->where('nama', 'like', '%DKV%'),
                    'KLN' => $q->where('nama', 'like', '%KLN%')->orWhere('nama', 'like', '%KULINER%'),
                    'HTL' => $q->where('nama', 'like', '%HTL%')->orWhere('nama', 'like', '%HOTEL%'),
                    'PBS' => $q->where('nama', 'like', '%PBS%'),
                    default => $q->where('nama', 'like', "%{$jUpper}%"),
                };
            });
        }

        if ($selectedKelasId) {
            $targetKelasIds = collect([$selectedKelasId]);
        } elseif ($selectedTingkat !== 'all' || $selectedJurusan !== 'all') {
            $targetKelasIds = $kelasQuery->pluck('id');
        } else {
            $targetKelasIds = null;
        }

        $pertemuanSubQuery = Pertemuan::whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($filterMode === 'semester') {
            if ($selectedTahunAjaran !== 'all') {
                $pertemuanSubQuery->where(fn ($sub) => $sub->where('tahun_ajaran', $selectedTahunAjaran)->orWhereNull('tahun_ajaran'));
            }
            if ($selectedSemester !== 'all') {
                $pertemuanSubQuery->where(fn ($sub) => $sub->where('semester', $selectedSemester)->orWhereNull('semester'));
            }
        }

        if ($targetKelasIds !== null) {
            $pertemuanSubQuery->whereHas('jadwal', fn ($j) => $j->whereIn('kelas_id', $targetKelasIds));
        }

        // Ringkasan per siswa: Fast-Path (Mode Semester) vs Fallback (Mode Custom)
        if ($filterMode === 'semester') {
            $rekapQuery = RekapKehadiranSiswa::query();

            if ($selectedTahunAjaran !== 'all') {
                $rekapQuery->where('tahun_ajaran', $selectedTahunAjaran);
            }
            if ($selectedSemester !== 'all') {
                $sem = in_array($selectedSemester, ['2', 'genap'], true) ? 'genap' : 'ganjil';
                $rekapQuery->where(function ($q) use ($selectedSemester, $sem) {
                    $q->where('semester', $selectedSemester)->orWhere('semester', $sem);
                });
            }
            if ($targetKelasIds !== null) {
                $rekapQuery->whereIn('kelas_id', $targetKelasIds);
            }

            $siswaStats = $rekapQuery
                ->selectRaw('siswa_id, SUM(total) as total, SUM(hadir) as hadir, SUM(sakit) as sakit, SUM(izin) as izin, SUM(alpa) as alpa, SUM(dispensasi) as dispensasi')
                ->groupBy('siswa_id')
                ->get();
        } else {
            $siswaStats = KehadiranSiswa::whereIn('pertemuan_id', $pertemuanSubQuery->select('id'))
                ->selectRaw("siswa_id,
                    count(*) as total,
                    SUM(CASE WHEN status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                    SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                    SUM(CASE WHEN status = 'alpa' THEN 1 ELSE 0 END) as alpa,
                    SUM(CASE WHEN status = 'dispensasi' THEN 1 ELSE 0 END) as dispensasi
                ")
                ->groupBy('siswa_id')
                ->get();
        }

        $siswaIds = $siswaStats->pluck('siswa_id');
        $siswaModels = User::with('siswaProfile.kelas')->whereIn('id', $siswaIds)->get()->keyBy('id');

        $summary = $siswaStats->map(function ($stat) use ($siswaModels) {
            $siswa = $siswaModels->get($stat->siswa_id);

            return [
                'siswa' => $siswa,
                'siswa_id' => $stat->siswa_id,
                'kelas_nama' => $siswa?->siswaProfile?->kelas?->nama ?? '-',
                'hadir' => (int) $stat->hadir,
                'sakit' => (int) $stat->sakit,
                'izin' => (int) $stat->izin,
                'alpa' => (int) $stat->alpa,
                'dispensasi' => (int) $stat->dispensasi,
                'total' => (int) $stat->total,
            ];
        })->sortBy(fn ($item) => $item['siswa']?->name ?? '')->values();

        $schoolName = Setting::get('school_name', 'Nama Sekolah');
        $selectedKelas = $selectedKelasId ? Kelas::find($selectedKelasId) : null;
        $tahunAjaran = $selectedTahunAjaran;
        $semesterLabel = Setting::getSemesterLabel($selectedSemester);

        $pdf = Pdf::loadView('kepala-sekolah.pdf.siswa', compact(
            'summary', 'startDate', 'endDate', 'schoolName', 'selectedKelas',
            'tahunAjaran', 'semesterLabel'
        ))->setPaper('a4', 'landscape');

        $filename = 'laporan-kehadiran-siswa-'.$startDate->format('Y-m-d').'-sd-'.$endDate->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
