<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
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
        $jabatan = $request->input('jabatan');

        $users = User::whereNotIn('role', ['super_admin', 'siswa'])
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($jabatan, function ($q, $j) {
                if ($j === 'kaprog') {
                    $q->where(function ($sub) {
                        $sub->whereHas('roles', fn ($r) => $r->where('name', 'like', '%kaprog%'))
                            ->orWhereHas('guruProfile', fn ($gp) => $gp->whereNotNull('kaprog_jurusan'));
                    });
                } elseif ($j === 'bk') {
                    $q->where(function ($sub) {
                        $sub->whereHas('roles', fn ($r) => $r->where('name', 'like', '%bk%'))
                            ->orWhereIn('id', Kelas::whereNotNull('bk_id')->pluck('bk_id'));
                    });
                } elseif ($j === 'wali_kelas') {
                    $q->where(function ($sub) {
                        $sub->whereHas('roles', fn ($r) => $r->where('name', 'like', '%wali%'))
                            ->orWhereIn('id', Kelas::whereNotNull('wali_kelas_id')->pluck('wali_kelas_id'));
                    });
                } elseif ($j === 'piket') {
                    $q->where(function ($sub) {
                        $sub->where('role', 'piket')
                            ->orWhereHas('roles', fn ($r) => $r->where('name', 'like', '%piket%'));
                    });
                }
            })
            ->when($request->input('search'), fn ($q, $s) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhereHas('guruProfile', fn ($gp) => $gp->where('nip', 'like', "%{$s}%"))))
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
            'role' => ['required', 'in:admin,kepala_sekolah,guru,piket,tu'],
            'spatie_roles' => ['nullable', 'array'],
            'spatie_roles.*' => ['exists:roles,name'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        $pengguna->update([
            'role' => $validated['role'],
        ]);

        $pengguna->syncRoles($validated['spatie_roles'] ?? []);

        $redirectTo = route('admin.pengguna.index');
        if (! empty($validated['redirect_to'])) {
            $parsed = parse_url($validated['redirect_to']);
            // Pastikan URL internal (tanpa host asing atau host sama dengan request host)
            if (empty($parsed['host']) || $parsed['host'] === $request->getHost()) {
                $redirectTo = $validated['redirect_to'];
            }
        }

        return redirect($redirectTo)->with('success', 'Akses dan role pengguna berhasil diperbarui.');
    }
}
