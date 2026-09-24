<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\JadwalPelajaran;
use App\Models\Pertemuan;
use App\Services\JadwalBlokResolverService;
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
        $kelas = $user->siswaProfile?->kelas;

        // Get today's active jadwal for student's class (respecting sistem blok)
        $jadwals = $kelas
            ? app(JadwalBlokResolverService::class)->resolveJadwal($kelas, $today, (string) $hariAngka)
            : collect();

        // Jika kelas split_harian dan siswa memiliki kelompok blok (A atau B), filter hanya kelompoknya + reguler
        if ($kelas && $kelas->is_sistem_blok && $kelas->model_rotasi === 'split_harian' && $user->siswaProfile?->kelompok_blok) {
            $kelompokSiswa = $user->siswaProfile->kelompok_blok;
            $jadwals = $jadwals->filter(function (JadwalPelajaran $j) use ($kelompokSiswa) {
                return $j->kelompok_blok === 'reguler' || $j->kelompok_blok === $kelompokSiswa || empty($j->kelompok_blok);
            });
        }

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
