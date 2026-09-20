<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\FotoBukti;
use App\Models\JadwalPelajaran;
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

        $now = Carbon::now();
        $isToday = $tanggalCarbon->isToday();
        $isWithinTime = $now->format('H:i') >= '06:30';

        $isPast = ! ($isToday && $isWithinTime);

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

        $now = Carbon::now();
        $isToday = $tanggalCarbon->isToday();
        $isWithinTime = $now->format('H:i') >= '06:30';

        if (! ($isToday && $isWithinTime)) {
            return redirect()->route('siswa.dashboard')->with('error', 'Waktu pengiriman laporan ditutup. Siswa hanya dapat melapor pada hari yang sama mulai pukul 06:30 hingga 23:59.');
        }

        $pertemuan = Pertemuan::firstOrCreate(
            ['jadwal_id' => $jadwal->id, 'tanggal' => $tanggalCarbon->format('Y-m-d 00:00:00')],
            ['status' => 'menunggu']
        );

        $validated = $request->validate([
            'foto' => ['required', 'image', 'max:10240'], // Max 10MB input, will be compressed to < 300KB WebP
            'status_guru_dilaporkan' => ['required', 'in:hadir,sakit,alpa,dispensasi'],
            'jenis_alpa_dilaporkan' => ['nullable', 'required_if:status_guru_dilaporkan,alpa', 'in:ada_tugas,tanpa_tugas,guru_pengganti'],
            'guru_pengganti_nama' => ['nullable', 'required_if:jenis_alpa_dilaporkan,guru_pengganti', 'string', 'max:255'],
        ]);

        // Compress and store the photo as WebP
        $imageCompressor = app(ImageCompressor::class);
        $fotoPath = $imageCompressor->compressAndStore($request->file('foto'), 'foto-bukti', 1200, 80);

        // Delete old photo if re-capturing and update/create record inside transaction
        DB::transaction(function () use ($pertemuan, $user, $fotoPath, $validated) {
            $existing = FotoBukti::where('pertemuan_id', $pertemuan->id)
                ->where('siswa_id', $user->id)
                ->first();

            if ($existing) {
                Storage::disk('public')->delete($existing->foto_path);
                $existing->update([
                    'foto_path' => $fotoPath,
                    'status_guru_dilaporkan' => $validated['status_guru_dilaporkan'],
                    'jenis_alpa_dilaporkan' => $validated['jenis_alpa_dilaporkan'] ?? null,
                    'guru_pengganti_nama' => $validated['guru_pengganti_nama'] ?? null,
                ]);
            } else {
                FotoBukti::create([
                    'pertemuan_id' => $pertemuan->id,
                    'siswa_id' => $user->id,
                    'foto_path' => $fotoPath,
                    'status_guru_dilaporkan' => $validated['status_guru_dilaporkan'],
                    'jenis_alpa_dilaporkan' => $validated['jenis_alpa_dilaporkan'] ?? null,
                    'guru_pengganti_nama' => $validated['guru_pengganti_nama'] ?? null,
                ]);
            }
        });

        return redirect()->route('siswa.dashboard')->with('success', 'Foto bukti berhasil disimpan. Terima kasih!');
    }
}
