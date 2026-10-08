<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\FotoBukti;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
use App\Models\KehadiranSiswa;
use App\Models\Pertemuan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class PertemuanController extends Controller
{
    /**
     * Show or create a pertemuan for a specific jadwal and date.
     */
    public function show(int $jadwalId, string $tanggal): View|RedirectResponse
    {
        $jadwal = JadwalPelajaran::with(['kelas', 'mataPelajaran', 'kelas.siswaProfiles.user'])
            ->where('guru_id', Auth::id())
            ->findOrFail($jadwalId);

        $tanggalCarbon = Carbon::parse($tanggal);

        // Prevent creating ghost meetings for future dates beyond today
        if ($tanggalCarbon->gt(now()->endOfDay())) {
            return redirect()->route('guru.dashboard')->with('error', 'Pertemuan untuk tanggal mendatang belum dapat dibuka.');
        }

        $pertemuan = Pertemuan::with([
            'kehadiranGuru',
            'kehadiranSiswas.siswa',
            'fotoBuktis.siswa',
        ])->firstOrCreate(
            ['jadwal_id' => $jadwal->id, 'tanggal' => $tanggalCarbon->format('Y-m-d 00:00:00')],
            ['status' => 'menunggu']
        );

        // Auto-create kehadiran_siswa rows for active students in the class (scoped to block group for split classes)
        $siswaQuery = $jadwal->kelas->siswaProfiles()
            ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'));

        if ($jadwal->kelas->is_sistem_blok && $jadwal->kelas->model_rotasi === 'split_harian' && in_array($jadwal->kelompok_blok, ['kelompok_a', 'kelompok_b'])) {
            $siswaQuery->where('kelompok_blok', $jadwal->kelompok_blok);
        }

        $siswaIds = $siswaQuery->pluck('user_id')->filter();

        foreach ($siswaIds as $siswaId) {
            KehadiranSiswa::firstOrCreate(
                ['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaId],
                ['status' => 'hadir']
            );
        }

        $pertemuan->load('kehadiranSiswas.siswa');

        $isLocked = $tanggalCarbon->lt(now()->subDays(7)->startOfDay()) || $tanggalCarbon->gt(now()->endOfDay());

        // Get consecutive schedules in the same session
        $consecutiveSchedules = $jadwal->getConsecutiveSchedules();
        $totalJp = $consecutiveSchedules->count();
        $jamMulaiFormatted = substr($consecutiveSchedules->first()->jam_mulai, 0, 5);
        $jamSelesaiFormatted = substr($consecutiveSchedules->last()->jam_selesai, 0, 5);

        return view('guru.pertemuan.show', [
            'jadwal' => $jadwal,
            'pertemuan' => $pertemuan,
            'tanggal' => $tanggalCarbon,
            'isLocked' => $isLocked,
            'consecutiveSchedules' => $consecutiveSchedules,
            'totalJp' => $totalJp,
            'jamMulaiFormatted' => $jamMulaiFormatted,
            'jamSelesaiFormatted' => $jamSelesaiFormatted,
        ]);
    }

    /**
     * Update materi ajar, penugasan, guru attendance, and siswa attendance all at once.
     */
    public function saveAll(Request $request, Pertemuan $pertemuan): RedirectResponse
    {
        // Ensure this pertemuan belongs to the authenticated guru
        abort_unless($pertemuan->jadwal->guru_id === Auth::id(), 403);

        $tanggalCarbon = Carbon::parse($pertemuan->tanggal);
        if ($tanggalCarbon->lt(now()->subDays(7)->startOfDay()) || $tanggalCarbon->gt(now()->endOfDay())) {
            return back()->with('error', 'Waktu pengisian data untuk tanggal ini sudah ditutup (batas maksimal 7 hari ke belakang).');
        }

        $validated = $request->validate([
            // Pertemuan
            'materi_ajar' => ['nullable', 'string', 'max:5000'],
            'penugasan' => ['nullable', 'string', 'max:5000'],
            // Siswa
            'siswa' => ['nullable', 'array'],
            'siswa.*.status' => ['required', 'in:hadir,terlambat,sakit,izin,alpa,dispensasi'],
            'siswa.*.keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $consecutiveSchedules = $pertemuan->jadwal->getConsecutiveSchedules();

        DB::transaction(function () use ($pertemuan, $validated, $consecutiveSchedules) {
            foreach ($consecutiveSchedules as $sched) {
                $targetPertemuan = ($sched->id === $pertemuan->jadwal_id)
                    ? $pertemuan
                    : Pertemuan::firstOrCreate(
                        ['jadwal_id' => $sched->id, 'tanggal' => $pertemuan->tanggal],
                        ['status' => 'menunggu']
                    );

                // 1. Update Pertemuan
                $targetPertemuan->update([
                    'materi_ajar' => $validated['materi_ajar'] ?? null,
                    'penugasan' => $validated['penugasan'] ?? null,
                    'status' => 'berlangsung',
                ]);

                // 2. Ensure KehadiranGuru exists with default 'hadir'
                KehadiranGuru::firstOrCreate(
                    ['pertemuan_id' => $targetPertemuan->id, 'guru_id' => Auth::id()],
                    [
                        'status' => 'hadir',
                        'waktu_hadir' => now(),
                    ]
                );

                // 3. Update Siswa Attendance
                if (isset($validated['siswa']) && is_array($validated['siswa'])) {
                    foreach ($validated['siswa'] as $siswaId => $data) {
                        KehadiranSiswa::updateOrCreate(
                            ['pertemuan_id' => $targetPertemuan->id, 'siswa_id' => $siswaId],
                            [
                                'status' => $data['status'],
                                'keterangan' => $data['keterangan'] ?? null,
                            ]
                        );
                    }
                }
            }
        });

        $successMsg = $consecutiveSchedules->count() > 1
            ? "Agenda mengajar dan presensi siswa berhasil disimpan untuk {$consecutiveSchedules->count()} jam pelajaran sekaligus."
            : 'Agenda mengajar dan presensi siswa berhasil disimpan.';

        return back()->with('success', $successMsg);
    }

    /**
     * Download individual photo proof (masuk or checkout).
     */
    public function downloadFoto(Request $request, FotoBukti $fotoBukti): BinaryFileResponse|RedirectResponse
    {
        $user = Auth::user();
        $jadwal = $fotoBukti->pertemuan?->jadwal;
        $isOwner = $jadwal && (int) $jadwal->guru_id === (int) $user->id;
        $isPengganti = $fotoBukti->pertemuan?->kehadiranGuru && ($fotoBukti->pertemuan->kehadiranGuru->guru_pengganti_nama === $user->name);
        $hasPrivilege = in_array($user->role, ['admin', 'super_admin', 'piket', 'kepala_sekolah', 'pengawas'])
            || $user->hasAnyRole(['admin', 'super_admin', 'piket', 'kepala_sekolah', 'pengawas']);

        abort_unless($isOwner || $isPengganti || $hasPrivilege, 403, 'Anda tidak memiliki akses untuk mengunduh foto ini.');

        $type = $request->query('type', 'masuk');
        $path = ($type === 'checkout') ? $fotoBukti->foto_checkout_path : $fotoBukti->foto_path;

        if (empty($path)) {
            return back()->with('error', 'Foto bukti belum diunggah.');
        }

        $fullPath = $this->resolvePhotoFullPath($path);

        if (! $fullPath || ! file_exists($fullPath)) {
            return back()->with('error', 'File foto tidak ditemukan di penyimpanan server.');
        }

        $extension = pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'webp';
        $kelasNama = Str::slug($jadwal?->kelas?->nama ?? 'Kelas', '-');
        $mapelNama = Str::slug($jadwal?->mataPelajaran?->nama ?? 'Mapel', '-');
        $siswaNama = Str::slug($fotoBukti->siswa?->name ?? 'Siswa', '-');
        $tanggalStr = Carbon::parse($fotoBukti->pertemuan?->tanggal ?? now())->format('Y-m-d');
        $typeLabel = ($type === 'checkout') ? 'Checkout' : 'Masuk';

        $downloadFilename = "Foto-Presensi_{$kelasNama}_{$mapelNama}_{$tanggalStr}_{$typeLabel}_{$siswaNama}.{$extension}";

        return response()->download($fullPath, $downloadFilename, [
            'Content-Type' => mime_content_type($fullPath) ?: 'image/webp',
        ]);
    }

    /**
     * Download all photo proofs for a meeting as a ZIP archive.
     */
    public function downloadAllFoto(Pertemuan $pertemuan): BinaryFileResponse|RedirectResponse
    {
        $user = Auth::user();
        $jadwal = $pertemuan->jadwal;
        $isOwner = $jadwal && (int) $jadwal->guru_id === (int) $user->id;
        $isPengganti = $pertemuan->kehadiranGuru && ($pertemuan->kehadiranGuru->guru_pengganti_nama === $user->name);
        $hasPrivilege = in_array($user->role, ['admin', 'super_admin', 'piket', 'kepala_sekolah', 'pengawas'])
            || $user->hasAnyRole(['admin', 'super_admin', 'piket', 'kepala_sekolah', 'pengawas']);

        abort_unless($isOwner || $isPengganti || $hasPrivilege, 403, 'Anda tidak memiliki akses untuk mengunduh foto pertemuan ini.');

        $pertemuan->load(['fotoBuktis.siswa', 'jadwal.kelas', 'jadwal.mataPelajaran']);
        $fotoBuktis = $pertemuan->fotoBuktis;

        if ($fotoBuktis->isEmpty()) {
            return back()->with('error', 'Belum ada foto bukti yang diunggah untuk pertemuan ini.');
        }

        $filesToZip = [];

        foreach ($fotoBuktis as $idx => $foto) {
            $siswaNama = Str::slug($foto->siswa?->name ?? 'Siswa', '-');
            $indexNum = sprintf('%02d', $idx + 1);

            // Foto Masuk
            if (! empty($foto->foto_path)) {
                $filePath = $this->resolvePhotoFullPath($foto->foto_path);
                if ($filePath && file_exists($filePath)) {
                    $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'webp';
                    $entryName = "{$indexNum}_Masuk_{$siswaNama}.{$ext}";
                    $filesToZip[$entryName] = $filePath;
                }
            }

            // Foto Checkout
            if (! empty($foto->foto_checkout_path)) {
                $filePathOut = $this->resolvePhotoFullPath($foto->foto_checkout_path);
                if ($filePathOut && file_exists($filePathOut)) {
                    $ext = pathinfo($filePathOut, PATHINFO_EXTENSION) ?: 'webp';
                    $entryName = "{$indexNum}_Checkout_{$siswaNama}.{$ext}";
                    $filesToZip[$entryName] = $filePathOut;
                }
            }
        }

        if (empty($filesToZip)) {
            return back()->with('error', 'File fisik foto bukti tidak ditemukan di penyimpanan server.');
        }

        $kelasNama = Str::slug($jadwal?->kelas?->nama ?? 'Kelas', '-');
        $mapelNama = Str::slug($jadwal?->mataPelajaran?->nama ?? 'Mapel', '-');
        $tanggalStr = Carbon::parse($pertemuan->tanggal)->format('Y-m-d');
        $zipFilename = "Semua-Foto-Presensi_{$kelasNama}_{$mapelNama}_{$tanggalStr}.zip";

        $tempZipPath = tempnam(sys_get_temp_dir(), 'foto_zip_');
        $zip = new ZipArchive;

        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tempZipPath);

            return back()->with('error', 'Gagal membuat arsip ZIP foto.');
        }

        foreach ($filesToZip as $entryName => $filePath) {
            $zip->addFile($filePath, $entryName);
        }
        $zip->close();

        return response()->download($tempZipPath, $zipFilename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Resolve the absolute path of a photo stored in disk public or public directory.
     */
    private function resolvePhotoFullPath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $trimmed = ltrim($path, '/\\');

        if (str_starts_with($trimmed, 'images/')) {
            $candidate = public_path($trimmed);
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        $disk = Storage::disk('public');
        if ($disk->exists($trimmed)) {
            return $disk->path($trimmed);
        }

        // If path has prefix 'storage/', strip it for public disk check
        if (str_starts_with($trimmed, 'storage/')) {
            $stripped = substr($trimmed, 8);
            if ($disk->exists($stripped)) {
                return $disk->path($stripped);
            }
        }

        $storageCandidate = public_path('storage/'.$trimmed);
        if (file_exists($storageCandidate)) {
            return $storageCandidate;
        }

        $publicDirectCandidate = public_path($trimmed);
        if (file_exists($publicDirectCandidate)) {
            return $publicDirectCandidate;
        }

        return null;
    }
}
