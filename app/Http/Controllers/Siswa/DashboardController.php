<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\Pertemuan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $today = Carbon::today();
        $hariAngka = (int) $today->format('N'); // 1=Mon, 6=Sat

        // Get student's class
        $kelasId = $user->siswaProfile?->kelas_id;

        // Get today's jadwal for student's class
        $jadwals = $kelasId
            ? JadwalPelajaran::with(['guru', 'mataPelajaran'])
                ->where('kelas_id', $kelasId)
                ->where('hari', $hariAngka)
                ->orderBy('jam_mulai')
                ->get()
            : collect();

        // For each jadwal, find existing pertemuan and student's capture
        $jadwalsWithStatus = $jadwals->map(function (JadwalPelajaran $jadwal) use ($today, $user) {
            $pertemuan = Pertemuan::with(['fotoBuktis' => function ($q) use ($user) {
                $q->where('siswa_id', $user->id);
            }])->where('jadwal_id', $jadwal->id)
                ->whereDate('tanggal', $today)
                ->first();

            return [
                'jadwal' => $jadwal,
                'pertemuan' => $pertemuan,
                'sudahCapture' => $pertemuan?->fotoBuktis->isNotEmpty() ?? false,
            ];
        });

        return view('siswa.dashboard', [
            'jadwalsWithStatus' => $jadwalsWithStatus,
            'today' => $today,
        ]);
    }
}
