<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KalenderBlokMinggu;
use App\Models\Kelas;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function index(): View
    {
        $kelas = Kelas::with(['waliKelas', 'guruBk'])->withCount('siswaProfiles')->orderBy('nama')->paginate(15)->withQueryString();
        $hasKalender = KalenderBlokMinggu::exists();

        return view('admin.kelas.index', compact('kelas', 'hasKalender'));
    }

    public function show(Kelas $kelas): View
    {
        $kelas->load([
            'waliKelas.guruProfile',
            'guruBk.guruProfile',
            'siswaProfiles.user',
            'jadwalPelajarans.guru.guruProfile',
            'jadwalPelajarans.mataPelajaran',
        ]);

        $jadwalGrouped = $kelas->jadwalPelajarans
            ->sortBy(['hari', 'jam_mulai'])
            ->groupBy('hari');

        $totalSiswa = $kelas->siswaProfiles->count();

        return view('admin.kelas.show', compact('kelas', 'jadwalGrouped', 'totalSiswa'));
    }

    public function create(): View
    {
        $guruList = User::where('role', 'guru')->orderBy('name')->get();

        return view('admin.kelas.create', compact('guruList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'tingkat' => ['required', 'string', 'max:20'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'wali_kelas_id' => ['nullable', 'exists:users,id'],
            'bk_id' => ['nullable', 'exists:users,id'],
            'is_sistem_blok' => ['nullable', 'boolean'],
            'model_rotasi' => ['nullable', 'in:rotasi_minggu,split_harian'],
            'blok_awal' => ['nullable', 'in:kelompok_a,kelompok_b'],
        ]);

        $validated['is_sistem_blok'] = $request->boolean('is_sistem_blok');
        if (! $validated['is_sistem_blok']) {
            $validated['model_rotasi'] = null;
            $validated['blok_awal'] = null;
        }

        Kelas::create($validated);

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function edit(Kelas $kelas): View
    {
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $semuaSiswa = SiswaProfile::with(['user', 'kelas'])->get();

        return view('admin.kelas.edit', compact('kelas', 'guruList', 'semuaSiswa'));
    }

    public function update(Request $request, Kelas $kelas): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'tingkat' => ['required', 'string', 'max:20'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'wali_kelas_id' => ['nullable', 'exists:users,id'],
            'bk_id' => ['nullable', 'exists:users,id'],
            'is_sistem_blok' => ['nullable', 'boolean'],
            'model_rotasi' => ['nullable', 'in:rotasi_minggu,split_harian'],
            'blok_awal' => ['nullable', 'in:kelompok_a,kelompok_b'],
        ]);

        $validated['is_sistem_blok'] = $request->boolean('is_sistem_blok');
        if (! $validated['is_sistem_blok']) {
            $validated['model_rotasi'] = null;
            $validated['blok_awal'] = null;
        }

        $kelas->update($validated);

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        $kelas->delete();

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        if ($request->boolean('delete_all')) {
            $query = Kelas::query();
            // Handle any filters here if added in the future (none currently for kelas index)
            $count = $query->count();
            $query->delete();

            return redirect()->route('admin.kelas.index')->with('success', "Seluruh {$count} kelas berhasil dihapus.");
        }

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:kelas,id'],
        ]);

        Kelas::whereIn('id', $request->input('ids'))->delete();

        return redirect()->route('admin.kelas.index')->with('success', count($request->input('ids')).' kelas berhasil dihapus.');
    }

    /**
     * Toggle aktif/non-aktif sistem blok untuk kelas tertentu.
     */
    public function toggleBlok(Request $request, Kelas $kelas): RedirectResponse
    {
        $newState = ! $kelas->is_sistem_blok;
        $data = ['is_sistem_blok' => $newState];
        if ($newState) {
            $data['model_rotasi'] = $request->input('model_rotasi', $kelas->model_rotasi ?: 'rotasi_minggu');
            $data['blok_awal'] = $request->input('blok_awal', $kelas->blok_awal ?: 'kelompok_a');
        }

        $kelas->update($data);

        $label = ($data['blok_awal'] ?? $kelas->blok_awal) === 'kelompok_b' ? 'Kelompok B (Kejuruan)' : 'Kelompok A (Umum)';
        $status = $newState ? "diaktifkan mulai {$label}" : 'dinonaktifkan';

        return back()->with('success', "Sistem blok untuk kelas {$kelas->nama} berhasil {$status}.");
    }

    /**
     * Update konfigurasi sistem blok (model rotasi & blok awal) untuk kelas.
     */
    public function updateBlok(Request $request, Kelas $kelas): RedirectResponse
    {
        $validated = $request->validate([
            'model_rotasi' => ['nullable', 'in:rotasi_minggu,split_harian'],
            'blok_awal' => ['required', 'in:kelompok_a,kelompok_b'],
        ]);

        $data = [
            'blok_awal' => $validated['blok_awal'],
        ];

        if (! empty($validated['model_rotasi'])) {
            $data['model_rotasi'] = $validated['model_rotasi'];
        }

        $kelas->update($data);

        $label = $validated['blok_awal'] === 'kelompok_b' ? 'Kelompok B (Kejuruan)' : 'Kelompok A (Umum)';

        return back()->with('success', "Blok awal kelas {$kelas->nama} berhasil diubah ke {$label}.");
    }
}
