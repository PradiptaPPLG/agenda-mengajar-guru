<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class PenggunaController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->input('role');

        $users = User::where('role', '!=', 'super_admin')
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($request->input('search'), fn ($q, $s) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pengguna.index', compact('users'));
    }

    public function edit(User $pengguna): View
    {
        if ($pengguna->role === 'super_admin') {
            abort(403, 'Akun Super Admin tidak dapat diedit dari menu ini.');
        }

        $roles = Role::orderBy('name')->get();

        return view('admin.pengguna.edit', ['user' => $pengguna, 'roles' => $roles]);
    }

    public function update(Request $request, User $pengguna): RedirectResponse
    {
        if ($pengguna->role === 'super_admin') {
            abort(403, 'Akun Super Admin tidak dapat diedit dari menu ini.');
        }

        $validated = $request->validate([
            'role' => ['required', 'in:admin,kepala_sekolah,guru,siswa,piket,tu'],
            'spatie_roles' => ['nullable', 'array'],
            'spatie_roles.*' => ['exists:roles,name'],
        ]);

        $pengguna->update([
            'role' => $validated['role'],
        ]);

        $pengguna->syncRoles($validated['spatie_roles'] ?? []);

        return redirect()->route('admin.pengguna.index')->with('success', 'Akses dan role pengguna berhasil diperbarui.');
    }
}
