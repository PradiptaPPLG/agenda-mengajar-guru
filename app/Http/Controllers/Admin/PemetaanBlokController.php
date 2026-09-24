<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\SiswaProfile;
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
        $kelompokA = $request->input('kelompok_a', []);
        $kelompokB = $request->input('kelompok_b', []);
        $split = $request->input('split', []);
        $siswaSplit = $request->input('siswa', []); // [siswa_id => 'kelompok_a'/'kelompok_b']

        $semuaKelas = Kelas::all();

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
                    'blok_awal' => 'split',
                    'model_rotasi' => 'rotasi_minggu',
                ]);

                // Simpan pengaturan siswa untuk kelas split ini
                // Dapatkan ID siswa di kelas ini
                $siswaIds = SiswaProfile::where('kelas_id', $kelas->id)->pluck('id');
                foreach ($siswaIds as $siswaId) {
                    if (isset($siswaSplit[$siswaId])) {
                        SiswaProfile::where('id', $siswaId)->update([
                            'kelompok_blok' => $siswaSplit[$siswaId],
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
