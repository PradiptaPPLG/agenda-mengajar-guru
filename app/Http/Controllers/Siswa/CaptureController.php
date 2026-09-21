<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\FotoBukti;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
use App\Models\Pertemuan;
use App\Services\ImageCompressor;
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
        $kelasId = $user->siswaProfile?->kelas_id;

        $jadwal = JadwalPelajaran::findOrFail($jadwalId);
        abort_unless($jadwal->kelas_id === $kelasId, 403);

        $tanggalCarbon = Carbon::parse($tanggal);

        if ($tanggalCarbon->gt(now()->endOfDay())) {
            return redirect()->route('siswa.dashboard')->with('error', 'Laporan kehadiran untuk tanggal mendatang belum dapat diakses.');
        }

        if ($tanggalCarbon->isToday()) {
            $nowTime = now()->format('H:i');
            $jamMulai = substr($jadwal->jam_mulai, 0, 5);
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

        $existingCapture = FotoBukti::where('pertemuan_id', $pertemuan->id)
            ->where('siswa_id', $user->id)
            ->first();

        return view('siswa.capture.show', [
            'pertemuan' => $pertemuan->load(['jadwal.guru', 'jadwal.mataPelajaran', 'kehadiranGuru']),
            'existingCapture' => $existingCapture,
            'isPast' => $isPast,
        ]);
    }

    public function store(Request $request, int $jadwalId, string $tanggal): RedirectResponse
    {
        $user = Auth::user();
        $kelasId = $user->siswaProfile?->kelas_id;

        $jadwal = JadwalPelajaran::findOrFail($jadwalId);
        abort_unless($jadwal->kelas_id === $kelasId, 403);

        $tanggalCarbon = Carbon::parse($tanggal);

        if ($tanggalCarbon->gt(now()->endOfDay()) || $tanggalCarbon->lt(now()->subDays(7)->startOfDay())) {
            return redirect()->route('siswa.dashboard')->with('error', 'Waktu pengiriman laporan untuk tanggal ini sudah ditutup (maksimal 7 hari ke belakang).');
        }

        if ($tanggalCarbon->isToday()) {
            $nowTime = now()->format('H:i');
            $jamMulai = substr($jadwal->jam_mulai, 0, 5);
            if ($nowTime < $jamMulai) {
                return redirect()->route('siswa.dashboard')->with('error', "Pengambilan foto untuk mata pelajaran {$jadwal->mataPelajaran->nama} belum dibuka (Mulai pukul {$jamMulai} WIB).");
            }
        }

        $pertemuan = Pertemuan::firstOrCreate(
            ['jadwal_id' => $jadwal->id, 'tanggal' => $tanggalCarbon->format('Y-m-d 00:00:00')],
            ['status' => 'menunggu']
        );

        $validated = $request->validate([
            'foto' => ['nullable', 'image', 'max:10240'], // Max 10MB input, will be compressed to < 300KB WebP
            'status_guru_dilaporkan' => ['required', 'in:hadir,terlambat,tidak_hadir,sakit,alpa,dispensasi'],
            'alasan_tidak_hadir' => ['nullable', 'required_if:status_guru_dilaporkan,tidak_hadir', 'in:sakit,izin,rapat_dinas,dinas_luar,tugas_luar,tanpa_keterangan'],
            'jenis_alpa_dilaporkan' => ['nullable', 'string', 'max:50'],
            'guru_pengganti_nama' => ['nullable', 'string', 'max:255'],
        ]);

        $existing = FotoBukti::where('pertemuan_id', $pertemuan->id)
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

        // Save FotoBukti & automatically sync teacher attendance in KehadiranGuru
        DB::transaction(function () use ($pertemuan, $user, $fotoPath, $validated, $existing, $jadwal, $request) {
            if ($existing) {
                if ($request->hasFile('foto') && $existing->foto_path && $existing->foto_path !== $fotoPath) {
                    Storage::disk('public')->delete($existing->foto_path);
                }
                $existing->update([
                    'foto_path' => $fotoPath,
                    'status_guru_dilaporkan' => $validated['status_guru_dilaporkan'],
                    'alasan_tidak_hadir' => $validated['alasan_tidak_hadir'] ?? null,
                    'jenis_alpa_dilaporkan' => $validated['jenis_alpa_dilaporkan'] ?? null,
                    'guru_pengganti_nama' => $validated['guru_pengganti_nama'] ?? null,
                ]);
            } else {
                FotoBukti::create([
                    'pertemuan_id' => $pertemuan->id,
                    'siswa_id' => $user->id,
                    'foto_path' => $fotoPath,
                    'status_guru_dilaporkan' => $validated['status_guru_dilaporkan'],
                    'alasan_tidak_hadir' => $validated['alasan_tidak_hadir'] ?? null,
                    'jenis_alpa_dilaporkan' => $validated['jenis_alpa_dilaporkan'] ?? null,
                    'guru_pengganti_nama' => $validated['guru_pengganti_nama'] ?? null,
                ]);
            }

            // Sync to KehadiranGuru
            $statusGuru = $validated['status_guru_dilaporkan'];
            KehadiranGuru::updateOrCreate(
                ['pertemuan_id' => $pertemuan->id, 'guru_id' => $jadwal->guru_id],
                [
                    'status' => $statusGuru,
                    'alasan_tidak_hadir' => $validated['alasan_tidak_hadir'] ?? null,
                    'guru_pengganti_nama' => $validated['guru_pengganti_nama'] ?? null,
                    'waktu_hadir' => KehadiranGuru::where('pertemuan_id', $pertemuan->id)->where('guru_id', $jadwal->guru_id)->value('waktu_hadir') ?? now(),
                ]
            );

            $pertemuan->update(['status' => 'berlangsung']);
        });

        return redirect()->route('siswa.dashboard')->with('success', 'Foto bukti dan presensi guru berhasil disimpan!');
    }
}
