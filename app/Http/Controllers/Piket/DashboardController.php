<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\KalenderBlokMinggu;
use App\Models\Kelas;
use App\Models\MasterJamPelajaran;
use App\Models\Pertemuan;
use App\Services\JadwalBlokResolverService;
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
        $kelasList = Kelas::orderBy('nama')->get();
        $allJadwals = app(JadwalBlokResolverService::class)
            ->resolveJadwalBanyakKelas($kelasList, $today, (string) $hari)
            ->load(['kelas', 'guru', 'mataPelajaran'])
            ->sortBy(['jam_mulai', 'kelas.nama']);

        // Ambil standar jam pelajaran dari master
        $masterJam = MasterJamPelajaran::orderBy('jam_ke')->get();

        $timeSlots = $masterJam->map(function ($jam) {
            $mulai = substr($jam->jam_mulai, 0, 5);
            $selesai = substr($jam->jam_selesai, 0, 5);

            return [
                'jam_ke' => $jam->jam_ke,
                'jam_mulai' => $mulai,
                'jam_selesai' => $selesai,
                'label' => $mulai.' - '.$selesai,
                'title' => 'Jam Ke-'.$jam->jam_ke.' ('.$mulai.' - '.$selesai.')',
            ];
        });

        // Cari slot-slot yang memiliki jadwal aktif hari ini
        $availableSlotIndices = $allJadwals->map(function ($j) use ($timeSlots) {
            $m = substr($j->jam_mulai, 0, 5);
            $s = substr($j->jam_selesai, 0, 5);

            return $timeSlots->search(fn ($slot) => $slot['jam_mulai'] === $m && $slot['jam_selesai'] === $s);
        })->filter(fn ($idx) => $idx !== false)->unique()->values();

        // Deteksi slot jam yang sedang berjalan sekarang
        $currentActiveSlotIndex = null;
        foreach ($timeSlots as $idx => $slot) {
            if ($nowTime >= $slot['jam_mulai'] && $nowTime <= $slot['jam_selesai']) {
                $currentActiveSlotIndex = $idx;
                break;
            }
        }

        // Jika sekarang di jeda/istirahat atau sebelum jam mulai, cari slot berikutnya yang ada jadwal
        if ($currentActiveSlotIndex === null || ! $availableSlotIndices->contains($currentActiveSlotIndex)) {
            $currentActiveSlotIndex = $availableSlotIndices->first(fn ($idx) => $timeSlots[$idx]['jam_mulai'] >= $nowTime)
                ?? $availableSlotIndices->first()
                ?? 0;
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

            if ($slotIdx === false) {
                // Fallback pencarian berbasis jam mulai jika durasi membentang
                $slotIdx = $timeSlots->search(fn ($s) => $mulai >= $s['jam_mulai'] && $mulai < $s['jam_selesai']);
            }

            $jamKe = $slotIdx !== false ? $timeSlots[$slotIdx]['jam_ke'] : 1;
            $slotKey = $slotIdx !== false ? (string) $slotIdx : 'unassigned';

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
                'jadwal_id' => $jadwal->id,
                'guru_id' => $jadwal->guru_id,
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

        // Ambil data blok aktif untuk banner sistem blok
        $mingguAktif = KalenderBlokMinggu::aktif($today)->first();
        $kelasSistemBlok = Kelas::where('is_sistem_blok', true)->orderBy('nama')->get();

        return view('piket.dashboard', compact(
            'filteredItems',
            'timeSlots',
            'currentActiveSlotIndex',
            'selectedSlotKey',
            'selectedTingkat',
            'selectedStatus',
            'stats',
            'today',
            'nowTime',
            'mingguAktif',
            'kelasSistemBlok'
        ));
    }
}
