<?php

namespace App\Http\Controllers\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuru;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function guru(Request $request): View
    {
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $kelasList = Kelas::orderBy('nama')->get();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now()->endOfMonth();

        $selectedGuruId = $request->input('guru_id');
        $selectedKelasId = $request->input('kelas_id');

        $query = KehadiranGuru::with([
            'guru',
            'pertemuan.jadwal.kelas',
            'pertemuan.jadwal.mataPelajaran',
            'pertemuan.fotoBuktis',
        ])
            ->whereHas('pertemuan', function ($q) use ($startDate, $endDate, $selectedKelasId) {
                $q->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()]);
                if ($selectedKelasId) {
                    $q->whereHas('jadwal', fn ($j) => $j->where('kelas_id', $selectedKelasId));
                }
            });

        if ($selectedGuruId) {
            $query->where('guru_id', $selectedGuruId);
        }

        $kehadiran = $query->orderBy('created_at', 'desc')->get();

        // Summary per guru
        $summary = $kehadiran->groupBy('guru_id')->map(function ($items) {
            return [
                'guru' => $items->first()->guru,
                'hadir' => $items->where('status', 'hadir')->count(),
                'sakit' => $items->where('status', 'sakit')->count(),
                'alpa' => $items->where('status', 'alpa')->count(),
                'dispensasi' => $items->where('status', 'dispensasi')->count(),
                'total' => $items->count(),
            ];
        })->values();

        return view('kepala-sekolah.report.guru', compact(
            'kehadiran', 'summary', 'guruList', 'kelasList',
            'startDate', 'endDate', 'selectedGuruId', 'selectedKelasId'
        ));
    }

    public function siswa(Request $request): View
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
        $summary = $kehadiranSiswaData->groupBy('siswa_id')->map(function ($items) {
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

        return view('kepala-sekolah.report.siswa', compact(
            'summary', 'kelasList', 'startDate', 'endDate', 'selectedKelasId'
        ));
    }
}
