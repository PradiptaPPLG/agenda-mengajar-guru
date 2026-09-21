<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KalenderBlokMinggu;
use App\Models\KehadiranGuru;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();
        $mingguAktif = KalenderBlokMinggu::aktif($today)->first();
        $kelasSistemBlok = Kelas::where('is_sistem_blok', true)->orderBy('nama')->get();

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

        // Chart Data (Last 7 Days) for Siswa
        $chartDataSiswa = [
            'labels' => ['Hadir', 'Sakit', 'Izin', 'Alpa', 'Dispensasi'],
            'data' => [0, 0, 0, 0, 0],
        ];

        $totalStatsSiswa = KehadiranSiswa::whereDate('created_at', '>=', $today->copy()->subDays(7))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $chartDataSiswa['data'][0] = $totalStatsSiswa['hadir'] ?? 0;
        $chartDataSiswa['data'][1] = $totalStatsSiswa['sakit'] ?? 0;
        $chartDataSiswa['data'][2] = $totalStatsSiswa['izin'] ?? 0;
        $chartDataSiswa['data'][3] = $totalStatsSiswa['alpa'] ?? 0;
        $chartDataSiswa['data'][4] = $totalStatsSiswa['dispensasi'] ?? 0;

        // Leaderboard Top Guru
        $topGurus = KehadiranGuru::with('guru')
            ->selectRaw('guru_id, 
                COUNT(*) as total_sesi, 
                SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as total_hadir,
                (SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as persentase')
            ->groupBy('guru_id')
            ->having('total_sesi', '>', 0)
            ->orderByDesc('persentase')
            ->orderByDesc('total_hadir')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'chartData', 'chartDataSiswa', 'topGurus', 'mingguAktif', 'kelasSistemBlok'));
    }
}
