<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\JadwalPelajaran;
use App\Models\Pertemuan;
use App\Models\Setting;
use App\Services\JadwalBlokResolverService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['identifier' => 'Akun Anda sedang dinonaktifkan oleh Wali Kelas atau Admin.']);
        }

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

        // Group continuous schedules in the same session
        $groupedJadwals = JadwalPelajaran::groupContinuousSchedules($jadwals);
        $enableCheckout = (Setting::get('enable_checkout_foto', '0') == '1');

        // For each jadwal, calculate whether lesson start time has been reached
        $jadwalsWithStatus = $groupedJadwals->map(function (JadwalPelajaran $jadwal) use ($today, $user, $nowTime) {
            $subIds = $jadwal->sub_jadwal_ids ?? [$jadwal->id];

            $pertemuan = Pertemuan::with(['fotoBuktis.siswa', 'kehadiranGuru'])
                ->whereIn('jadwal_id', $subIds)
                ->whereDate('tanggal', $today)
                ->first();

            $semuaFotoBukti = $pertemuan?->fotoBuktis ?? collect();
            $fotoBuktiSaya = $semuaFotoBukti->firstWhere('siswa_id', $user->id);
            $fotoBuktiKelas = $fotoBuktiSaya ?? $semuaFotoBukti->first();

            $sudahCapture = ($fotoBuktiKelas !== null && ! empty($fotoBuktiKelas->foto_path));
            $sudahCheckout = ($fotoBuktiKelas !== null && ! empty($fotoBuktiKelas->foto_checkout_path));
            $isMyCapture = ($fotoBuktiSaya !== null);
            $reportedBy = $fotoBuktiKelas?->siswa?->name;
            $reportedAt = $fotoBuktiKelas?->created_at?->format('H:i');

            $jamMulai = $jadwal->jam_mulai_formatted ?? substr($jadwal->jam_mulai, 0, 5);
            $jamSelesai = $jadwal->jam_selesai_formatted ?? substr($jadwal->jam_selesai, 0, 5);

            $jamCheckoutMulai = Carbon::createFromFormat('H:i', $jamSelesai)->subMinutes(15)->format('H:i');
            $canCheckout = ($nowTime >= $jamCheckoutMulai);

            $isStarted = ($nowTime >= $jamMulai);
            $isActiveNow = ($nowTime >= $jamMulai && $nowTime <= $jamSelesai);

            return [
                'jadwal' => $jadwal,
                'pertemuan' => $pertemuan,
                'fotoBukti' => $fotoBuktiKelas,
                'sudahCapture' => $sudahCapture,
                'sudahCheckout' => $sudahCheckout,
                'isMyCapture' => $isMyCapture,
                'reportedBy' => $reportedBy,
                'reportedAt' => $reportedAt,
                'canCheckout' => $canCheckout,
                'jamCheckoutMulai' => $jamCheckoutMulai,
                'isStarted' => $isStarted,
                'isActiveNow' => $isActiveNow,
                'jamMulai' => $jamMulai,
                'jamSelesai' => $jamSelesai,
                'totalJp' => $jadwal->total_jp ?? 1,
                'isMultiJam' => ! empty($jadwal->is_multi_jam),
            ];
        });

        $hariLiburHariIni = HariLibur::getLibur($today);

        return view('siswa.dashboard', [
            'jadwalsWithStatus' => $jadwalsWithStatus,
            'today' => $today,
            'nowTime' => $nowTime,
            'hariLiburHariIni' => $hariLiburHariIni,
            'enableCheckout' => $enableCheckout,
        ]);
    }
}
