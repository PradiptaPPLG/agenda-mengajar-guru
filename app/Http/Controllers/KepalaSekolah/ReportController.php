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

        $selectedGuruId = $request->input('guru_id');
        $selectedKelasId = $request->input('kelas_id');
        $onlyDiscrepancy = $request->boolean('only_discrepancy');

        $query = KehadiranGuru::with([
            'guru',
            'pertemuan.jadwal.kelas',
            'pertemuan.jadwal.mataPelajaran',
            'pertemuan.fotoBuktis',
        ])
            ->whereHas('pertemuan', function ($q) use ($startDate, $endDate, $selectedKelasId, $selectedTahunAjaran, $selectedSemester, $filterMode) {
                $q->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()]);

                if ($filterMode === 'semester') {
                    if ($selectedTahunAjaran !== 'all') {
                        $q->where(fn ($sub) => $sub->where('tahun_ajaran', $selectedTahunAjaran)->orWhereNull('tahun_ajaran'));
                    }
                    if ($selectedSemester !== 'all') {
                        $q->where(fn ($sub) => $sub->where('semester', $selectedSemester)->orWhereNull('semester'));
                    }
                }

                if ($selectedKelasId) {
                    $q->whereHas('jadwal', fn ($j) => $j->where('kelas_id', $selectedKelasId));
                }
            });

        if ($selectedGuruId) {
            $query->where('guru_id', $selectedGuruId);
        }

        $allKehadiran = $query->orderBy('created_at', 'desc')->get();
        $discrepancyCount = $allKehadiran->filter(fn ($kh) => $kh->has_discrepancy)->count();

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
                'hadir' => $items->where('status', 'hadir')->count(),
                'sakit' => $items->where('status', 'sakit')->count(),
                'alpa' => $items->where('status', 'alpa')->count(),
                'dispensasi' => $items->where('status', 'dispensasi')->count(),
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
            'kehadiran', 'summary', 'guruList', 'kelasList',
            'startDate', 'endDate', 'selectedGuruId', 'selectedKelasId',
            'onlyDiscrepancy', 'discrepancyCount',
            'selectedTahunAjaran', 'selectedSemester', 'daftarTahunAjaran', 'daftarSemester', 'filterMode'
        ));
    }

    public function siswa(Request $request): View
    {
        $kelasList = Kelas::orderBy('nama')->get();

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

        $selectedKelasId = $request->input('kelas_id');

        // Get pertemuan in range
        $pertemuanQuery = Pertemuan::whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($filterMode === 'semester') {
            if ($selectedTahunAjaran !== 'all') {
                $pertemuanQuery->where(fn ($sub) => $sub->where('tahun_ajaran', $selectedTahunAjaran)->orWhereNull('tahun_ajaran'));
            }
            if ($selectedSemester !== 'all') {
                $pertemuanQuery->where(fn ($sub) => $sub->where('semester', $selectedSemester)->orWhereNull('semester'));
            }
        }

        if ($selectedKelasId) {
            $pertemuanQuery->whereHas('jadwal', fn ($j) => $j->where('kelas_id', $selectedKelasId));
        }

        $pertemuanIds = $pertemuanQuery->pluck('id');

        $kehadiranSiswaData = KehadiranSiswa::with(['siswa', 'pertemuan.jadwal.mataPelajaran'])
            ->whereIn('pertemuan_id', $pertemuanIds)
            ->get();

        // Summary per siswa (10 per page)
        $summaryCollection = $kehadiranSiswaData->groupBy('siswa_id')->map(function ($items) {
            return [
                'siswa' => $items->first()->siswa,
                'hadir' => $items->where('status', 'hadir')->count(),
                'sakit' => $items->where('status', 'sakit')->count(),
                'izin' => $items->where('status', 'izin')->count(),
                'alpa' => $items->where('status', 'alpa')->count(),
                'dispensasi' => $items->where('status', 'dispensasi')->count(),
                'total' => $items->count(),
            ];
        })->values();

        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $perPage = 10;
        $summary = new LengthAwarePaginator(
            $summaryCollection->slice(($page - 1) * $perPage, $perPage)->values(),
            $summaryCollection->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page', 'query' => $request->query()]
        );

        $daftarTahunAjaran = Setting::getDaftarTahunAjaran();
        $daftarSemester = Setting::getDaftarSemester();

        return view('kepala-sekolah.report.siswa', compact(
            'summary', 'kelasList', 'startDate', 'endDate', 'selectedKelasId',
            'selectedTahunAjaran', 'selectedSemester', 'daftarTahunAjaran', 'daftarSemester', 'filterMode'
        ));
    }
}
