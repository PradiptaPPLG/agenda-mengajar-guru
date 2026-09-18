<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranSiswa;
use App\Models\Pertemuan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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

        $pertemuan = Pertemuan::with([
            'kehadiranGuru',
            'kehadiranSiswas.siswa',
            'fotoBuktis.siswa',
        ])->firstOrCreate(
            ['jadwal_id' => $jadwal->id, 'tanggal' => $tanggalCarbon->format('Y-m-d 00:00:00')],
            ['status' => 'menunggu']
        );

        // Auto-create kehadiran_siswa rows for all students in the class
        $siswaIds = $jadwal->kelas->siswaProfiles->pluck('user_id');
        foreach ($siswaIds as $siswaId) {
            KehadiranSiswa::firstOrCreate(
                ['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaId],
                ['status' => 'hadir']
            );
        }

        $pertemuan->load('kehadiranSiswas.siswa');

        return view('guru.pertemuan.show', [
            'jadwal' => $jadwal,
            'pertemuan' => $pertemuan,
            'tanggal' => $tanggalCarbon,
        ]);
    }

    /**
     * Update materi ajar, penugasan, guru attendance, and siswa attendance all at once.
     */
    public function saveAll(Request $request, Pertemuan $pertemuan): RedirectResponse
    {
        // Ensure this pertemuan belongs to the authenticated guru
        abort_unless($pertemuan->jadwal->guru_id === Auth::id(), 403);

        $validated = $request->validate([
            // Pertemuan
            'materi_ajar' => ['nullable', 'string', 'max:5000'],
            'penugasan' => ['nullable', 'string', 'max:5000'],
            // Guru
            'status' => ['nullable', 'in:hadir,sakit,alpa,dispensasi'],
            'jenis_alpa' => ['nullable', 'required_if:status,alpa', 'in:ada_tugas,tanpa_tugas,guru_pengganti'],
            'guru_pengganti_nama' => ['nullable', 'required_if:jenis_alpa,guru_pengganti', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            // Siswa
            'siswa' => ['nullable', 'array'],
            'siswa.*.status' => ['required', 'in:hadir,sakit,izin,alpa,dispensasi'],
        ]);

        // 1. Update Pertemuan
        $pertemuan->update([
            'materi_ajar' => $validated['materi_ajar'] ?? null,
            'penugasan' => $validated['penugasan'] ?? null,
        ]);

        // 2. Update Guru Attendance (only if submitted)
        if (isset($validated['status'])) {
            \App\Models\KehadiranGuru::updateOrCreate(
                ['pertemuan_id' => $pertemuan->id, 'guru_id' => Auth::id()],
                [
                    'status' => $validated['status'],
                    'jenis_alpa' => $validated['jenis_alpa'] ?? null,
                    'guru_pengganti_nama' => $validated['guru_pengganti_nama'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,
                    'waktu_hadir' => \App\Models\KehadiranGuru::where('pertemuan_id', $pertemuan->id)->where('guru_id', Auth::id())->value('waktu_hadir') ?? now(),
                ]
            );
            $pertemuan->update(['status' => 'berlangsung']);
        }

        // 3. Update Siswa Attendance
        if (isset($validated['siswa']) && is_array($validated['siswa'])) {
            foreach ($validated['siswa'] as $siswaId => $data) {
                KehadiranSiswa::updateOrCreate(
                    ['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaId],
                    ['status' => $data['status']]
                );
            }
        }

        return back()->with('success', 'Semua data pertemuan berhasil disimpan.');
    }
}
