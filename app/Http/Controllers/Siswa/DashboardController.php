<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
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
        $nowTime = Carbon::now()->format('H:i');

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

        // For each jadwal, calculate whether lesson start time has been reached
        $jadwalsWithStatus = $jadwals->map(function (JadwalPelajaran $jadwal) use ($today, $user, $nowTime) {
            $pertemuan = Pertemuan::with(['fotoBuktis' => function ($q) use ($user) {
                $q->where('siswa_id', $user->id);
            }])->where('jadwal_id', $jadwal->id)
                ->whereDate('tanggal', $today)
                ->first();

            $jamMulai = substr($jadwal->jam_mulai, 0, 5);
            $jamSelesai = substr($jadwal->jam_selesai, 0, 5);

            $isStarted = ($nowTime >= $jamMulai);
            $isActiveNow = ($nowTime >= $jamMulai && $nowTime <= $jamSelesai);

            return [
                'jadwal' => $jadwal,
                'pertemuan' => $pertemuan,
                'sudahCapture' => $pertemuan?->fotoBuktis->isNotEmpty() ?? false,
                'isStarted' => $isStarted,
                'isActiveNow' => $isActiveNow,
                'jamMulai' => $jamMulai,
                'jamSelesai' => $jamSelesai,
            ];
        });

        $hariLiburHariIni = HariLibur::getLibur($today);

        return view('siswa.dashboard', [
            'jadwalsWithStatus' => $jadwalsWithStatus,
            'today' => $today,
            'nowTime' => $nowTime,
            'hariLiburHariIni' => $hariLiburHariIni,
        ]);
    }
}
