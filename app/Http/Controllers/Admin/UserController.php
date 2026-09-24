<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\SimpleExcel\SimpleExcelReader;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::whereIn('role', ['guru', 'piket'])
            ->with(['guruProfile', 'roles', 'mapels', 'jadwalPelajarans.mataPelajaran', 'jadwalPelajarans.kelas'])
            ->when($request->input('search'), fn ($q, $s) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $kelas = Kelas::orderBy('nama')->get();
        $roles = Role::orderBy('name')->get();
        $mataPelajarans = MataPelajaran::orderBy('nama')->get();
        $daftarJurusan = $this->getDaftarJurusan();

        return view('admin.users.create', compact('kelas', 'roles', 'mataPelajarans', 'daftarJurusan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('role'), ['piket', 'tu'])),
                'email',
                'unique:users,email',
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:guru,siswa,piket,tu'],
            'spatie_roles' => ['nullable', 'array'],
            'spatie_roles.*' => ['exists:roles,name'],
            'nip' => [Rule::requiredIf(fn () => $request->input('role') === 'guru'), 'nullable', 'string', 'max:30'],
            'nis' => [Rule::requiredIf(fn () => $request->input('role') === 'siswa'), 'nullable', 'string', 'max:30'],
            'kelas_id' => ['nullable', 'exists:kelas,id'],
            'wali_kelas_id' => ['nullable', 'exists:kelas,id'],
            'bk_kelas_ids' => ['nullable', 'array'],
            'bk_kelas_ids.*' => ['exists:kelas,id'],
            'kaprog_jurusan' => ['nullable', 'string', 'max:50'],
            'mapel_ids' => ['nullable', 'array'],
            'mapel_ids.*' => ['exists:mata_pelajarans,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        if (! empty($validated['spatie_roles'])) {
            $user->syncRoles($validated['spatie_roles']);
        }

        if ($validated['role'] === 'guru') {
            $hasKaprogRole = collect($validated['spatie_roles'] ?? [])->contains(fn ($r) => str_contains(strtolower($r), 'kaprog'));

            $user->guruProfile()->create([
                'nip' => $validated['nip'] ?? null,
                'kaprog_jurusan' => $hasKaprogRole ? ($validated['kaprog_jurusan'] ?? null) : null,
            ]);

            if (! empty($validated['mapel_ids'])) {
                $user->mapels()->sync($validated['mapel_ids']);
            }

            $hasWaliRole = collect($validated['spatie_roles'] ?? [])->contains(fn ($r) => str_contains(strtolower($r), 'wali'));
            $hasBkRole = collect($validated['spatie_roles'] ?? [])->contains(fn ($r) => str_contains(strtolower($r), 'bk'));

            if ($hasWaliRole && ! empty($validated['wali_kelas_id'])) {
                Kelas::where('id', $validated['wali_kelas_id'])->update(['wali_kelas_id' => $user->id]);
            }
            if ($hasBkRole && ! empty($validated['bk_kelas_ids'])) {
                Kelas::whereIn('id', $validated['bk_kelas_ids'])->update(['bk_id' => $user->id]);
            }
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
        $roles = Role::orderBy('name')->get();
        $mataPelajarans = MataPelajaran::orderBy('nama')->get();
        $daftarJurusan = $this->getDaftarJurusan();

        // Get existing assignments
        $assignedWaliKelas = Kelas::where('wali_kelas_id', $user->id)->first();
        $assignedBkKelas = Kelas::where('bk_id', $user->id)->pluck('id')->toArray();
        $assignedMapels = $user->mapels->pluck('id')->toArray();

        return view('admin.users.edit', compact(
            'user', 'kelas', 'roles', 'mataPelajarans', 'daftarJurusan',
            'assignedWaliKelas', 'assignedBkKelas', 'assignedMapels'
        ));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('role'), ['piket', 'tu'])),
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:guru,siswa,piket,tu'],
            'spatie_roles' => ['nullable', 'array'],
            'spatie_roles.*' => ['exists:roles,name'],
            'nip' => [Rule::requiredIf(fn () => $request->input('role') === 'guru'), 'nullable', 'string', 'max:30'],
            'nis' => [Rule::requiredIf(fn () => $request->input('role') === 'siswa'), 'nullable', 'string', 'max:30'],
            'kelas_id' => ['nullable', 'exists:kelas,id'],
            'wali_kelas_id' => ['nullable', 'exists:kelas,id'],
            'bk_kelas_ids' => ['nullable', 'array'],
            'bk_kelas_ids.*' => ['exists:kelas,id'],
            'kaprog_jurusan' => ['nullable', 'string', 'max:50'],
            'mapel_ids' => ['nullable', 'array'],
            'mapel_ids.*' => ['exists:mata_pelajarans,id'],
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        $user->syncRoles($validated['spatie_roles'] ?? []);

        // Manage Guru specific data
        if ($validated['role'] === 'guru') {
            $hasKaprogRole = collect($validated['spatie_roles'] ?? [])->contains(fn ($r) => str_contains(strtolower($r), 'kaprog'));

            $user->guruProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nip' => $validated['nip'] ?? null,
                    'kaprog_jurusan' => $hasKaprogRole ? ($validated['kaprog_jurusan'] ?? null) : null,
                ]
            );

            $user->mapels()->sync($validated['mapel_ids'] ?? []);

            $hasWaliRole = collect($validated['spatie_roles'] ?? [])->contains(fn ($r) => str_contains(strtolower($r), 'wali'));
            $hasBkRole = collect($validated['spatie_roles'] ?? [])->contains(fn ($r) => str_contains(strtolower($r), 'bk'));

            // Sync Wali Kelas
            Kelas::where('wali_kelas_id', $user->id)->update(['wali_kelas_id' => null]);
            if ($hasWaliRole && ! empty($validated['wali_kelas_id'])) {
                Kelas::where('id', $validated['wali_kelas_id'])->update(['wali_kelas_id' => $user->id]);
            }

            // Sync BK
            Kelas::where('bk_id', $user->id)->update(['bk_id' => null]);
            if ($hasBkRole && ! empty($validated['bk_kelas_ids'])) {
                Kelas::whereIn('id', $validated['bk_kelas_ids'])->update(['bk_id' => $user->id]);
            }
        } elseif ($user->guruProfile) {
            $user->guruProfile()->delete();
            $user->mapels()->detach();
            Kelas::where('wali_kelas_id', $user->id)->update(['wali_kelas_id' => null]);
            Kelas::where('bk_id', $user->id)->update(['bk_id' => null]);
        }

        // Manage Siswa specific data
        if ($validated['role'] === 'siswa') {
            $user->siswaProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nis' => $validated['nis'] ?? null,
                    'kelas_id' => $validated['kelas_id'] ?? null,
                ]
            );
        } elseif ($user->siswaProfile) {
            $user->siswaProfile()->delete();
        }

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        if ($request->boolean('delete_all')) {
            $query = User::whereIn('role', ['guru', 'piket', 'tu']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Mencegah menghapus akun sendiri
            $query->where('id', '!=', auth()->id());

            $count = $query->count();
            $query->delete();

            return redirect()->route('admin.users.index')->with('success', "Seluruh {$count} pengguna berhasil dihapus.");
        }

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:users,id'],
        ]);

        $ids = $request->input('ids');

        // Prevent deleting self
        if (in_array(auth()->id(), $ids)) {
            $ids = array_diff($ids, [auth()->id()]);
            if (empty($ids)) {
                return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun sendiri. Tidak ada akun lain yang dipilih.');
            }
        }

        User::whereIn('id', $ids)->delete();

        return redirect()->route('admin.users.index')->with('success', count($ids).' pengguna berhasil dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $filePath = $request->file('file')->getRealPath();
        $rows = SimpleExcelReader::create($filePath)->getRows();

        $successCount = 0;
        $errorCount = 0;

        foreach ($rows as $row) {
            try {
                $name = $row['nama'] ?? $row['Name'] ?? null;
                $nip = $row['nip'] ?? $row['NIP'] ?? null;
                $email = $row['email'] ?? $row['Email'] ?? null;

                if (! $name) {
                    $errorCount++;

                    continue;
                }

                if (! $email) {
                    $email = strtolower(str_replace(' ', '', $name)).'@sekolah.sch.id';
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password123'),
                    'role' => 'guru',
                ]);

                if ($nip) {
                    $user->guruProfile()->create(['nip' => (string) $nip]);
                }

                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        return redirect()->route('admin.users.index')
            ->with('success', "Import selesai. {$successCount} guru berhasil diimport, {$errorCount} gagal.");
    }

    private function getDaftarJurusan(): array
    {
        $fromKelas = Kelas::all()->map(function ($k) {
            preg_match('/^\d+([A-Za-z]+)/', $k->nama, $matches);

            return isset($matches[1]) ? strtoupper($matches[1]) : null;
        })->filter()->unique()->values()->toArray();

        $default = ['AKL', 'PPLG', 'RPL', 'DKV', 'PM', 'MPLB', 'HTL', 'KLN', 'ITL'];

        return array_values(array_unique(array_merge($default, $fromKelas)));
    }
}
