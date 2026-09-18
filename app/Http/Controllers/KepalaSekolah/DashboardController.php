<?php

namespace App\Http\Controllers\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuru;
use App\Models\Pertemuan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();

        $stats = [
            'guru_hadir_hari_ini' => KehadiranGuru::whereDate('created_at', $today)->where('status', 'hadir')->count(),
            'guru_alpa_hari_ini' => KehadiranGuru::whereDate('created_at', $today)->where('status', 'alpa')->count(),
            'guru_sakit_hari_ini' => KehadiranGuru::whereDate('created_at', $today)->where('status', 'sakit')->count(),
            'total_guru' => User::where('role', 'guru')->count(),
            'pertemuan_hari_ini' => Pertemuan::whereDate('tanggal', $today)->count(),
        ];

        // Recent kehadiran guru (last 7 days)
        $recentKehadiran = KehadiranGuru::with(['guru', 'pertemuan.jadwal.kelas', 'pertemuan.jadwal.mataPelajaran'])
            ->whereDate('created_at', '>=', $today->copy()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Chart Data (Last 7 Days) for Pie Chart
        $chartData = [
            'labels' => ['Hadir', 'Sakit', 'Alpa', 'Dispensasi'],
            'data' => [0, 0, 0, 0],
        ];

        $totalStats = KehadiranGuru::whereDate('created_at', '>=', $today->copy()->subDays(7))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $chartData['data'][0] = $totalStats['hadir'] ?? 0;
        $chartData['data'][1] = $totalStats['sakit'] ?? 0;
        $chartData['data'][2] = $totalStats['alpa'] ?? 0;
        $chartData['data'][3] = $totalStats['dispensasi'] ?? 0;

        return view('kepala-sekolah.dashboard', compact('stats', 'recentKehadiran', 'today', 'chartData'));
    }
}
