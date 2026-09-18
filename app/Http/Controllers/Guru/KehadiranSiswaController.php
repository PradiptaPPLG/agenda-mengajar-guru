<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\KehadiranSiswa;
use App\Models\Pertemuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KehadiranSiswaController extends Controller
{
    /**
     * Update student attendance status.
     */
    public function update(Request $request, Pertemuan $pertemuan, int $siswaId): RedirectResponse
    {
        abort_unless($pertemuan->jadwal->guru_id === Auth::id(), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:hadir,sakit,izin,alpa,dispensasi'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        KehadiranSiswa::updateOrCreate(
            ['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaId],
            $validated
        );

        return back()->with('success', 'Status kehadiran siswa diperbarui.');
    }

    /**
     * Bulk update all students (mark all as present, then apply exceptions).
     */
    public function bulkUpdate(Request $request, Pertemuan $pertemuan): RedirectResponse
    {
        abort_unless($pertemuan->jadwal->guru_id === Auth::id(), 403);

        $validated = $request->validate([
            'siswa' => ['required', 'array'],
            'siswa.*.status' => ['required', 'in:hadir,sakit,izin,alpa,dispensasi'],
            'siswa.*.keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated['siswa'] as $siswaId => $data) {
            KehadiranSiswa::updateOrCreate(
                ['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaId],
                $data
            );
        }

        return back()->with('success', 'Kehadiran siswa berhasil disimpan.');
    }
}
