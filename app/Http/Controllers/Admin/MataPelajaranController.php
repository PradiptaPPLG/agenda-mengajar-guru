<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MataPelajaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MataPelajaranController extends Controller
{
    public function index(Request $request): View
    {
        $query = MataPelajaran::with('kelas')->orderBy('nama');

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%')
                  ->orWhere('kode', 'like', '%' . $request->search . '%');
        }

        if (!$request->has('include_adaptif')) {
            $query->where('jenis', 'normatif');
        }

        $mataPelajarans = $query->paginate(20)->withQueryString();

        return view('admin.mata-pelajaran.index', compact('mataPelajarans'));
    }

    public function create(): View
    {
        $kelasList = \App\Models\Kelas::orderBy('nama')->get();
        return view('admin.mata-pelajaran.create', compact('kelasList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', 'unique:mata_pelajarans'],
            'jenis' => ['required', 'in:normatif,adaptif'],
            'kelas_ids' => ['nullable', 'array'],
            'kelas_ids.*' => ['exists:kelas,id'],
        ]);

        $mataPelajaran = MataPelajaran::create([
            'nama' => $validated['nama'],
            'kode' => $validated['kode'],
            'jenis' => $validated['jenis'],
        ]);

        if ($validated['jenis'] === 'adaptif' && isset($validated['kelas_ids'])) {
            $mataPelajaran->kelas()->sync($validated['kelas_ids']);
        }

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function edit(MataPelajaran $mataPelajaran): View
    {
        $mataPelajaran->load('kelas');
        $kelasList = \App\Models\Kelas::orderBy('nama')->get();
        return view('admin.mata-pelajaran.edit', compact('mataPelajaran', 'kelasList'));
    }

    public function update(Request $request, MataPelajaran $mataPelajaran): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', Rule::unique('mata_pelajarans')->ignore($mataPelajaran->id)],
            'jenis' => ['required', 'in:normatif,adaptif'],
            'kelas_ids' => ['nullable', 'array'],
            'kelas_ids.*' => ['exists:kelas,id'],
        ]);

        $mataPelajaran->update([
            'nama' => $validated['nama'],
            'kode' => $validated['kode'],
            'jenis' => $validated['jenis'],
        ]);

        if ($validated['jenis'] === 'adaptif' && isset($validated['kelas_ids'])) {
            $mataPelajaran->kelas()->sync($validated['kelas_ids']);
        } else {
            $mataPelajaran->kelas()->detach();
        }

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(MataPelajaran $mataPelajaran): RedirectResponse
    {
        $mataPelajaran->delete();

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
