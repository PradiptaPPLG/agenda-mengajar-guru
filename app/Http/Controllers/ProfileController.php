<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isGuruBk = $user->isGuruBk();
        $allKelas = $isGuruBk ? Kelas::with('guruBk')->orderBy('tingkat')->orderBy('nama')->get() : collect();
        $assignedBkKelasIds = $isGuruBk ? Kelas::where('bk_id', $user->id)->pluck('id')->toArray() : [];

        return view('profile.index', compact('user', 'isGuruBk', 'allKelas', 'assignedBkKelasIds'));
    }

    public function updateBkKelas(Request $request)
    {
        $user = auth()->user();
        if (! $user->isGuruBk()) {
            return back()->with('error', 'Anda tidak memiliki hak akses sebagai Guru BK.');
        }

        $validated = $request->validate([
            'bk_kelas_ids' => ['nullable', 'array'],
            'bk_kelas_ids.*' => ['integer', 'exists:kelas,id'],
        ]);

        $selectedIds = $validated['bk_kelas_ids'] ?? [];

        // Lepas kelas yang sebelumnya dibina oleh guru ini tapi sekarang di-uncheck
        Kelas::where('bk_id', $user->id)
            ->whereNotIn('id', $selectedIds)
            ->update(['bk_id' => null]);

        // Tetapkan kelas yang baru dicentang ke guru ini
        if (! empty($selectedIds)) {
            Kelas::whereIn('id', $selectedIds)->update(['bk_id' => $user->id]);
        }

        return back()->with('success', 'Daftar kelas binaan BK berhasil diperbarui ('.count($selectedIds).' kelas dibina).');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password berhasil diperbarui.');
    }
}
