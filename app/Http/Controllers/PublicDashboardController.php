<?php

namespace App\Http\Controllers;

use App\Models\KehadiranGuru;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\KelasPkl;
use App\Models\MasterJamPelajaran;
use App\Models\Pertemuan;
use App\Models\User;
use App\Services\JadwalBlokResolverService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();
        $hari = $today->dayOfWeekIso;
        $nowTime = Carbon::now()->format('H:i');

        // ── Guru Stats Hari Ini (dihitung per ORANG/unik, bukan per sesi) ────────
        // Guru "hadir"       = punya minimal 1 sesi berstatus 'hadir' hari ini
        // Guru "terlambat"   = tidak ada sesi 'hadir', tapi ada sesi 'terlambat'
        // Guru "tidak hadir" = semua sesi hari ini non-hadir (tidak ada hadir/terlambat sama sekali)
        $guruTodayQuery = fn () => KehadiranGuru::whereHas('pertemuan', fn ($q) => $q->whereDate('tanggal', $today));

        // Guru IDs yang hadir di minimal 1 sesi
        $guruHadirIds = $guruTodayQuery()->where('status', 'hadir')->distinct()->pluck('guru_id');
        // Guru IDs yang terlambat di minimal 1 sesi (tapi tidak ada yang hadir)
        $guruTerlambatIds = $guruTodayQuery()->where('status', 'terlambat')
            ->whereNotIn('guru_id', $guruHadirIds)
            ->distinct()->pluck('guru_id');
        // Guru IDs yang SAMA SEKALI tidak hadir/terlambat hari ini
        $guruTidakHadirIds = $guruTodayQuery()
            ->whereNotIn('status', ['hadir', 'terlambat'])
            ->whereNotIn('guru_id', $guruHadirIds)
            ->whereNotIn('guru_id', $guruTerlambatIds)
            ->distinct()->pluck('guru_id');

        // ── Siswa Stats Hari Ini (dihitung per ORANG/unik, bukan per sesi) ────────
        // Siswa "hadir"       = punya minimal 1 sesi 'hadir' hari ini
        // Siswa "terlambat"   = tidak ada sesi 'hadir', tapi ada sesi 'terlambat'
        // Siswa "tidak hadir" = siswa yang SAMA SEKALI tidak hadir/terlambat di semua sesi hari ini
        $siswaTodayQuery = fn () => KehadiranSiswa::whereHas('pertemuan', fn ($q) => $q->whereDate('tanggal', $today));

        $siswaHadirIds = $siswaTodayQuery()->where('status', 'hadir')->distinct()->pluck('siswa_id');
        $siswaTerlambatIds = $siswaTodayQuery()->where('status', 'terlambat')
            ->whereNotIn('siswa_id', $siswaHadirIds)
            ->distinct()->pluck('siswa_id');
        $siswaTidakHadirIds = $siswaTodayQuery()
            ->whereNotIn('status', ['hadir', 'terlambat'])
            ->whereNotIn('siswa_id', $siswaHadirIds)
            ->whereNotIn('siswa_id', $siswaTerlambatIds)
            ->distinct()->pluck('siswa_id');

        $stats = [
            // Guru (per kepala/orang)
            'guru_hadir_hari_ini' => $guruHadirIds->count(),
            'guru_terlambat_hari_ini' => $guruTerlambatIds->count(),
            'guru_tidak_hadir_hari_ini' => $guruTidakHadirIds->count(),
            'total_guru' => User::where('role', 'guru')->count(),
            'pertemuan_hari_ini' => Pertemuan::whereDate('tanggal', $today)->count(),

            // Siswa (per kepala/orang)
            'siswa_hadir_hari_ini' => $siswaHadirIds->count(),
            'siswa_terlambat_hari_ini' => $siswaTerlambatIds->count(),
            'siswa_tidak_hadir_hari_ini' => $siswaTidakHadirIds->count(),
            'total_siswa' => User::where('role', 'siswa')->count(),
        ];

        // Real-Time Monitoring KBM Hari Ini (menggunakan resolver sistem blok)
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

        // Cari slot-slot yang memiliki jadwal aktif hari ini (overlap check)
        $availableSlotIndices = $allJadwals->map(function ($j) use ($timeSlots) {
            $m = substr($j->jam_mulai, 0, 5);
            $s = substr($j->jam_selesai, 0, 5);

            $idx = $timeSlots->search(fn ($slot) => $slot['jam_mulai'] === $m && $slot['jam_selesai'] === $s);
            if ($idx === false) {
                $idx = $timeSlots->search(fn ($slot) => $m < $slot['jam_selesai'] && $s > $slot['jam_mulai']);
            }

            return $idx;
        })->filter(fn ($idx) => $idx !== false)->unique()->values();

        // Deteksi slot jam aktif sekarang berdasarkan rentang jam pelajaran
        $currentActiveSlotIndex = null;
        foreach ($timeSlots as $idx => $slot) {
            if ($nowTime >= $slot['jam_mulai'] && $nowTime <= $slot['jam_selesai']) {
                $currentActiveSlotIndex = $idx;
                break;
            }
        }

        // Jika sekarang di jeda/istirahat atau di luar jam KBM
        if ($currentActiveSlotIndex === null) {
            // Cari slot yang jam mulainya paling dekat berikutnya
            $nextSlot = $timeSlots->search(fn ($s) => $s['jam_mulai'] >= $nowTime);
            if ($nextSlot !== false) {
                $currentActiveSlotIndex = $nextSlot;
            } else {
                // Jika sudah melewati semua jam sekolah (sore/malam), pakai slot terakhir hari ini
                $currentActiveSlotIndex = $availableSlotIndices->last() ?? ($timeSlots->keys()->last() ?? 0);
            }
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

            if ($slotIdx === false) {
                // Fallback pencarian berbasis rentang waktu (overlap) jika durasi membentang
                $slotIdx = $timeSlots->search(fn ($s) => $mulai < $s['jam_selesai'] && $selesai > $s['jam_mulai']);
            }

            $jamKe = $slotIdx !== false ? $timeSlots[$slotIdx]['jam_ke'] : 1;
            $slotKey = $slotIdx !== false ? (string) $slotIdx : 'unassigned';

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

        // Filter
        if ($selectedSlotKey !== 'all') {
            $monitoringCards = $monitoringCards->where('slot_index', $selectedSlotKey);
        }
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
        if ($selectedStatus !== 'all') {
            $monitoringCards = $monitoringCards->where('status', $selectedStatus);
        }
        if ($selectedAlasan !== 'all') {
            $monitoringCards = $monitoringCards->where('alasan_key', $selectedAlasan);
        }

        // 7-day Pie charts
        $chartHadir = [];
        $chartTerlambat = [];
        $chartTidakHadir = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $dayQuery = KehadiranGuru::whereHas('pertemuan', fn ($q) => $q->whereDate('tanggal', $date));
            $chartHadir[] = (clone $dayQuery)->where('status', 'hadir')->count();
            $chartTerlambat[] = (clone $dayQuery)->where('status', 'terlambat')->count();
            $chartTidakHadir[] = (clone $dayQuery)->whereIn('status', ['tidak_hadir', 'sakit', 'alpa', 'dispensasi'])->count();
        }
        $guruPie = [
            'hadir' => array_sum($chartHadir),
            'terlambat' => array_sum($chartTerlambat),
            'tidak_hadir' => array_sum($chartTidakHadir),
        ];
        $guruPieTotal = array_sum($guruPie) ?: 1;
        $guruPiePct = [
            'hadir' => round($guruPie['hadir'] / $guruPieTotal * 100, 1),
            'terlambat' => round($guruPie['terlambat'] / $guruPieTotal * 100, 1),
            'tidak_hadir' => round($guruPie['tidak_hadir'] / $guruPieTotal * 100, 1),
        ];

        $siswaQuery7 = KehadiranSiswa::whereHas('pertemuan', fn ($q) => $q->whereDate('tanggal', '>=', $today->copy()->subDays(7)->toDateString()));
        $siswaPie = [
            'hadir' => (clone $siswaQuery7)->where('status', 'hadir')->count(),
            'terlambat' => (clone $siswaQuery7)->where('status', 'terlambat')->count(),
            'sakit' => (clone $siswaQuery7)->where('status', 'sakit')->count(),
            'izin' => (clone $siswaQuery7)->where('status', 'izin')->count(),
            'dispensasi' => (clone $siswaQuery7)->where('status', 'dispensasi')->count(),
            'alpa' => (clone $siswaQuery7)->where('status', 'alpa')->count(),
        ];
        $siswaPieTotal = array_sum($siswaPie) ?: 1;
        $siswaPiePct = [
            'hadir' => round($siswaPie['hadir'] / $siswaPieTotal * 100, 1),
            'terlambat' => round($siswaPie['terlambat'] / $siswaPieTotal * 100, 1),
            'sakit' => round($siswaPie['sakit'] / $siswaPieTotal * 100, 1),
            'izin' => round($siswaPie['izin'] / $siswaPieTotal * 100, 1),
            'dispensasi' => round($siswaPie['dispensasi'] / $siswaPieTotal * 100, 1),
            'alpa' => round($siswaPie['alpa'] / $siswaPieTotal * 100, 1),
        ];

        $kelasPklHariIni = KelasPkl::with('kelas')->sedangBerlangsung($today)->get();

        return view('public-dashboard', compact(
            'stats',
            'guruPie',
            'guruPiePct',
            'siswaPie',
            'siswaPiePct',
            'today',
            'nowTime',
            'timeSlots',
            'currentActiveSlotIndex',
            'selectedSlotKey',
            'selectedTingkat',
            'selectedStatus',
            'selectedAlasan',
            'monitoringCards',
            'kelasPklHariIni'
        ));
    }
}
