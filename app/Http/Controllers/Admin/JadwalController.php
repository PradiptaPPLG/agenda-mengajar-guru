<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalController extends Controller
{
    public function index(Request $request): View
    {
        $jadwals = JadwalPelajaran::with(['kelas', 'guru', 'mataPelajaran'])
            ->when($request->input('kelas_id'), fn ($q, $id) => $q->where('kelas_id', $id))
            ->when($request->input('guru_id'), fn ($q, $id) => $q->where('guru_id', $id))
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->paginate(30)
            ->withQueryString();

        $kelasList = Kelas::orderBy('nama')->get();
        $guruList = User::where('role', 'guru')->orderBy('name')->get();

        return view('admin.jadwal.index', compact('jadwals', 'kelasList', 'guruList'));
    }

    public function create(): View
    {
        $kelasList = Kelas::orderBy('nama')->get();
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $mataPelajarans = MataPelajaran::orderBy('nama')->get();

        return view('admin.jadwal.create', compact('kelasList', 'guruList', 'mataPelajarans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'guru_id' => ['required', 'exists:users,id'],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajarans,id'],
            'hari' => ['required', 'integer', 'between:1,6'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
        ]);

        JadwalPelajaran::create($validated);

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(JadwalPelajaran $jadwal): View
    {
        $kelasList = Kelas::orderBy('nama')->get();
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $mataPelajarans = MataPelajaran::orderBy('nama')->get();

        return view('admin.jadwal.edit', compact('jadwal', 'kelasList', 'guruList', 'mataPelajarans'));
    }

    public function update(Request $request, JadwalPelajaran $jadwal): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'guru_id' => ['required', 'exists:users,id'],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajarans,id'],
            'hari' => ['required', 'integer', 'between:1,6'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
        ]);

        $jadwal->update($validated);

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(JadwalPelajaran $jadwal): RedirectResponse
    {
        $jadwal->delete();

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal berhasil dihapus.');
    }
}
