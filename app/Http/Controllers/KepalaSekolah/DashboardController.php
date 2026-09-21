<?php

namespace App\Http\Controllers\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
use App\Models\Pertemuan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();
        $hari = $today->dayOfWeekIso;
        $nowTime = Carbon::now()->format('H:i');

        // Statistik agregat hari ini
        $stats = [
            'guru_hadir_hari_ini' => KehadiranGuru::whereDate('created_at', $today)->where('status', 'hadir')->count(),
            'guru_terlambat_hari_ini' => KehadiranGuru::whereDate('created_at', $today)->where('status', 'terlambat')->count(),
            'guru_tidak_hadir_hari_ini' => KehadiranGuru::whereDate('created_at', $today)->whereIn('status', ['tidak_hadir', 'sakit', 'alpa', 'dispensasi'])->count(),
            'total_guru' => User::where('role', 'guru')->count(),
            'pertemuan_hari_ini' => Pertemuan::whereDate('tanggal', $today)->count(),
        ];

        // ── Real-Time Monitoring KBM Hari Ini Berbasis Kartu & Jam Berjalan ──
        $allJadwals = JadwalPelajaran::with(['kelas', 'guru', 'mataPelajaran'])
            ->where('hari', $hari)
            ->orderBy('jam_mulai')
            ->get();

        // Identifikasi slot waktu jam pelajaran
        $timeSlots = $allJadwals->map(function ($j) {
            return [
                'jam_mulai' => substr($j->jam_mulai, 0, 5),
                'jam_selesai' => substr($j->jam_selesai, 0, 5),
                'label' => substr($j->jam_mulai, 0, 5).' - '.substr($j->jam_selesai, 0, 5),
            ];
        })->unique('label')->values();

        $timeSlots = $timeSlots->map(function ($slot, $idx) {
            $slot['jam_ke'] = $idx + 1;
            $slot['title'] = 'Jam Ke-'.($idx + 1).' ('.$slot['label'].')';

            return $slot;
        });

        // Deteksi slot jam yang aktif sekarang
        $currentActiveSlotIndex = null;
        foreach ($timeSlots as $idx => $slot) {
            if ($nowTime >= $slot['jam_mulai'] && $nowTime <= $slot['jam_selesai']) {
                $currentActiveSlotIndex = $idx;
                break;
            }
        }
        if ($currentActiveSlotIndex === null) {
            foreach ($timeSlots as $idx => $slot) {
                if ($nowTime < $slot['jam_mulai']) {
                    $currentActiveSlotIndex = $idx;
                    break;
                }
            }
        }
        if ($currentActiveSlotIndex === null && $timeSlots->isNotEmpty()) {
            $currentActiveSlotIndex = 0;
        }

        $selectedSlotKey = $request->input('slot', $currentActiveSlotIndex !== null ? (string) $currentActiveSlotIndex : 'all');
        $selectedTingkat = $request->input('tingkat', 'all');
        $selectedStatus = $request->input('status', 'all');
        $selectedAlasan = $request->input('alasan', 'all');

        $todayPertemuans = Pertemuan::with(['kehadiranGuru', 'fotoBuktis.siswa'])
            ->whereDate('tanggal', $today)
            ->whereIn('jadwal_id', $allJadwals->pluck('id'))
            ->get()
            ->keyBy('jadwal_id');

        $monitoringCards = $allJadwals->map(function ($jadwal) use ($timeSlots, $todayPertemuans) {
            $mulai = substr($jadwal->jam_mulai, 0, 5);
            $selesai = substr($jadwal->jam_selesai, 0, 5);
            $slotIdx = $timeSlots->search(fn ($s) => $s['jam_mulai'] === $mulai && $s['jam_selesai'] === $selesai);
            $jamKe = $slotIdx !== false ? $slotIdx + 1 : 1;
            $slotKey = $slotIdx !== false ? (string) $slotIdx : '0';

            $pertemuan = $todayPertemuans->get($jadwal->id);
            $kh = $pertemuan?->kehadiranGuru;

            $status = 'belum_hadir';
            $alasanKey = null;
            $alasanLabel = null;
            $guruPengganti = null;
            $waktuHadir = null;

            if ($kh) {
                if ($kh->status === 'hadir') {
                    $status = 'hadir';
                    $waktuHadir = $kh->waktu_hadir;
                } elseif ($kh->status === 'terlambat') {
                    $status = 'terlambat';
                    $waktuHadir = $kh->waktu_hadir;
                } elseif (in_array($kh->status, ['tidak_hadir', 'sakit', 'alpa', 'dispensasi'])) {
                    $status = 'tidak_hadir';
                    $alasanKey = $kh->alasan_tidak_hadir ?? (in_array($kh->status, ['sakit', 'alpa', 'dispensasi']) ? $kh->status : 'tanpa_keterangan');
                    $alasanLabel = $kh->alasan_tidak_hadir_label ?? $kh->status_label;
                    $guruPengganti = $kh->guru_pengganti_nama;
                }
            }

            return [
                'jadwal' => $jadwal,
                'pertemuan' => $pertemuan,
                'jam_ke' => $jamKe,
                'slot_index' => $slotKey,
                'slot_label' => $mulai.' - '.$selesai,
                'tingkat' => $jadwal->kelas->tingkat ?? '',
                'kelas_nama' => $jadwal->kelas->nama ?? '',
                'guru_nama' => $jadwal->guru->name ?? '',
                'mapel_nama' => $jadwal->mataPelajaran->nama ?? '',
                'status' => $status,
                'alasan_key' => $alasanKey,
                'alasan_label' => $alasanLabel,
                'guru_pengganti' => $guruPengganti,
                'waktu_hadir' => $waktuHadir,
            ];
        });

        // Filter slot waktu
        if ($selectedSlotKey !== 'all') {
            $monitoringCards = $monitoringCards->where('slot_index', $selectedSlotKey);
        }

        // Filter tingkat kelas
        if ($selectedTingkat !== 'all') {
            $monitoringCards = $monitoringCards->filter(function ($item) use ($selectedTingkat) {
                $t = strtoupper($item['tingkat']);
                $k = strtoupper($item['kelas_nama']);
                if ($selectedTingkat === '10' || $selectedTingkat === 'X') {
                    return $t === 'X' || $t === '10' || str_starts_with($k, '10') || str_starts_with($k, 'X');
                }
                if ($selectedTingkat === '11' || $selectedTingkat === 'XI') {
                    return $t === 'XI' || $t === '11' || str_starts_with($k, '11') || str_starts_with($k, 'XI');
                }
                if ($selectedTingkat === '12' || $selectedTingkat === 'XII') {
                    return $t === 'XII' || $t === '12' || str_starts_with($k, '12') || str_starts_with($k, 'XII');
                }

                return true;
            });
        }

        // Filter status utama
        if ($selectedStatus !== 'all') {
            $monitoringCards = $monitoringCards->where('status', $selectedStatus);
        }

        // Filter sub-status alasan tidak hadir
        if ($selectedAlasan !== 'all') {
            $monitoringCards = $monitoringCards->where('alasan_key', $selectedAlasan);
        }

        // Recent kehadiran guru (last 7 days)
        $recentKehadiran = KehadiranGuru::with(['guru', 'pertemuan.jadwal.kelas', 'pertemuan.jadwal.mataPelajaran'])
            ->whereDate('created_at', '>=', $today->copy()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('kepala-sekolah.dashboard', compact(
            'stats',
            'recentKehadiran',
            'today',
            'nowTime',
            'timeSlots',
            'currentActiveSlotIndex',
            'selectedSlotKey',
            'selectedTingkat',
            'selectedStatus',
            'selectedAlasan',
            'monitoringCards'
        ));
    }
}
