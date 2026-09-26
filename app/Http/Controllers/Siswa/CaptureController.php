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

        $enableCheckout = (Setting::get('enable_checkout_foto', '0') == '1');

        return view('siswa.capture.show', [
            'pertemuan' => $pertemuan->load(['jadwal.guru', 'jadwal.mataPelajaran', 'kehadiranGuru']),
            'existingCapture' => $existingCapture,
            'isPast' => $isPast,
            'enableCheckout' => $enableCheckout,
            'consecutiveSchedules' => $consecutiveSchedules,
            'totalJp' => $consecutiveSchedules->count(),
            'jamMulai' => $jamMulai,
            'jamSelesai' => $jamSelesai,
        ]);
    }

    public function store(Request $request, int $jadwalId, string $tanggal): RedirectResponse
    {
        $user = Auth::user();
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
            'status_guru_dilaporkan' => ['required', 'in:hadir,terlambat,tidak_hadir,sakit,alpa,dispensasi'],
            'alasan_tidak_hadir' => ['nullable', 'required_if:status_guru_dilaporkan,tidak_hadir', 'in:sakit,izin,rapat_dinas,dinas_luar,tugas_luar,tanpa_keterangan'],
            'jenis_alpa_dilaporkan' => ['nullable', 'string', 'max:50'],
            'guru_pengganti_nama' => ['nullable', 'string', 'max:255'],
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

        // Apply Tolerance Logic for KehadiranGuru
        $statusGuru = $validated['status_guru_dilaporkan'];
        if ($statusGuru === 'hadir' && $tanggalCarbon->isToday()) {
            $toleransi = Setting::get('toleransi_keterlambatan_menit', 5);
            $waktuBatas = Carbon::parse($jamMulai)->addMinutes((int) $toleransi)->format('H:i');
            $nowTime = now()->format('H:i');

            if ($nowTime > $waktuBatas) {
                $statusGuru = 'terlambat';
            }
        }

        // Save FotoBukti & automatically sync teacher attendance in KehadiranGuru for all consecutive schedules
        DB::transaction(function () use ($consecutiveSchedules, $user, $fotoPath, $validated, $statusGuru, $tanggalCarbon) {
            foreach ($consecutiveSchedules as $sched) {
                $targetPertemuan = Pertemuan::firstOrCreate(
                    ['jadwal_id' => $sched->id, 'tanggal' => $tanggalCarbon->format('Y-m-d 00:00:00')],
                    ['status' => 'menunggu']
                );

                $schedExisting = FotoBukti::where('pertemuan_id', $targetPertemuan->id)
                    ->where('siswa_id', $user->id)
                    ->first();

                if ($schedExisting) {
                    if ($request->hasFile('foto') && $schedExisting->foto_path && $schedExisting->foto_path !== $fotoPath) {
                        Storage::disk('public')->delete($schedExisting->foto_path);
                    }
                    $schedExisting->update([
                        'foto_path' => $fotoPath,
                        'status_guru_dilaporkan' => $validated['status_guru_dilaporkan'],
                        'alasan_tidak_hadir' => $validated['alasan_tidak_hadir'] ?? null,
                        'jenis_alpa_dilaporkan' => $validated['jenis_alpa_dilaporkan'] ?? null,
                        'guru_pengganti_nama' => $validated['guru_pengganti_nama'] ?? null,
                    ]);
                } else {
                    FotoBukti::create([
                        'pertemuan_id' => $targetPertemuan->id,
                        'siswa_id' => $user->id,
                        'foto_path' => $fotoPath,
                        'status_guru_dilaporkan' => $validated['status_guru_dilaporkan'],
                        'alasan_tidak_hadir' => $validated['alasan_tidak_hadir'] ?? null,
                        'jenis_alpa_dilaporkan' => $validated['jenis_alpa_dilaporkan'] ?? null,
                        'guru_pengganti_nama' => $validated['guru_pengganti_nama'] ?? null,
                    ]);
                }

                // Sync to KehadiranGuru
                KehadiranGuru::updateOrCreate(
                    ['pertemuan_id' => $targetPertemuan->id, 'guru_id' => $sched->guru_id],
                    [
                        'status' => $statusGuru,
                        'alasan_tidak_hadir' => $validated['alasan_tidak_hadir'] ?? null,
                        'guru_pengganti_nama' => $validated['guru_pengganti_nama'] ?? null,
                        'waktu_hadir' => KehadiranGuru::where('pertemuan_id', $targetPertemuan->id)->where('guru_id', $sched->guru_id)->value('waktu_hadir') ?? now(),
                    ]
                );

                $targetPertemuan->update(['status' => 'berlangsung']);
            }
        });

        $msg = $consecutiveSchedules->count() > 1
            ? "Foto bukti presensi guru berhasil disimpan untuk {$consecutiveSchedules->count()} jam pelajaran sekaligus!"
            : 'Foto bukti dan presensi guru berhasil disimpan!';

        return redirect()->route('siswa.dashboard')->with('success', $msg);
    }

    /**
     * Store student check-out photo at the end of class/school day.
     */
    public function storeCheckout(Request $request, int $jadwalId, string $tanggal): RedirectResponse
    {
        $user = Auth::user();
        $kelas = $user->siswaProfile?->kelas;

        $jadwal = JadwalPelajaran::findOrFail($jadwalId);
        abort_unless($jadwal->kelas_id === $kelas?->id, 403);

        $tanggalCarbon = Carbon::parse($tanggal);

        if ($tanggalCarbon->gt(now()->endOfDay()) || $tanggalCarbon->lt(now()->subDays(7)->startOfDay())) {
            return redirect()->route('siswa.dashboard')->with('error', 'Waktu pengiriman laporan untuk tanggal ini sudah ditutup.');
        }

        $consecutiveSchedules = $jadwal->getConsecutiveSchedules();
        $subIds = $consecutiveSchedules->pluck('id')->all();

        $pertemuanIds = Pertemuan::whereIn('jadwal_id', $subIds)
            ->whereDate('tanggal', $tanggalCarbon)
            ->pluck('id');

        $hasFotoAwal = FotoBukti::whereIn('pertemuan_id', $pertemuanIds)
            ->where('siswa_id', $user->id)
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
                    ->first();

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
