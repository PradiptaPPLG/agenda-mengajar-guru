<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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

        // 1. Kartu Statistik Utama
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

        return view('super-admin.dashboard', compact(
            'stats',
            'progressPertemuan',
            'anomaliJadwalTerlewat',
            'kelasBelumTerpetakan',
            'latestActivities'
        ));
    }
}
