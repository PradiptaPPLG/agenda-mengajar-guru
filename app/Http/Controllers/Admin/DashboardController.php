<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

use App\Models\KehadiranGuru;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();

        $stats = [
            'total_guru' => User::where('role', 'guru')->count(),
            'total_siswa' => User::where('role', 'siswa')->count(),
            'total_kelas' => Kelas::count(),
            'pertemuan_hari_ini' => Pertemuan::whereDate('tanggal', $today)->count(),
        ];

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

        return view('admin.dashboard', compact('stats', 'chartData'));
    }
}
