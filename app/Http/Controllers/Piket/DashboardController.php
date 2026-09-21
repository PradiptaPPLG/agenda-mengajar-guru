<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\Pertemuan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();
        // 1=Senin .. 7=Minggu. (SMK 1-6)
        $hari = $today->dayOfWeekIso;
        $nowTime = Carbon::now()->format('H:i');

        // Ambil semua jadwal pelajaran hari ini untuk seluruh tingkatan (10, 11, 12 / X, XI, XII)
        $allJadwals = JadwalPelajaran::with(['kelas', 'guru', 'mataPelajaran'])
            ->where('hari', $hari)
            ->orderBy('jam_mulai')
            ->get();

        // Cari slot waktu unik hari ini dan beri nomor jam ke-1, ke-2, dst.
        $timeSlots = $allJadwals->map(function ($j) {
            return [
                'jam_mulai' => substr($j->jam_mulai, 0, 5),
                'jam_selesai' => substr($j->jam_selesai, 0, 5),
                'label' => substr($j->jam_mulai, 0, 5).' - '.substr($j->jam_selesai, 0, 5),
            ];
        })->unique('label')->values();

        // Tambahkan label Jam ke-N
        $timeSlots = $timeSlots->map(function ($slot, $idx) {
            $slot['jam_ke'] = $idx + 1;
            $slot['title'] = 'Jam Ke-'.($idx + 1).' ('.$slot['label'].')';

            return $slot;
        });

        // Deteksi slot jam yang sedang berjalan sekarang
        $currentActiveSlotIndex = null;
        foreach ($timeSlots as $idx => $slot) {
            if ($nowTime >= $slot['jam_mulai'] && $nowTime <= $slot['jam_selesai']) {
                $currentActiveSlotIndex = $idx;
                break;
            }
        }

        // Jika tidak pas di jam pelajaran, cari yang terdekat atau default ke slot pertama
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

        // Ambil pertemuan hari ini yang terkait jadwal
        $todayPertemuans = Pertemuan::with(['kehadiranGuru', 'fotoBuktis.siswa'])
            ->whereDate('tanggal', $today)
            ->whereIn('jadwal_id', $allJadwals->pluck('id'))
            ->get()
            ->keyBy('jadwal_id');

        // Petakan tiap jadwal dengan jam_ke dan status kehadiran guru real-time
        $jadwalItems = $allJadwals->map(function ($jadwal) use ($timeSlots, $todayPertemuans) {
            $mulai = substr($jadwal->jam_mulai, 0, 5);
            $selesai = substr($jadwal->jam_selesai, 0, 5);
            $slotIdx = $timeSlots->search(fn ($s) => $s['jam_mulai'] === $mulai && $s['jam_selesai'] === $selesai);
            $jamKe = $slotIdx !== false ? $slotIdx + 1 : 1;
            $slotKey = $slotIdx !== false ? (string) $slotIdx : '0';

            $pertemuan = $todayPertemuans->get($jadwal->id);
            $kh = $pertemuan?->kehadiranGuru;

            // Status guru: 'hadir', 'terlambat', 'tidak_hadir', 'belum_hadir'
            $status = 'belum_hadir';
            $alasan = null;
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
                    $alasan = $kh->alasan_tidak_hadir_label ?? $kh->status_label;
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
                'alasan' => $alasan,
                'guru_pengganti' => $guruPengganti,
                'waktu_hadir' => $waktuHadir,
            ];
        });

        // Filter berdasarkan slot waktu yang dipilih
        $filteredItems = $jadwalItems;
        if ($selectedSlotKey !== 'all') {
            $filteredItems = $filteredItems->where('slot_index', $selectedSlotKey);
        }

        // Filter berdasarkan tingkat (10/X, 11/XI, 12/XII)
        if ($selectedTingkat !== 'all') {
            $filteredItems = $filteredItems->filter(function ($item) use ($selectedTingkat) {
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

        // Filter berdasarkan status kehadiran
        if ($selectedStatus !== 'all') {
            $filteredItems = $filteredItems->where('status', $selectedStatus);
        }

        // Hitung statistik untuk slot & filter aktif
        $stats = [
            'total' => $filteredItems->count(),
            'hadir' => $filteredItems->where('status', 'hadir')->count(),
            'terlambat' => $filteredItems->where('status', 'terlambat')->count(),
            'tidak_hadir' => $filteredItems->where('status', 'tidak_hadir')->count(),
            'belum_hadir' => $filteredItems->where('status', 'belum_hadir')->count(),
        ];

        return view('piket.dashboard', compact(
            'filteredItems',
            'timeSlots',
            'currentActiveSlotIndex',
            'selectedSlotKey',
            'selectedTingkat',
            'selectedStatus',
            'stats',
            'today',
            'nowTime'
        ));
    }
}
