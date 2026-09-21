<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuruProfile;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\SimpleExcel\SimpleExcelReader;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->input('role');
        $users = User::when($role, fn ($q) => $q->where('role', $role), fn ($q) => $q->whereIn('role', ['guru', 'piket']))
            ->when($request->input('search'), fn ($q, $s) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
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
            'email' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('role'), ['super_admin', 'admin', 'kepala_sekolah', 'piket'])),
                'email',
                'unique:users,email',
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:super_admin,admin,kepala_sekolah,guru,siswa,piket'],
            'nip' => [Rule::requiredIf(fn () => $request->input('role') === 'guru'), 'nullable', 'string', 'max:30'],
            'nis' => [Rule::requiredIf(fn () => $request->input('role') === 'siswa'), 'nullable', 'string', 'max:30'],
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
            'email' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('role'), ['super_admin', 'admin', 'kepala_sekolah', 'piket'])),
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:super_admin,admin,kepala_sekolah,guru,siswa,piket'],
            'nip' => [Rule::requiredIf(fn () => $request->input('role') === 'guru'), 'nullable', 'string', 'max:30'],
            'nis' => [Rule::requiredIf(fn () => $request->input('role') === 'siswa'), 'nullable', 'string', 'max:30'],
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

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,csv|max:10240',
        ]);

        $file = $request->file('excel_file');
        $path = $file->getRealPath();

        $reader = SimpleExcelReader::create($path, $file->getClientOriginalExtension())->noHeaderRow();

        $imported = 0;
        $headerFound = false;
        $nameIndex = -1;
        $emailIndex = -1;
        $nipIndex = -1;

        $defaultPassword = Hash::make('password');
        set_time_limit(300);

        $reader->getRows()->each(function (array $rowProperties) use (&$imported, &$headerFound, &$nameIndex, &$emailIndex, &$nipIndex, $defaultPassword) {
            if (! $headerFound) {
                foreach ($rowProperties as $index => $value) {
                    $val = strtolower(trim((string) $value));
                    if (str_contains($val, 'nama')) {
                        $nameIndex = $index;
                    }
                    if (str_contains($val, 'email')) {
                        $emailIndex = $index;
                    }
                    if (str_contains($val, 'nip') || str_contains($val, 'n i p')) {
                        $nipIndex = $index;
                    }
                }
                if ($nameIndex !== -1) {
                    $headerFound = true;
                }

                return;
            }

            $nama = isset($rowProperties[$nameIndex]) ? trim($rowProperties[$nameIndex]) : null;
            if (! $nama) {
                return;
            }

            $nip = ($nipIndex !== -1 && isset($rowProperties[$nipIndex])) ? trim($rowProperties[$nipIndex]) : null;

            $email = null;
            if ($emailIndex !== -1 && ! empty($rowProperties[$emailIndex])) {
                $email = trim($rowProperties[$emailIndex]);
            }

            if (! $email) {
                $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($nama));
                $email = $cleanName.'.'.rand(1000, 9999).'@guru.sekolah.sch.id';
            }

            // Cek apakah guru sudah ada (berdasarkan NIP, Email, atau Nama)
            $existingUser = null;
            if ($nip) {
                $existingProfile = GuruProfile::where('nip', $nip)->first();
                if ($existingProfile) {
                    $existingUser = $existingProfile->user;
                }
            }
            if (! $existingUser && $email) {
                $existingUser = User::where('email', $email)->first();
            }
            if (! $existingUser) {
                $existingUser = User::where('name', $nama)->where('role', 'guru')->first();
            }

            if (! $existingUser) {
                $user = User::create([
                    'name' => $nama,
                    'email' => $email,
                    'password' => $defaultPassword,
                    'role' => 'guru',
                ]);

                GuruProfile::create([
                    'user_id' => $user->id,
                    'nip' => $nip,
                ]);

                $imported++;
            }
        });

        return redirect()->route('admin.users.index')->with('success', "Berhasil mengimpor {$imported} guru baru.");
    }
}
