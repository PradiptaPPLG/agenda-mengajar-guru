<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function index(): View
    {
        $kelas = Kelas::with('waliKelas')->withCount('siswaProfiles')->orderBy('nama')->get();

        return view('admin.kelas.index', compact('kelas'));
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
        ]);

        Kelas::create($validated);

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function edit(Kelas $kelas): View
    {
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $semuaSiswa = \App\Models\SiswaProfile::with(['user', 'kelas'])->get();

        return view('admin.kelas.edit', compact('kelas', 'guruList', 'semuaSiswa'));
    }

    public function update(Request $request, Kelas $kelas): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'tingkat' => ['required', 'string', 'max:20'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'wali_kelas_id' => ['nullable', 'exists:users,id'],
        ]);

        $kelas->update($validated);

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        $kelas->delete();

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }
}
