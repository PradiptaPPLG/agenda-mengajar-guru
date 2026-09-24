<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
use App\Models\KehadiranSiswa;
use App\Models\Pertemuan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Auto-create kehadiran_siswa rows for all active students in the class
        $siswaIds = $jadwal->kelas->siswaProfiles()
            ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
            ->pluck('user_id')
            ->filter();

        foreach ($siswaIds as $siswaId) {
            KehadiranSiswa::firstOrCreate(
                ['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaId],
                ['status' => 'hadir']
            );
        }

        $pertemuan->load('kehadiranSiswas.siswa');

        $isLocked = $tanggalCarbon->lt(now()->subDays(7)->startOfDay()) || $tanggalCarbon->gt(now()->endOfDay());

        return view('guru.pertemuan.show', [
            'jadwal' => $jadwal,
            'pertemuan' => $pertemuan,
            'tanggal' => $tanggalCarbon,
            'isLocked' => $isLocked,
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
            'siswa.*.status' => ['required', 'in:hadir,sakit,izin,alpa,dispensasi'],
            'siswa.*.keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($pertemuan, $validated) {
            // 1. Update Pertemuan
            $pertemuan->update([
                'materi_ajar' => $validated['materi_ajar'] ?? null,
                'penugasan' => $validated['penugasan'] ?? null,
                'status' => 'berlangsung',
            ]);

            // 2. Ensure KehadiranGuru exists with default 'hadir' if not yet created by student
            KehadiranGuru::firstOrCreate(
                ['pertemuan_id' => $pertemuan->id, 'guru_id' => Auth::id()],
                [
                    'status' => 'hadir',
                    'waktu_hadir' => now(),
                ]
            );

            // 3. Update Siswa Attendance
            if (isset($validated['siswa']) && is_array($validated['siswa'])) {
                foreach ($validated['siswa'] as $siswaId => $data) {
                    KehadiranSiswa::updateOrCreate(
                        ['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaId],
                        [
                            'status' => $data['status'],
                            'keterangan' => $data['keterangan'] ?? null,
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Agenda mengajar dan presensi siswa berhasil disimpan.');
    }
}
