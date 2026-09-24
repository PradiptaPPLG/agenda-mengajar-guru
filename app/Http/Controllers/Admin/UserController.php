<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuruProfile;
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
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            'excel_file' => ['nullable', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'file' => ['nullable', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $file = $request->file('excel_file') ?? $request->file('file');
        if (! $file) {
            return redirect()->back()->with('error', 'File Excel wajib diunggah.');
        }

        $filePath = $file->getRealPath();
        $reader = SimpleExcelReader::create($filePath, $file->getClientOriginalExtension());
        $spoutReader = $reader->getReader();

        $successCount = 0;
        $updatedCount = 0;
        $errorCount = 0;
        $defaultPassword = Hash::make('password123');

        $processRow = function (array $rowProperties, &$headerFound, &$nameIndex, &$nipIndex, &$emailIndex, &$mapelIndex, &$kelasIndex) use (
            &$successCount, &$updatedCount, &$errorCount, $defaultPassword
        ) {
            if (! $headerFound) {
                foreach ($rowProperties as $index => $value) {
                    if (is_string($value)) {
                        $lowerVal = trim(preg_replace('/\s+/', ' ', strtolower($value)));
                        if (in_array($lowerVal, ['nama guru', 'nama lengkap', 'nama', 'name', 'guru'])) {
                            $nameIndex = $index;
                        } elseif (in_array($lowerVal, ['nip', 'nomor induk pegawai', 'no induk pegawai', 'no. induk pegawai'])) {
                            $nipIndex = $index;
                        } elseif (in_array($lowerVal, ['email', 'alamat email', 'surel'])) {
                            $emailIndex = $index;
                        } elseif (in_array($lowerVal, ['mapel yang diampu', 'mapel', 'mata pelajaran', 'mengajar', 'daftar mapel', 'mapel diampu'])) {
                            $mapelIndex = $index;
                        } elseif (in_array($lowerVal, ['kelas', 'kelas yang diajar', 'kelas mengajar', 'daftar kelas'])) {
                            $kelasIndex = $index;
                        }
                    }
                }
                if ($nameIndex !== -1) {
                    $headerFound = true;
                }

                return;
            }

            $name = isset($rowProperties[$nameIndex]) ? trim((string) $rowProperties[$nameIndex]) : null;
            if (! $name || $name === '-' || in_array(strtolower($name), ['nama guru', 'nama', 'nama lengkap'])) {
                return;
            }

            $nip = ($nipIndex !== -1 && isset($rowProperties[$nipIndex])) ? trim((string) $rowProperties[$nipIndex]) : null;
            if ($nip === '' || $nip === '-') {
                $nip = null;
            }
            $email = ($emailIndex !== -1 && isset($rowProperties[$emailIndex])) ? trim((string) $rowProperties[$emailIndex]) : null;
            $mapel = ($mapelIndex !== -1 && isset($rowProperties[$mapelIndex])) ? trim((string) $rowProperties[$mapelIndex]) : null;
            $kelasMengajar = ($kelasIndex !== -1 && isset($rowProperties[$kelasIndex])) ? trim((string) $rowProperties[$kelasIndex]) : null;

            try {
                $user = $this->findMatchingGuru($name, $nip, $email);

                if (! $user) {
                    if (! $email) {
                        $cleanPrefix = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
                        if ($cleanPrefix === '') {
                            $cleanPrefix = 'guru';
                        }
                        $email = $cleanPrefix.'@sekolah.sch.id';
                    }

                    if (User::withTrashed()->where('email', $email)->exists()) {
                        $cleanPrefix = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
                        $email = ($cleanPrefix ?: 'guru').rand(10, 999).'@sekolah.sch.id';
                    }

                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => $defaultPassword,
                        'role' => 'guru',
                    ]);

                    $nipToSave = null;
                    if ($nip && $nip !== '-') {
                        $nipTaken = GuruProfile::where('nip', $nip)->exists();
                        if (! $nipTaken) {
                            $nipToSave = $nip;
                        }
                    }

                    $user->guruProfile()->create([
                        'nip' => $nipToSave,
                    ]);
                    $successCount++;
                } else {
                    // Update NIP if provided and not yet filled or updated
                    if ($nip) {
                        $conflict = GuruProfile::where('nip', $nip)->where('user_id', '!=', $user->id)->first();
                        if (! $conflict) {
                            $user->guruProfile()->updateOrCreate(
                                ['user_id' => $user->id],
                                ['nip' => $nip]
                            );
                        }
                    } elseif (! $user->guruProfile) {
                        $user->guruProfile()->create();
                    }
                    $updatedCount++;
                }

                // Process Mapels
                $mapelModels = [];
                if ($mapel) {
                    $mapelNames = array_map('trim', explode(',', $mapel));
                    foreach ($mapelNames as $mName) {
                        if ($mName === '') {
                            continue;
                        }
                        $mModel = $this->findOrCreateMapel($mName);
                        if ($mModel) {
                            $mapelModels[] = $mModel;
                        }
                    }
                    if (! empty($mapelModels)) {
                        $user->mapels()->syncWithoutDetaching(array_column($mapelModels, 'id'));
                    }
                }

                // Ensure taught classes are linked/created if specified
                if ($kelasMengajar) {
                    $kelasNames = array_map('trim', explode(',', $kelasMengajar));
                    foreach ($kelasNames as $kName) {
                        if ($kName === '') {
                            continue;
                        }
                        $this->findOrCreateKelas($kName);
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
            }
        };

        if (method_exists($spoutReader, 'getSheetIterator')) {
            $spoutReader->open($filePath);
            foreach ($spoutReader->getSheetIterator() as $sheet) {
                $headerFound = false;
                $nameIndex = $nipIndex = $emailIndex = $mapelIndex = $kelasIndex = -1;

                foreach ($sheet->getRowIterator() as $row) {
                    $rowProperties = [];
                    foreach ($row->getCells() as $cell) {
                        $rowProperties[] = $cell->getValue();
                    }
                    $processRow($rowProperties, $headerFound, $nameIndex, $nipIndex, $emailIndex, $mapelIndex, $kelasIndex);
                }
            }
            $spoutReader->close();
        } else {
            $headerFound = false;
            $nameIndex = $nipIndex = $emailIndex = $mapelIndex = $kelasIndex = -1;
            $reader->noHeaderRow()->getRows()->each(function (array $rowProperties) use ($processRow, &$headerFound, &$nameIndex, &$nipIndex, &$emailIndex, &$mapelIndex, &$kelasIndex) {
                $processRow($rowProperties, $headerFound, $nameIndex, $nipIndex, $emailIndex, $mapelIndex, $kelasIndex);
            });
        }

        $message = "Import selesai. {$successCount} guru baru ditambahkan";
        if ($updatedCount > 0) {
            $message .= ", {$updatedCount} data guru diperbarui";
        }
        $message .= '.';
        if ($errorCount > 0) {
            $message .= " ({$errorCount} baris tidak valid dilewati).";
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    private function findMatchingGuru(string $name, ?string $nip = null, ?string $email = null): ?User
    {
        return User::findMatchingGuru($name, $nip, $email);
    }

    private function findOrCreateMapel(string $mName): ?MataPelajaran
    {
        $mName = trim($mName);
        if ($mName === '') {
            return null;
        }

        $m = MataPelajaran::withTrashed()
            ->where('kode', $mName)
            ->orWhere('nama', $mName)
            ->first();

        if (! $m) {
            $cleanLookup = strtolower(str_replace(' ', '', $mName));
            $m = MataPelajaran::withTrashed()
                ->whereRaw("REPLACE(LOWER(kode), ' ', '') = ?", [$cleanLookup])
                ->orWhereRaw("REPLACE(LOWER(nama), ' ', '') = ?", [$cleanLookup])
                ->first();
        }

        if ($m) {
            if ($m->trashed()) {
                $m->restore();
            }

            return $m;
        }

        $cleanCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $mName), 0, 10));
        if ($cleanCode === '') {
            $cleanCode = 'MAPEL-'.rand(100, 999);
        }

        if (MataPelajaran::withTrashed()->where('kode', $cleanCode)->exists()) {
            $cleanCode = substr($cleanCode, 0, 6).'-'.rand(10, 99);
        }

        return MataPelajaran::create([
            'nama' => strtoupper($mName),
            'kode' => $cleanCode,
            'jenis' => (str_contains(strtoupper($mName), 'KK') || str_contains(strtoupper($mName), 'DPK')) ? 'kejuruan' : 'umum',
        ]);
    }

    private function findOrCreateKelas(string $kName): ?Kelas
    {
        $kName = trim($kName);
        if ($kName === '') {
            return null;
        }

        $k = Kelas::findByNameFlexible($kName, true);

        if ($k) {
            if ($k->trashed()) {
                $k->restore();
            }

            return $k;
        }

        preg_match('/^(\d+)/', $kName, $mGrade);
        $tingkat = $mGrade[1] ?? '10';

        return Kelas::create([
            'nama' => $kName,
            'tingkat' => $tingkat,
            'tahun_ajaran' => date('Y').'/'.(date('Y') + 1),
        ]);
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'template_guru_').'.xlsx';
        $writer = SimpleExcelWriter::create($tempPath);

        $writer->addRow([
            'Nama Guru' => 'Budi Santoso, S.Pd.',
            'NIP' => '198001012010011001',
            'Email' => 'budi@sekolah.sch.id',
            'Mapel yang diampu' => 'MAT, INGG',
            'Kelas' => '10 PPLG, 11 RPL',
        ]);

        $writer->close();

        return response()->download($tempPath, 'template-import-guru.xlsx')->deleteFileAfterSend(true);
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
