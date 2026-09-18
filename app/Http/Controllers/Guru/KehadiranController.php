<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuru;
use App\Models\Pertemuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KehadiranController extends Controller
{
    /**
     * Store or update teacher attendance for a pertemuan.
     */
    public function store(Request $request, Pertemuan $pertemuan): RedirectResponse
    {
        abort_unless($pertemuan->jadwal->guru_id === Auth::id(), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:hadir,sakit,alpa,dispensasi'],
            'jenis_alpa' => ['nullable', 'required_if:status,alpa', 'in:ada_tugas,tanpa_tugas,guru_pengganti'],
            'guru_pengganti_nama' => ['nullable', 'required_if:jenis_alpa,guru_pengganti', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['guru_id'] = Auth::id();
        $validated['waktu_hadir'] = now();

        KehadiranGuru::updateOrCreate(
            ['pertemuan_id' => $pertemuan->id, 'guru_id' => Auth::id()],
            $validated
        );

        // Update pertemuan status
        $pertemuan->update(['status' => 'berlangsung']);

        return back()->with('success', 'Kehadiran berhasil dicatat.');
    }
}
