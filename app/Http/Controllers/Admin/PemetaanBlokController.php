<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PemetaanBlokController extends Controller
{
    public function index(): View
    {
        // Ambil semua kelas beserta siswanya untuk UI baru
        $kelas = Kelas::with(['siswaProfiles.user'])->orderBy('tingkat')->orderBy('nama')->get();

        return view('admin.pemetaan-blok.index', compact('kelas'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'kelompok_a' => ['nullable', 'array'],
            'kelompok_a.*' => ['integer', 'exists:kelas,id'],
            'kelompok_b' => ['nullable', 'array'],
            'kelompok_b.*' => ['integer', 'exists:kelas,id'],
            'split' => ['nullable', 'array'],
            'split.*' => ['integer', 'exists:kelas,id'],
            'siswa' => ['nullable', 'array'],
            'siswa.*' => ['in:kelompok_a,kelompok_b'],
        ]);

        $kelompokA = $request->input('kelompok_a', []);
        $kelompokB = $request->input('kelompok_b', []);
        $split = $request->input('split', []);
        $siswaSplit = $request->input('siswa', []); // [siswa_id => 'kelompok_a'/'kelompok_b']

        // Preload semua kelas beserta siswaProfiles sekaligus untuk menghindari N+1
        $semuaKelas = Kelas::with('siswaProfiles')->get();

        foreach ($semuaKelas as $kelas) {
            if (in_array($kelas->id, $kelompokA)) {
                $kelas->update([
                    'is_sistem_blok' => true,
                    'blok_awal' => 'kelompok_a',
                    'model_rotasi' => 'rotasi_minggu',
                ]);
            } elseif (in_array($kelas->id, $kelompokB)) {
                $kelas->update([
                    'is_sistem_blok' => true,
                    'blok_awal' => 'kelompok_b',
                    'model_rotasi' => 'rotasi_minggu',
                ]);
            } elseif (in_array($kelas->id, $split)) {
                $kelas->update([
                    'is_sistem_blok' => true,
                    'blok_awal' => null, // split_harian tidak memiliki kelompok awal (A & B berjalan bersamaan)
                    'model_rotasi' => 'split_harian',
                ]);

                // Simpan pengaturan kelompok blok untuk setiap siswa di kelas split
                foreach ($kelas->siswaProfiles as $siswa) {
                    if (isset($siswaSplit[$siswa->id])) {
                        $siswa->update([
                            'kelompok_blok' => $siswaSplit[$siswa->id],
                        ]);
                    }
                }
            } else {
                // Kelas yang tidak diceklis di mana pun jadi reguler
                $kelas->update([
                    'is_sistem_blok' => false,
                    'blok_awal' => null,
                    'model_rotasi' => null,
                ]);
            }
        }

        return redirect()->route('admin.pemetaan-blok.index')->with('success', 'Pemetaan blok berhasil disimpan.');
    }
}
