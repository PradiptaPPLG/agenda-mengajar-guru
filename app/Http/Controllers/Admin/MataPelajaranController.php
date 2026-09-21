<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
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
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                    ->orWhere('kode', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('jenis')) {
            if ($request->jenis === 'umum') {
                $query->whereIn('jenis', ['umum', 'normatif']);
            } elseif ($request->jenis === 'produktif') {
                $query->whereIn('jenis', ['produktif', 'adaptif', 'kejuruan']);
            }
        }

        $mataPelajarans = $query->paginate(20)->withQueryString();

        return view('admin.mata-pelajaran.index', compact('mataPelajarans'));
    }

    public function create(): View
    {
        $kelasList = Kelas::orderBy('nama')->get();

        return view('admin.mata-pelajaran.create', compact('kelasList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', 'unique:mata_pelajarans'],
            'jenis' => ['required', 'in:umum,produktif,normatif,adaptif,kejuruan'],
            'kelas_ids' => ['nullable', 'array'],
            'kelas_ids.*' => ['exists:kelas,id'],
        ]);

        $mataPelajaran = MataPelajaran::create([
            'nama' => $validated['nama'],
            'kode' => $validated['kode'],
            'jenis' => $validated['jenis'],
        ]);

        if (in_array($validated['jenis'], ['produktif', 'adaptif']) && isset($validated['kelas_ids'])) {
            $mataPelajaran->kelas()->sync($validated['kelas_ids']);
        }

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function edit(MataPelajaran $mataPelajaran): View
    {
        $mataPelajaran->load('kelas');
        $kelasList = Kelas::orderBy('nama')->get();

        return view('admin.mata-pelajaran.edit', compact('mataPelajaran', 'kelasList'));
    }

    public function update(Request $request, MataPelajaran $mataPelajaran): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', Rule::unique('mata_pelajarans')->ignore($mataPelajaran->id)],
            'jenis' => ['required', 'in:umum,produktif,normatif,adaptif,kejuruan'],
            'kelas_ids' => ['nullable', 'array'],
            'kelas_ids.*' => ['exists:kelas,id'],
        ]);

        $mataPelajaran->update([
            'nama' => $validated['nama'],
            'kode' => $validated['kode'],
            'jenis' => $validated['jenis'],
        ]);

        if (in_array($validated['jenis'], ['produktif', 'adaptif']) && isset($validated['kelas_ids'])) {
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
