<?php

namespace App\Http\Controllers\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuru;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function guru(Request $request): View
    {
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $kelasList = Kelas::orderBy('nama')->get();

        $selectedTahunAjaran = $request->input('tahun_ajaran', Setting::getTahunAjaranAktif());
        $selectedSemester = $request->input('semester', Setting::getSemesterAktif());
        $filterMode = $request->input('filter_mode', 'semester'); // 'semester' atau 'custom'

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

        // Filter Angkatan (Tingkat), Jurusan, Guru, dan Kelas
        $selectedTingkat = $request->input('tingkat', 'all');
        $selectedJurusan = $request->input('jurusan', 'all');
        $selectedGuruId = $request->input('guru_id');
        $selectedKelasId = $request->input('kelas_id');
        $onlyDiscrepancy = $request->boolean('only_discrepancy');

        $daftarJurusan = [
            'PPLG' => 'PPLG (Pengembangan Perangkat Lunak & Gim)',
            'AKL' => 'AKL (Akuntansi & Keuangan Lembaga)',
            'MPLB' => 'MPLB (Manajemen Perkantoran & Layanan Bisnis)',
            'PM' => 'PM (Pemasaran / Bisnis Retail)',
            'DKV' => 'DKV (Desain Komunikasi Visual)',
            'KLN' => 'KLN (Kuliner)',
            'HTL' => 'HTL (Perhotelan)',
            'PBS' => 'PBS (Perbankan Syariah)',
        ];

        $daftarTingkat = [
            'all' => 'Semua Angkatan',
            '10' => 'Kelas 10 (Fase E)',
            '11' => 'Kelas 11 (Fase F)',
            '12' => 'Kelas 12 (Fase F Lanjutan)',
        ];

        // Query kelas yang memenuhi filter tingkat & jurusan
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

        $matchingKelasList = (clone $kelasQuery)->orderBy('tingkat')->orderBy('nama')->get();
        $matchingKelasIds = $matchingKelasList->pluck('id');

        if ($selectedKelasId) {
            $targetKelasIds = collect([$selectedKelasId]);
        } elseif ($selectedTingkat !== 'all' || $selectedJurusan !== 'all') {
            $targetKelasIds = $matchingKelasIds;
        } else {
            $targetKelasIds = null;
        }

        $query = KehadiranGuru::with([
            'guru',
            'pertemuan.jadwal.kelas',
            'pertemuan.jadwal.mataPelajaran',
            'pertemuan.fotoBuktis',
        ])
            ->whereHas('pertemuan', function ($q) use ($startDate, $endDate, $targetKelasIds, $selectedTahunAjaran, $selectedSemester, $filterMode) {
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
            $query->where('guru_id', $selectedGuruId);
        }

        $allKehadiran = $query->orderBy('created_at', 'desc')->get();
        $totalKehadiranRecords = $allKehadiran->count();
        $discrepancyCount = $allKehadiran->filter(fn ($kh) => $kh->has_discrepancy)->count();

        // ── Agregasi Alasan & Persentase Kehadiran Guru (Pivot Style) ──
        $breakdownGroups = [];
        foreach ($allKehadiran as $row) {
            $isHadir = $row->isHadirCategory();
            $rawKet = trim($row->keterangan ?? '');

            if ($isHadir) {
                // Sesuai pivot: status hadir bernilai '-' jika keterangan kosong
                $label = ($rawKet === '' || $rawKet === '-') ? '-' : $rawKet;
                $category = 'hadir';
            } else {
                if ($rawKet !== '' && $rawKet !== '-') {
                    $label = ucfirst($rawKet);
                } elseif ($row->alasan_tidak_hadir_label) {
                    $label = $row->alasan_tidak_hadir_label;
                } else {
                    $label = $row->status_label;
                }
                $category = strtolower($row->status ?? 'tidak_hadir');
            }

            if (! isset($breakdownGroups[$label])) {
                $breakdownGroups[$label] = [
                    'label' => $label,
                    'category' => $category,
                    'count' => 0,
                    'percentage' => 0,
                ];
            }
            $breakdownGroups[$label]['count']++;
        }

        // Hitung persentase desimal presisi 2 digit
        $alasanBreakdown = collect($breakdownGroups)->map(function ($item) use ($totalKehadiranRecords) {
            $item['percentage'] = $totalKehadiranRecords > 0
                ? round(($item['count'] / $totalKehadiranRecords) * 100, 2)
                : 0;

            return $item;
        })->sortByDesc('count')->values();

        $grandTotalCount = $totalKehadiranRecords;
        $grandTotalPercentage = $totalKehadiranRecords > 0 ? 100.00 : 0.00;

        // Siapkan warna dan dataset untuk Pie / Donut Chart
        $colorPalette = [
            '#2563EB', // Blue (Hadir '-')
            '#EF4444', // Red
            '#F59E0B', // Amber
            '#10B981', // Emerald
            '#8B5CF6', // Purple
            '#EC4899', // Pink
            '#06B6D4', // Cyan
            '#F97316', // Orange
            '#6366F1', // Indigo
            '#14B8A6', // Teal
            '#84CC16', // Lime
            '#A855F7', // Violet
            '#E11D48', // Rose
            '#64748B', // Slate
            '#D97706', // Amber dark
            '#0284C7', // Sky
            '#7C3AED', // Violet dark
            '#DB2777', // Pink dark
        ];

        $pieLabels = [];
        $piePercentages = [];
        $pieCounts = [];
        $pieColors = [];

        foreach ($alasanBreakdown as $idx => $item) {
            $pieLabels[] = $item['label'];
            $piePercentages[] = $item['percentage'];
            $pieCounts[] = $item['count'];

            if ($item['label'] === '-' || strtolower($item['label']) === 'hadir') {
                $pieColors[] = '#2563EB'; // Royal Blue khas pivot excel
            } else {
                $pieColors[] = $colorPalette[$idx % count($colorPalette)];
            }
        }

        $kehadiranCollection = $onlyDiscrepancy
            ? $allKehadiran->filter(fn ($kh) => $kh->has_discrepancy)->values()
            : $allKehadiran;

        // Paginate Kehadiran Detail (10 per page)
        $kehadiranPage = LengthAwarePaginator::resolveCurrentPage('page');
        $perPage = 10;
        $kehadiran = new LengthAwarePaginator(
            $kehadiranCollection->slice(($kehadiranPage - 1) * $perPage, $perPage)->values(),
            $kehadiranCollection->count(),
            $perPage,
            $kehadiranPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page', 'query' => $request->query()]
        );

        // Summary per guru (10 per page)
        $summaryCollection = $allKehadiran->groupBy('guru_id')->map(function ($items) {
            return [
                'guru' => $items->first()->guru,
                'hadir' => $items->filter(fn ($item) => $item->isHadirCategory())->count(),
                'sakit' => $items->filter(fn ($item) => $item->isSakitCategory())->count(),
                'dispensasi' => $items->filter(fn ($item) => $item->isDispensasiCategory())->count(),
                'alpa' => $items->filter(fn ($item) => $item->isAlpaCategory())->count(),
                'total' => $items->count(),
            ];
        })->values();

        $summaryPage = LengthAwarePaginator::resolveCurrentPage('summary_page');
        $summary = new LengthAwarePaginator(
            $summaryCollection->slice(($summaryPage - 1) * $perPage, $perPage)->values(),
            $summaryCollection->count(),
            $perPage,
            $summaryPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'summary_page', 'query' => $request->query()]
        );

        $daftarTahunAjaran = Setting::getDaftarTahunAjaran();
        $daftarSemester = Setting::getDaftarSemester();

        return view('kepala-sekolah.report.guru', compact(
            'kehadiran',
            'summary',
            'guruList',
            'kelasList',
            'matchingKelasList',
            'startDate',
            'endDate',
            'selectedGuruId',
            'selectedKelasId',
            'selectedTingkat',
            'selectedJurusan',
            'daftarJurusan',
            'daftarTingkat',
            'onlyDiscrepancy',
            'discrepancyCount',
            'selectedTahunAjaran',
            'selectedSemester',
            'daftarTahunAjaran',
            'daftarSemester',
            'filterMode',
            'alasanBreakdown',
            'grandTotalCount',
            'grandTotalPercentage',
            'pieLabels',
            'piePercentages',
            'pieCounts',
            'pieColors'
        ));
    }

    public function siswa(Request $request): View
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

        // Filter Angkatan (Tingkat), Jurusan, Kelas, dan Siswa
        $selectedTingkat = $request->input('tingkat', 'all');
        $selectedJurusan = $request->input('jurusan', 'all');
        $selectedKelasId = $request->input('kelas_id');
        $selectedSiswaId = $request->input('siswa_id');

        $daftarJurusan = [
            'PPLG' => 'PPLG (Pengembangan Perangkat Lunak & Gim)',
            'AKL' => 'AKL (Akuntansi & Keuangan Lembaga)',
            'MPLB' => 'MPLB (Manajemen Perkantoran & Layanan Bisnis)',
            'PM' => 'PM (Pemasaran / Bisnis Retail)',
            'DKV' => 'DKV (Desain Komunikasi Visual)',
            'KLN' => 'KLN (Kuliner)',
            'HTL' => 'HTL (Perhotelan)',
            'PBS' => 'PBS (Perbankan Syariah)',
        ];

        $daftarTingkat = [
            'all' => 'Semua Angkatan',
            '10' => 'Kelas 10 (Fase E)',
            '11' => 'Kelas 11 (Fase F)',
            '12' => 'Kelas 12 (Fase F Lanjutan)',
        ];

        // Query kelas yang memenuhi filter tingkat & jurusan
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

        // Kelas untuk dropdown filter
        $matchingKelasList = (clone $kelasQuery)->orderBy('tingkat')->orderBy('nama')->get();
        $matchingKelasIds = $matchingKelasList->pluck('id');

        // Jika user memilih spesifik kelas_id
        if ($selectedKelasId) {
            $targetKelasIds = collect([$selectedKelasId]);
        } else {
            $targetKelasIds = $matchingKelasIds;
        }

        // Query pertemuan yang sesuai rentang tanggal & kelas yang terfilter (subquery)
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

        // Query dasar presensi siswa (menggunakan subquery efisien)
        $baseKehadiranQuery = KehadiranSiswa::whereIn('pertemuan_id', $pertemuanSubQuery->select('id'));

        if ($selectedSiswaId && $selectedSiswaId !== 'all') {
            $baseKehadiranQuery->where('siswa_id', $selectedSiswaId);
        }

        $totalKehadiranRecords = (clone $baseKehadiranQuery)->count();

        // ── Agregasi Alasan & Persentase Kehadiran Siswa (Database Level GROUP BY) ──
        $rawBreakdown = (clone $baseKehadiranQuery)
            ->selectRaw("status, COALESCE(TRIM(keterangan), '') as ket, count(*) as count")
            ->groupBy('status', 'ket')
            ->get();

        $breakdownGroups = [];
        foreach ($rawBreakdown as $row) {
            $status = strtolower($row->status ?? 'hadir');
            $rawKet = trim($row->ket ?? '');

            if ($status === 'hadir') {
                // Sesuai pivot di Excel: status hadir bernilai '-' jika keterangan kosong
                $label = ($rawKet === '' || $rawKet === '-') ? '-' : $rawKet;
                $category = 'hadir';
            } else {
                if ($rawKet !== '' && $rawKet !== '-') {
                    $label = ucfirst($rawKet);
                } else {
                    $label = match ($status) {
                        'sakit' => 'Sakit',
                        'izin' => 'Izin',
                        'alpa' => 'Alpa',
                        'dispensasi' => 'Dispensasi',
                        'terlambat' => 'Terlambat',
                        default => ucfirst($status),
                    };
                }
                $category = $status;
            }

            if (! isset($breakdownGroups[$label])) {
                $breakdownGroups[$label] = [
                    'label' => $label,
                    'category' => $category,
                    'count' => 0,
                    'percentage' => 0,
                ];
            }
            $breakdownGroups[$label]['count'] += (int) $row->count;
        }

        // Hitung persentase desimal presisi 2 digit
        $alasanBreakdown = collect($breakdownGroups)->map(function ($item) use ($totalKehadiranRecords) {
            $item['percentage'] = $totalKehadiranRecords > 0
                ? round(($item['count'] / $totalKehadiranRecords) * 100, 2)
                : 0;

            return $item;
        })->sortByDesc('count')->values();

        $grandTotalCount = $totalKehadiranRecords;
        $grandTotalPercentage = $totalKehadiranRecords > 0 ? 100.00 : 0.00;

        // Siapkan warna dan dataset untuk Pie / Donut Chart
        $colorPalette = [
            '#2563EB', '#EF4444', '#F59E0B', '#10B981', '#8B5CF6',
            '#EC4899', '#06B6D4', '#F97316', '#6366F1', '#14B8A6',
            '#84CC16', '#A855F7', '#E11D48', '#64748B', '#D97706',
            '#0284C7', '#7C3AED', '#DB2777',
        ];

        $pieLabels = [];
        $piePercentages = [];
        $pieCounts = [];
        $pieColors = [];

        foreach ($alasanBreakdown as $idx => $item) {
            $pieLabels[] = $item['label'];
            $piePercentages[] = $item['percentage'];
            $pieCounts[] = $item['count'];

            if ($item['label'] === '-' || strtolower($item['label']) === 'hadir') {
                $pieColors[] = '#2563EB'; // Royal Blue khas pivot excel
            } else {
                $pieColors[] = $colorPalette[$idx % count($colorPalette)];
            }
        }

        // Summary per siswa (10 per page, langsung diagregasi di database)
        $paginatedSiswaStats = (clone $baseKehadiranQuery)
            ->selectRaw("siswa_id,
                count(*) as total,
                SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status = 'alpa' THEN 1 ELSE 0 END) as alpa,
                SUM(CASE WHEN status = 'dispensasi' THEN 1 ELSE 0 END) as dispensasi
            ")
            ->groupBy('siswa_id')
            ->paginate(10)
            ->appends($request->query());

        $siswaIdsOnPage = $paginatedSiswaStats->pluck('siswa_id');
        $siswaModels = User::with('siswaProfile.kelas')->whereIn('id', $siswaIdsOnPage)->get()->keyBy('id');

        $summary = $paginatedSiswaStats->through(function ($stat) use ($siswaModels) {
            $siswa = $siswaModels->get($stat->siswa_id);

            return [
                'siswa' => $siswa,
                'kelas_nama' => $siswa?->siswaProfile?->kelas?->nama ?? '-',
                'hadir' => (int) $stat->hadir,
                'sakit' => (int) $stat->sakit,
                'izin' => (int) $stat->izin,
                'alpa' => (int) $stat->alpa,
                'dispensasi' => (int) $stat->dispensasi,
                'total' => (int) $stat->total,
            ];
        });

        $daftarTahunAjaran = Setting::getDaftarTahunAjaran();
        $daftarSemester = Setting::getDaftarSemester();
        $allKelasList = Kelas::orderBy('nama')->get();

        // Daftar siswa untuk filter opsional
        $siswaFilterList = $selectedKelasId
            ? User::where('role', 'siswa')->whereHas('siswaProfile', fn ($sp) => $sp->where('kelas_id', $selectedKelasId))->orderBy('name')->get()
            : collect();

        return view('kepala-sekolah.report.siswa', compact(
            'summary',
            'matchingKelasList',
            'allKelasList',
            'startDate',
            'endDate',
            'selectedKelasId',
            'selectedTingkat',
            'selectedJurusan',
            'selectedSiswaId',
            'selectedTahunAjaran',
            'selectedSemester',
            'daftarTahunAjaran',
            'daftarSemester',
            'daftarJurusan',
            'daftarTingkat',
            'filterMode',
            'alasanBreakdown',
            'grandTotalCount',
            'grandTotalPercentage',
            'pieLabels',
            'piePercentages',
            'pieCounts',
            'pieColors',
            'siswaFilterList'
        ));
    }
}
