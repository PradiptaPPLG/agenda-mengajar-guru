<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\FotoBukti;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
use App\Models\Pertemuan;
use App\Models\Setting;
use App\Services\ImageCompressor;
use App\Services\JadwalBlokResolverService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CaptureController extends Controller
{
    public function show(int $jadwalId, string $tanggal): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['identifier' => 'Akun Anda sedang dinonaktifkan oleh Wali Kelas atau Admin.']);
        }

        // Check student is in this class
        $kelas = $user->siswaProfile?->kelas;

        $jadwal = JadwalPelajaran::findOrFail($jadwalId);
        abort_unless($jadwal->kelas_id === $kelas?->id, 403);

        $tanggalCarbon = Carbon::parse($tanggal);

        // Check if schedule is active for this class on this date in block system
        if ($kelas && $kelas->is_sistem_blok) {
            $activeJadwals = app(JadwalBlokResolverService::class)->resolveJadwal($kelas, $tanggalCarbon, (string) $jadwal->hari);
            if (! $activeJadwals->pluck('id')->contains($jadwal->id)) {
                return redirect()->route('siswa.dashboard')->with('error', 'Mata pelajaran ini tidak aktif untuk kelas Anda pada tanggal tersebut.');
            }

            if ($kelas->model_rotasi === 'split_harian' && $user->siswaProfile?->kelompok_blok) {
                $kelompokSiswa = $user->siswaProfile->kelompok_blok;
                if ($jadwal->kelompok_blok && $jadwal->kelompok_blok !== 'reguler' && $jadwal->kelompok_blok !== $kelompokSiswa) {
                    return redirect()->route('siswa.dashboard')->with('error', 'Mata pelajaran ini bukan untuk kelompok Anda.');
                }
            }
        }

        $consecutiveSchedules = $jadwal->getConsecutiveSchedules();
        $jadwalUtama = $consecutiveSchedules->first();
        $jadwalAkhir = $consecutiveSchedules->last();
        $jamMulai = substr($jadwalUtama->jam_mulai, 0, 5);
        $jamSelesai = substr($jadwalAkhir->jam_selesai, 0, 5);

        if ($tanggalCarbon->gt(now()->endOfDay())) {
            return redirect()->route('siswa.dashboard')->with('error', 'Laporan kehadiran untuk tanggal mendatang belum dapat diakses.');
        }

        if ($tanggalCarbon->isToday()) {
            $nowTime = now()->format('H:i');
            if ($nowTime < $jamMulai) {
                return redirect()->route('siswa.dashboard')->with('error', "Pengambilan foto untuk mata pelajaran {$jadwal->mataPelajaran->nama} belum dibuka (Mulai pukul {$jamMulai} WIB).");
            }
        }

        // Allow access outside current hours within 7 days for catch-up/recap
        $isPast = $tanggalCarbon->lt(now()->subDays(7)->startOfDay());

        $pertemuan = Pertemuan::firstOrCreate(
            ['jadwal_id' => $jadwal->id, 'tanggal' => $tanggalCarbon->format('Y-m-d 00:00:00')],
            ['status' => 'menunggu']
        );

        $subIds = $consecutiveSchedules->pluck('id')->all();
        $pertemuanIds = Pertemuan::whereIn('jadwal_id', $subIds)
            ->whereDate('tanggal', $tanggalCarbon)
            ->pluck('id');

        $existingCapture = FotoBukti::whereIn('pertemuan_id', $pertemuanIds)
            ->where('siswa_id', $user->id)
            ->first();

        $classCapture = FotoBukti::with('siswa')
            ->whereIn('pertemuan_id', $pertemuanIds)
            ->first();

        $enableCheckout = (Setting::get('enable_checkout_foto', '0') == '1');
        $jamCheckoutMulai = Carbon::createFromFormat('H:i', $jamSelesai)->subMinutes(15)->format('H:i');
        $canCheckout = ! $tanggalCarbon->isToday() || (now()->format('H:i') >= $jamCheckoutMulai);

        // Toleransi Waktu Keterlambatan:
        // Opsi 1: Mapel jam pertama (default 10 menit awal)
        // Opsi 2: Mapel ke-2 dan selanjutnya (default 15 menitan)
        $toleransiMapelPertama = (int) Setting::get('toleransi_mapel_pertama_menit', Setting::get('toleransi_keterlambatan_menit', '10'));
        $toleransiMapelLanjutan = (int) Setting::get('toleransi_mapel_lanjutan_menit', '15');

        $firstSchedOfDayId = null;
        if ($kelas && $kelas->is_sistem_blok) {
            $activeJadwals = app(JadwalBlokResolverService::class)->resolveJadwal($kelas, $tanggalCarbon, (string) $jadwal->hari);
            $firstSchedOfDayId = $activeJadwals->sortBy('jam_mulai')->first()?->id;
        }
        if (! $firstSchedOfDayId) {
            $firstSchedOfDayId = JadwalPelajaran::where('kelas_id', $kelas?->id)
                ->where('hari', $jadwal->hari)
                ->orderBy('jam_mulai')
                ->value('id');
        }
        $isFirstMapelOfDay = ($firstSchedOfDayId === $jadwalUtama->id);

        // Load existing per-JP status for this student (from FotoBukti per consecutive sched)
        $pertemuansByJadwal = Pertemuan::whereIn('jadwal_id', $subIds)
            ->whereDate('tanggal', $tanggalCarbon)
            ->get()
            ->keyBy('jadwal_id');

        $existingCapturesByJadwal = FotoBukti::whereIn('pertemuan_id', $pertemuansByJadwal->pluck('id'))
            ->where('siswa_id', $user->id)
            ->get()
            ->keyBy('pertemuan_id');

        // Build per-JP data for the view
        $perJpData = $consecutiveSchedules->map(function ($sched, $index) use ($pertemuansByJadwal, $existingCapturesByJadwal, $isFirstMapelOfDay, $toleransiMapelPertama, $toleransiMapelLanjutan, $tanggalCarbon) {
            $prt = $pertemuansByJadwal->get($sched->id);
            $capture = $prt ? $existingCapturesByJadwal->get($prt->id) : null;
            $jpIndex = $index + 1;
            $jamMulaiClean = substr($sched->jam_mulai, 0, 5);

            // Toleransi: jam pertama KBM pagi dapat 10 menit, jam ke-2+ / mapel ke-2+ dapat 15 menit
            $toleransiMenit = ($isFirstMapelOfDay && $index === 0) ? $toleransiMapelPertama : $toleransiMapelLanjutan;

            $deadlineToleransi = Carbon::createFromFormat('H:i', $jamMulaiClean)->addMinutes($toleransiMenit);
            $isWithinTolerance = false;
            if ($tanggalCarbon->isToday()) {
                $isWithinTolerance = now()->lt($deadlineToleransi);
            }

            return [
                'jadwal' => $sched,
                'pertemuan' => $prt,
                'capture' => $capture,
                'jp_index' => $jpIndex,
                'jam_mulai' => $jamMulaiClean,
                'jam_selesai' => substr($sched->jam_selesai, 0, 5),
                'current_status' => $capture?->status_guru_dilaporkan ?? null,
                'toleransi_menit' => $toleransiMenit,
                'deadline_toleransi' => $deadlineToleransi->format('H:i'),
                'is_within_tolerance' => $isWithinTolerance,
            ];
        });

        // Toleransi sesi global (untuk single-JP)
        $toleransiUtamaMenit = $isFirstMapelOfDay ? $toleransiMapelPertama : $toleransiMapelLanjutan;
        $deadlineToleransiUtama = Carbon::createFromFormat('H:i', $jamMulai)->addMinutes($toleransiUtamaMenit);
        $isWithinToleranceUtama = $tanggalCarbon->isToday() && now()->lt($deadlineToleransiUtama);

        return view('siswa.capture.show', [
            'pertemuan' => $pertemuan->load(['jadwal.guru', 'jadwal.mataPelajaran', 'kehadiranGuru']),
            'existingCapture' => $existingCapture ?? $classCapture,
            'myCapture' => $existingCapture,
            'classCapture' => $classCapture,
            'isPast' => $isPast,
            'enableCheckout' => $enableCheckout,
            'canCheckout' => $canCheckout,
            'jamCheckoutMulai' => $jamCheckoutMulai,
            'consecutiveSchedules' => $consecutiveSchedules,
            'totalJp' => $consecutiveSchedules->count(),
            'jamMulai' => $jamMulai,
            'jamSelesai' => $jamSelesai,
            'perJpData' => $perJpData,
            'isFirstMapelOfDay' => $isFirstMapelOfDay,
            'toleransiUtamaMenit' => $toleransiUtamaMenit,
            'deadlineToleransiUtama' => $deadlineToleransiUtama->format('H:i'),
            'isWithinToleranceUtama' => $isWithinToleranceUtama,
            'toleransiMapelPertama' => $toleransiMapelPertama,
            'toleransiMapelLanjutan' => $toleransiMapelLanjutan,
        ]);
    }

    public function store(Request $request, int $jadwalId, string $tanggal): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['identifier' => 'Akun Anda sedang dinonaktifkan oleh Wali Kelas atau Admin.']);
        }

        $kelas = $user->siswaProfile?->kelas;

        $jadwal = JadwalPelajaran::findOrFail($jadwalId);
        abort_unless($jadwal->kelas_id === $kelas?->id, 403);

        $tanggalCarbon = Carbon::parse($tanggal);

        // Check if schedule is active for this class on this date in block system
        if ($kelas && $kelas->is_sistem_blok) {
            $activeJadwals = app(JadwalBlokResolverService::class)->resolveJadwal($kelas, $tanggalCarbon, (string) $jadwal->hari);
            if (! $activeJadwals->pluck('id')->contains($jadwal->id)) {
                return redirect()->route('siswa.dashboard')->with('error', 'Mata pelajaran ini tidak aktif untuk kelas Anda pada tanggal tersebut.');
            }

            if ($kelas->model_rotasi === 'split_harian' && $user->siswaProfile?->kelompok_blok) {
                $kelompokSiswa = $user->siswaProfile->kelompok_blok;
                if ($jadwal->kelompok_blok && $jadwal->kelompok_blok !== 'reguler' && $jadwal->kelompok_blok !== $kelompokSiswa) {
                    return redirect()->route('siswa.dashboard')->with('error', 'Mata pelajaran ini bukan untuk kelompok Anda.');
                }
            }
        }

        if ($tanggalCarbon->gt(now()->endOfDay()) || $tanggalCarbon->lt(now()->subDays(7)->startOfDay())) {
            return redirect()->route('siswa.dashboard')->with('error', 'Waktu pengiriman laporan untuk tanggal ini sudah ditutup (maksimal 7 hari ke belakang).');
        }

        $consecutiveSchedules = $jadwal->getConsecutiveSchedules();
        $jadwalUtama = $consecutiveSchedules->first();
        $jamMulai = substr($jadwalUtama->jam_mulai, 0, 5);

        if ($tanggalCarbon->isToday()) {
            $nowTime = now()->format('H:i');
            if ($nowTime < $jamMulai) {
                return redirect()->route('siswa.dashboard')->with('error', "Pengambilan foto untuk mata pelajaran {$jadwal->mataPelajaran->nama} belum dibuka (Mulai pukul {$jamMulai} WIB).");
            }
        }

        $validated = $request->validate([
            'foto' => ['nullable', 'image', 'max:10240'], // Max 10MB input, will be compressed to < 300KB WebP
            // Per-JP statuses: keyed by jadwal_id (e.g., status_jp[123] = 'hadir')
            'status_jp' => ['nullable', 'array'],
            'status_jp.*' => ['required', 'in:hadir,terlambat,tidak_hadir,sakit,alpa,dispensasi'],
            'alasan_jp' => ['nullable', 'array'],
            'alasan_jp.*' => ['nullable', 'in:sakit,izin,cuti,rapat_dinas,dinas_luar,tugas_luar,tanpa_keterangan'],
            // Fallback global status for backward compatibility (single-JP schedules)
            'status_guru_dilaporkan' => ['nullable', 'in:hadir,terlambat,tidak_hadir,sakit,alpa,dispensasi'],
            'alasan_tidak_hadir' => ['nullable', 'in:sakit,izin,cuti,rapat_dinas,dinas_luar,tugas_luar,tanpa_keterangan'],
            'jenis_alpa_dilaporkan' => ['nullable', 'string', 'max:50'],
            'guru_pengganti_nama' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'keterangan_terlambat' => ['nullable', 'string', 'max:500'],
        ]);

        $subIds = $consecutiveSchedules->pluck('id')->all();
        $pertemuanIds = Pertemuan::whereIn('jadwal_id', $subIds)
            ->whereDate('tanggal', $tanggalCarbon)
            ->pluck('id');

        $existing = FotoBukti::whereIn('pertemuan_id', $pertemuanIds)
            ->where('siswa_id', $user->id)
            ->first();

        if (! $existing && ! $request->hasFile('foto')) {
            return back()->withErrors(['foto' => 'Foto bukti kehadiran wajib diunggah.'])->withInput();
        }

        $fotoPath = $existing?->foto_path;
        if ($request->hasFile('foto')) {
            $imageCompressor = app(ImageCompressor::class);
            $fotoPath = $imageCompressor->compressAndStore($request->file('foto'), 'foto-bukti', 1200, 80);
        }

        $hasNewFoto = $request->hasFile('foto');

        // Per-JP status map: keyed by jadwal_id
        $perJpStatuses = $validated['status_jp'] ?? [];

        // Determine global fallback status (for single-JP or when no per-JP data submitted)
        $globalStatus = $validated['status_guru_dilaporkan'] ?? 'hadir';

        // Validasi Toleransi: Cegah status terlambat jika masih dalam masa toleransi pada hari ini
        if ($tanggalCarbon->isToday()) {
            $toleransiMapelPertama = (int) Setting::get('toleransi_mapel_pertama_menit', Setting::get('toleransi_keterlambatan_menit', '10'));
            $toleransiMapelLanjutan = (int) Setting::get('toleransi_mapel_lanjutan_menit', '15');

            $firstSchedOfDayId = null;
            if ($kelas && $kelas->is_sistem_blok) {
                $activeJadwals = app(JadwalBlokResolverService::class)->resolveJadwal($kelas, $tanggalCarbon, (string) $jadwal->hari);
                $firstSchedOfDayId = $activeJadwals->sortBy('jam_mulai')->first()?->id;
            }
            if (! $firstSchedOfDayId) {
                $firstSchedOfDayId = JadwalPelajaran::where('kelas_id', $kelas?->id)
                    ->where('hari', $jadwal->hari)
                    ->orderBy('jam_mulai')
                    ->value('id');
            }
            $isFirstMapelOfDay = ($firstSchedOfDayId === $jadwalUtama->id);

            foreach ($consecutiveSchedules as $index => $sched) {
                $jpStatus = $perJpStatuses[$sched->id] ?? $globalStatus;
                if ($jpStatus === 'terlambat') {
                    $toleransiMenit = ($isFirstMapelOfDay && $index === 0) ? $toleransiMapelPertama : $toleransiMapelLanjutan;
                    $jamMulaiClean = substr($sched->jam_mulai, 0, 5);
                    $deadlineToleransi = Carbon::createFromFormat('H:i', $jamMulaiClean)->addMinutes($toleransiMenit);

                    if (now()->lt($deadlineToleransi)) {
                        $jpNum = $index + 1;

                        return back()->withErrors([
                            'status_guru_dilaporkan' => "Jam pelajaran ke-{$jpNum} ({$jamMulaiClean}) masih dalam masa toleransi keterlambatan ({$toleransiMenit} menit, hingga pukul {$deadlineToleransi->format('H:i')} WIB). Guru belum dapat ditandai Terlambat.",
                        ])->withInput();
                    }
                }
            }
        }

        $keteranganInput = trim((string) ($request->input('keterangan') ?: $request->input('keterangan_terlambat') ?: ''));
        $alasanPerJp = $validated['alasan_jp'] ?? [];

        // Save FotoBukti & sync KehadiranGuru per JP with individual statuses
        DB::transaction(function () use ($consecutiveSchedules, $user, $fotoPath, $hasNewFoto, $validated, $perJpStatuses, $globalStatus, $tanggalCarbon, $keteranganInput, $alasanPerJp) {
            foreach ($consecutiveSchedules as $index => $sched) {
                // Determine this JP's specific status:
                // 1. Use per-JP input if provided
                // 2. Fall back to global status for single-JP
                $jpStatus = $perJpStatuses[$sched->id] ?? $globalStatus;

                $targetPertemuan = Pertemuan::firstOrCreate(
                    ['jadwal_id' => $sched->id, 'tanggal' => $tanggalCarbon->format('Y-m-d 00:00:00')],
                    ['status' => 'menunggu']
                );

                $schedExisting = FotoBukti::where('pertemuan_id', $targetPertemuan->id)
                    ->where('siswa_id', $user->id)
                    ->first();

                // Determine alasan for this JP (only relevant when status is tidak_hadir)
                $alasanForJp = in_array($jpStatus, ['tidak_hadir', 'sakit', 'alpa', 'dispensasi'])
                    ? ($alasanPerJp[$sched->id] ?? $validated['alasan_tidak_hadir'] ?? null)
                    : null;

                $keteranganForJp = in_array($jpStatus, ['terlambat', 'tidak_hadir', 'sakit', 'alpa', 'dispensasi'])
                    ? ($keteranganInput ?: null)
                    : null;

                $fotoBuktiData = [
                    'foto_path' => $fotoPath,
                    'status_guru_dilaporkan' => $jpStatus,
                    'alasan_tidak_hadir' => $alasanForJp,
                    'jenis_alpa_dilaporkan' => $validated['jenis_alpa_dilaporkan'] ?? null,
                    'guru_pengganti_nama' => $validated['guru_pengganti_nama'] ?? null,
                    'keterangan' => $keteranganForJp,
                ];

                if ($schedExisting) {
                    if ($hasNewFoto && $schedExisting->foto_path && $schedExisting->foto_path !== $fotoPath) {
                        Storage::disk('public')->delete($schedExisting->foto_path);
                    }
                    $schedExisting->update($fotoBuktiData);
                } else {
                    FotoBukti::create(array_merge($fotoBuktiData, [
                        'pertemuan_id' => $targetPertemuan->id,
                        'siswa_id' => $user->id,
                    ]));
                }

                // Sync to KehadiranGuru with the per-JP status
                KehadiranGuru::updateOrCreate(
                    ['pertemuan_id' => $targetPertemuan->id, 'guru_id' => $sched->guru_id],
                    [
                        'status' => $jpStatus,
                        'alasan_tidak_hadir' => $alasanForJp,
                        'guru_pengganti_nama' => $validated['guru_pengganti_nama'] ?? null,
                        'keterangan' => $keteranganForJp,
                        'waktu_hadir' => in_array($jpStatus, ['hadir', 'terlambat'])
                            ? (KehadiranGuru::where('pertemuan_id', $targetPertemuan->id)->where('guru_id', $sched->guru_id)->value('waktu_hadir') ?? now())
                            : null,
                    ]
                );

                $targetPertemuan->update(['status' => 'berlangsung']);
            }
        });

        $msg = $consecutiveSchedules->count() > 1
            ? "Foto bukti presensi guru berhasil disimpan untuk {$consecutiveSchedules->count()} jam pelajaran (status per-JP tersimpan akurat)!"
            : 'Foto bukti dan presensi guru berhasil disimpan!';

        return redirect()->route('siswa.dashboard')->with('success', $msg);
    }

    /**
     * Store student check-out photo at the end of class/school day.
     */
    public function storeCheckout(Request $request, int $jadwalId, string $tanggal): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['identifier' => 'Akun Anda sedang dinonaktifkan oleh Wali Kelas atau Admin.']);
        }

        $kelas = $user->siswaProfile?->kelas;

        $jadwal = JadwalPelajaran::findOrFail($jadwalId);
        abort_unless($jadwal->kelas_id === $kelas?->id, 403);

        if (Setting::get('enable_checkout_foto', '0') !== '1') {
            return redirect()->route('siswa.dashboard')->with('error', 'Fitur foto check-out saat ini dinonaktifkan.');
        }

        $tanggalCarbon = Carbon::parse($tanggal);

        // Check if schedule is active for this class on this date in block system
        if ($kelas && $kelas->is_sistem_blok) {
            $activeJadwals = app(JadwalBlokResolverService::class)->resolveJadwal($kelas, $tanggalCarbon, (string) $jadwal->hari);
            if (! $activeJadwals->pluck('id')->contains($jadwal->id)) {
                return redirect()->route('siswa.dashboard')->with('error', 'Mata pelajaran ini tidak aktif untuk kelas Anda pada tanggal tersebut.');
            }

            if ($kelas->model_rotasi === 'split_harian' && $user->siswaProfile?->kelompok_blok) {
                $kelompokSiswa = $user->siswaProfile->kelompok_blok;
                if ($jadwal->kelompok_blok && $jadwal->kelompok_blok !== 'reguler' && $jadwal->kelompok_blok !== $kelompokSiswa) {
                    return redirect()->route('siswa.dashboard')->with('error', 'Mata pelajaran ini bukan untuk kelompok Anda.');
                }
            }
        }

        if ($tanggalCarbon->gt(now()->endOfDay()) || $tanggalCarbon->lt(now()->subDays(7)->startOfDay())) {
            return redirect()->route('siswa.dashboard')->with('error', 'Waktu pengiriman laporan untuk tanggal ini sudah ditutup.');
        }

        $consecutiveSchedules = $jadwal->getConsecutiveSchedules();
        $jadwalAkhir = $consecutiveSchedules->last();
        $jamSelesai = substr($jadwalAkhir->jam_selesai, 0, 5);
        $jamCheckoutMulai = Carbon::createFromFormat('H:i', $jamSelesai)->subMinutes(15)->format('H:i');

        if ($tanggalCarbon->isToday()) {
            $nowTime = now()->format('H:i');
            if ($nowTime < $jamCheckoutMulai) {
                return redirect()->route('siswa.dashboard')->with('error', "Check-out untuk mata pelajaran {$jadwal->mataPelajaran->nama} baru dapat dilakukan mulai pukul {$jamCheckoutMulai} WIB (15 menit sebelum jam pelajaran berakhir).");
            }
        }

        $subIds = $consecutiveSchedules->pluck('id')->all();

        $pertemuanIds = Pertemuan::whereIn('jadwal_id', $subIds)
            ->whereDate('tanggal', $tanggalCarbon)
            ->pluck('id');

        $hasFotoAwal = FotoBukti::whereIn('pertemuan_id', $pertemuanIds)
            ->whereNotNull('foto_path')
            ->exists();

        if (! $hasFotoAwal) {
            return redirect()->route('siswa.dashboard')->with('error', 'Foto bukti awal (masuk) wajib diunggah terlebih dahulu sebelum melakukan check-out.');
        }

        $validated = $request->validate([
            'foto_checkout' => ['required', 'image', 'max:10240'],
        ]);

        $imageCompressor = app(ImageCompressor::class);
        $fotoCheckoutPath = $imageCompressor->compressAndStore($request->file('foto_checkout'), 'foto-bukti', 1200, 80);

        DB::transaction(function () use ($pertemuanIds, $user, $fotoCheckoutPath) {
            foreach ($pertemuanIds as $pertemuanId) {
                $fotoBukti = FotoBukti::where('pertemuan_id', $pertemuanId)
                    ->where('siswa_id', $user->id)
                    ->first() ?? FotoBukti::where('pertemuan_id', $pertemuanId)->whereNotNull('foto_path')->first();

                if ($fotoBukti) {
                    if ($fotoBukti->foto_checkout_path && $fotoBukti->foto_checkout_path !== $fotoCheckoutPath) {
                        Storage::disk('public')->delete($fotoBukti->foto_checkout_path);
                    }

                    $fotoBukti->update([
                        'foto_checkout_path' => $fotoCheckoutPath,
                        'checkout_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('siswa.dashboard')->with('success', 'Foto bukti check-out (akhir jam pelajaran) berhasil disimpan!');
    }
}
