<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::when($request->input('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->input('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $kelas = Kelas::orderBy('nama')->get();

        return view('admin.users.create', compact('kelas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:super_admin,admin,kepala_sekolah,guru,siswa'],
            'nip' => ['nullable', 'string', 'max:30'],
            'nis' => ['nullable', 'string', 'max:30'],
            'kelas_id' => ['nullable', 'exists:kelas,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        if ($validated['role'] === 'guru') {
            $user->guruProfile()->create(['nip' => $validated['nip'] ?? null]);
        }

        if ($validated['role'] === 'siswa') {
            $user->siswaProfile()->create([
                'nis' => $validated['nis'] ?? null,
                'kelas_id' => $validated['kelas_id'] ?? null,
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        $kelas = Kelas::orderBy('nama')->get();

        return view('admin.users.edit', compact('user', 'kelas'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:super_admin,admin,kepala_sekolah,guru,siswa'],
            'nip' => ['nullable', 'string', 'max:30'],
            'nis' => ['nullable', 'string', 'max:30'],
            'kelas_id' => ['nullable', 'exists:kelas,id'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        if ($validated['password']) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        if ($validated['role'] === 'guru') {
            $user->guruProfile()->updateOrCreate(['user_id' => $user->id], ['nip' => $validated['nip'] ?? null]);
        }

        if ($validated['role'] === 'siswa') {
            $user->siswaProfile()->updateOrCreate(
                ['user_id' => $user->id],
                ['nis' => $validated['nis'] ?? null, 'kelas_id' => $validated['kelas_id'] ?? null]
            );
        }

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
