<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
        $now = Carbon::now();
        $user = auth()->user();
        $isSuperAdmin = $user && method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : ($user?->role === 'super_admin');

        $mingguAktif = KalenderBlokMinggu::aktif($today)->first();
        $kelasSistemBlok = Kelas::where('is_sistem_blok', true)->orderBy('nama')->get();

        // 1. Stats Utama
        $stats = [
            'total_guru' => User::where('role', 'guru')->count(),
            'total_siswa' => User::where('role', 'siswa')->count(),
            'total_kelas' => Kelas::count(),
            'total_admin' => User::where('role', 'admin')->count(),
            'pertemuan_hari_ini' => Pertemuan::whereDate('tanggal', $today)->count(),
        ];

        // 2. Live Progress Pertemuan Hari Ini
        $pertemuanQuery = Pertemuan::with(['jadwal.guru', 'jadwal.kelas', 'jadwal.mataPelajaran'])
            ->whereDate('tanggal', $today);

        $totalPertemuan = (clone $pertemuanQuery)->count();
        $pertemuanSelesai = (clone $pertemuanQuery)->where('status', 'selesai')->count();
        $pertemuanBerlangsung = (clone $pertemuanQuery)->where('status', 'berlangsung')->count();
        $pertemuanMenunggu = (clone $pertemuanQuery)->where(function ($q) {
            $q->whereNull('status')->orWhere('status', 'menunggu');
        })->count();

        $progressPertemuan = [
            'total' => $totalPertemuan,
            'selesai' => $pertemuanSelesai,
            'berlangsung' => $pertemuanBerlangsung,
            'menunggu' => $pertemuanMenunggu,
            'pct_selesai' => $totalPertemuan > 0 ? round(($pertemuanSelesai / $totalPertemuan) * 100, 1) : 0,
            'pct_berlangsung' => $totalPertemuan > 0 ? round(($pertemuanBerlangsung / $totalPertemuan) * 100, 1) : 0,
            'pct_menunggu' => $totalPertemuan > 0 ? round(($pertemuanMenunggu / $totalPertemuan) * 100, 1) : 0,
        ];

        // 3. Deteksi Anomali & Peringatan:
        // A. Guru belum mengisi agenda pada jadwal yang jamnya sudah lewat hari ini
        $anomaliJadwalTerlewat = (clone $pertemuanQuery)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'menunggu');
            })
            ->whereHas('jadwal', function ($q) use ($now) {
                $q->where('jam_selesai', '<=', $now->format('H:i:s'));
            })
            ->take(5)
            ->get();

        // B. Kelas blok yang belum terpetakan blok awal
        $kelasBelumTerpetakan = Kelas::where('is_sistem_blok', true)
            ->where(function ($q) {
                $q->whereNull('blok_awal')->orWhere('blok_awal', '');
            })
            ->take(5)
            ->get();

        // 4. Log Aktivitas Terbaru (5 terakhir)
        $latestActivities = ActivityLog::with('user')
            ->latest('created_at')
            ->take(5)
            ->get();

        // 5. Chart Data (Last 7 Days) for Pie Chart Guru
        $chartData = [
            'labels' => ['Hadir', 'Sakit', 'Alpa', 'Dispensasi'],
            'data' => [0, 0, 0, 0],
        ];

        $totalStats = KehadiranGuru::whereDate('created_at', '>=', $today->copy()->subDays(7))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $chartData['data'][0] = ($totalStats['hadir'] ?? 0) + ($totalStats['terlambat'] ?? 0);
        $chartData['data'][1] = $totalStats['sakit'] ?? 0;
        $chartData['data'][2] = $totalStats['alpa'] ?? 0;
        $chartData['data'][3] = $totalStats['dispensasi'] ?? 0;

        // 6. Chart Data (Last 7 Days) for Siswa
        $chartDataSiswa = [
            'labels' => ['Hadir', 'Sakit', 'Izin', 'Alpa', 'Dispensasi'],
            'data' => [0, 0, 0, 0, 0],
        ];

        $totalStatsSiswa = KehadiranSiswa::whereDate('created_at', '>=', $today->copy()->subDays(7))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $chartDataSiswa['data'][0] = ($totalStatsSiswa['hadir'] ?? 0) + ($totalStatsSiswa['terlambat'] ?? 0);
        $chartDataSiswa['data'][1] = $totalStatsSiswa['sakit'] ?? 0;
        $chartDataSiswa['data'][2] = $totalStatsSiswa['izin'] ?? 0;
        $chartDataSiswa['data'][3] = $totalStatsSiswa['alpa'] ?? 0;
        $chartDataSiswa['data'][4] = $totalStatsSiswa['dispensasi'] ?? 0;

        // 7. Leaderboard Top Guru (kecualikan jika ada catatan presensi saat kelas PKL)
        $topGurus = KehadiranGuru::with('guru')
            ->whereDoesntHave('pertemuan.jadwal.kelas.pkls', function ($q) {
                $q->where('is_aktif', true)
                    ->whereColumn('kelas_pkls.tanggal_mulai', '<=', 'pertemuans.tanggal')
                    ->whereColumn('kelas_pkls.tanggal_selesai', '>=', 'pertemuans.tanggal');
            })
            ->selectRaw("guru_id, 
                COUNT(*) as total_sesi, 
                SUM(CASE WHEN status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) as total_hadir,
                (SUM(CASE WHEN status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as persentase")
            ->groupBy('guru_id')
            ->having('total_sesi', '>', 0)
            ->orderByDesc('persentase')
            ->orderByDesc('total_hadir')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'progressPertemuan',
            'anomaliJadwalTerlewat',
            'kelasBelumTerpetakan',
            'latestActivities',
            'chartData',
            'chartDataSiswa',
            'topGurus',
            'mingguAktif',
            'kelasSistemBlok',
            'isSuperAdmin'
        ));
    }
}
