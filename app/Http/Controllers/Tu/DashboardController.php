<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\Pertemuan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $kelasList = Kelas::orderBy('nama')->get();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now()->endOfMonth();

        $selectedKelasId = $request->input('kelas_id');

        // Get pertemuan in range
        $pertemuanIds = Pertemuan::whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($selectedKelasId, fn ($q) => $q->whereHas('jadwal', fn ($j) => $j->where('kelas_id', $selectedKelasId)))
            ->pluck('id');

        $kehadiranSiswaData = KehadiranSiswa::with(['siswa', 'pertemuan.jadwal.mataPelajaran'])
            ->whereIn('pertemuan_id', $pertemuanIds)
            ->get();

        // Summary per siswa
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

        return view('tu.dashboard', compact(
            'summary', 'kelasList', 'startDate', 'endDate', 'selectedKelasId'
        ));
    }
}
