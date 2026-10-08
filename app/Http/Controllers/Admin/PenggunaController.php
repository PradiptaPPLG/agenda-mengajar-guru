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
use Spatie\Permission\Models\Role;

class PenggunaController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->input('role');
        $jabatan = $request->input('jabatan');

        $query = User::with(['guruProfile', 'roles'])
            ->where('role', '!=', 'siswa');

        if (! auth()->user()->isSuperAdmin()) {
            $query->where('role', '!=', 'super_admin');
        }

        $users = $query
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
                } elseif ($j === 'pengawas') {
                    $q->where(function ($sub) {
                        $sub->where('role', 'pengawas')
                            ->orWhereHas('roles', fn ($r) => $r->where('name', 'like', '%pengawas%'));
                    });
                }
            })
            ->when($request->input('search'), function ($q, $s) {
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhereHas('guruProfile', fn ($gp) => $gp->where('nip', 'like', "%{$s}%")));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pengguna.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.pengguna.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedRoles = ['admin', 'kepala_sekolah', 'pengawas', 'guru', 'piket', 'tu'];
        if (auth()->user()->isSuperAdmin()) {
            $allowedRoles[] = 'super_admin';
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:30', 'unique:guru_profiles,nip'],
            'email' => [
                Rule::requiredIf(fn () => empty($request->input('nip'))),
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', Rule::in($allowedRoles)],
            'spatie_roles' => ['nullable', 'array'],
            'spatie_roles.*' => ['exists:roles,name'],
            'is_active' => ['nullable', 'boolean'],
            'keterangan_jabatan' => ['nullable', 'string', 'max:150'],
        ]);

        $email = $validated['email'] ?? null;
        if (empty($email) && ! empty($validated['nip'])) {
            $cleanNip = preg_replace('/\s+/', '', $validated['nip']);
            $candidateEmail = $cleanNip.'@sekolah.sch.id';
            if (User::where('email', $candidateEmail)->exists()) {
                $candidateEmail = $cleanNip.'_'.substr(uniqid(), -4).'@sekolah.sch.id';
            }
            $email = $candidateEmail;
        }

        $rawPassword = $validated['password'] ?? null;
        if (empty($rawPassword)) {
            if (! empty($validated['nip'])) {
                $rawPassword = preg_replace('/\s+/', '', $validated['nip']);
            } else {
                $rawPassword = 'password123';
            }
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'password' => Hash::make($rawPassword),
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! empty($validated['nip']) || ! empty($validated['keterangan_jabatan']) || in_array($validated['role'], ['guru', 'kepala_sekolah', 'pengawas'])) {
            $user->guruProfile()->create([
                'nip' => $validated['nip'] ?? null,
                'kaprog_jurusan' => $validated['keterangan_jabatan'] ?? null,
            ]);
        }

        if (! empty($validated['spatie_roles'])) {
            $user->syncRoles($validated['spatie_roles']);
        }

        return redirect()->route('admin.pengguna.index')->with('success', "Akun pengguna {$user->name} berhasil ditambahkan.");
    }

    public function edit(User $pengguna): View
    {
        if ($pengguna->role === 'super_admin' && ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Akun Super Admin tidak dapat diedit dari menu ini.');
        }

        $roles = Role::orderBy('name')->get();

        return view('admin.pengguna.edit', [
            'user' => $pengguna->load(['guruProfile', 'roles']),
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $pengguna): RedirectResponse
    {
        if ($pengguna->role === 'super_admin' && ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Akun Super Admin tidak dapat diedit dari menu ini.');
        }

        $allowedRoles = ['admin', 'kepala_sekolah', 'pengawas', 'guru', 'piket', 'tu'];
        if (auth()->user()->isSuperAdmin()) {
            $allowedRoles[] = 'super_admin';
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nip' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('guru_profiles', 'nip')->ignore($pengguna->guruProfile?->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users')->ignore($pengguna->id),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', Rule::in($allowedRoles)],
            'spatie_roles' => ['nullable', 'array'],
            'spatie_roles.*' => ['exists:roles,name'],
            'is_active' => ['nullable', 'boolean'],
            'keterangan_jabatan' => ['nullable', 'string', 'max:150'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
        ];

        if (array_key_exists('email', $validated) && ! empty($validated['email'])) {
            $updateData['email'] = $validated['email'];
        }

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $pengguna->update($updateData);

        if (! empty($validated['nip']) || ! empty($validated['keterangan_jabatan']) || in_array($validated['role'], ['guru', 'kepala_sekolah', 'pengawas'])) {
            $pengguna->guruProfile()->updateOrCreate(
                ['user_id' => $pengguna->id],
                [
                    'nip' => $validated['nip'] ?? null,
                    'kaprog_jurusan' => $validated['keterangan_jabatan'] ?? $pengguna->guruProfile?->kaprog_jurusan,
                ]
            );
        }

        $pengguna->syncRoles($validated['spatie_roles'] ?? []);

        $redirectTo = route('admin.pengguna.index');
        if (! empty($validated['redirect_to'])) {
            $parsed = parse_url($validated['redirect_to']);
            if (empty($parsed['host']) || $parsed['host'] === $request->getHost()) {
                $redirectTo = $validated['redirect_to'];
            }
        }

        return redirect($redirectTo)->with('success', 'Data dan akses pengguna berhasil diperbarui.');
    }

    public function destroy(User $pengguna): RedirectResponse
    {
        if ($pengguna->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($pengguna->role === 'super_admin' && ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Tidak dapat menghapus akun Super Admin.');
        }

        $name = $pengguna->name;
        $pengguna->delete();

        return redirect()->route('admin.pengguna.index')->with('success', "Akun pengguna {$name} berhasil dihapus.");
    }
}
