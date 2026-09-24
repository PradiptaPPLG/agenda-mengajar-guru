<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;

class SiswaController extends Controller
{
    public function toggleActive(User $user)
    {
        if ($user->role !== 'siswa') {
            abort(403);
        }
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', 'Status siswa berhasil diperbarui.');
    }

    public function deactivateAll()
    {
        DB::transaction(function () {
            User::where('role', 'siswa')->update(['is_active' => false]);
        });

        return back()->with('success', 'Seluruh akun siswa berhasil dinonaktifkan.');
    }

    public function bulkDestroy(Request $request)
    {
        if ($request->boolean('delete_all')) {
            $query = User::where('role', 'siswa');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('siswaProfile', function ($sq) use ($search) {
                            $sq->where('nis', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->filled('kelas_id')) {
                if ($request->kelas_id === 'null') {
                    $query->whereHas('siswaProfile', function ($sq) {
                        $sq->whereNull('kelas_id');
                    });
                } else {
                    $query->whereHas('siswaProfile', function ($sq) use ($request) {
                        $sq->where('kelas_id', $request->kelas_id);
                    });
                }
            }

            $count = $query->count();
            $query->delete();

            return redirect()->route('admin.siswa.index')->with('success', "Seluruh {$count} siswa berhasil dihapus.");
        }

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:users,id'],
        ]);

        User::whereIn('id', $request->input('ids'))->where('role', 'siswa')->delete();

        return redirect()->route('admin.siswa.index')->with('success', count($request->input('ids')).' siswa berhasil dihapus.');
    }

    public function index(Request $request)
    {
        $query = SiswaProfile::with(['user', 'kelas'])->whereHas('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhere('nis', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kelas_id')) {
            if ($request->kelas_id === 'null') {
                $query->whereNull('kelas_id');
            } else {
                $query->where('kelas_id', $request->kelas_id);
            }
        }

        $siswa = $query->paginate(20)->withQueryString();
        $kelasList = Kelas::orderBy('nama')->get();

        return view('admin.siswa.index', compact('siswa', 'kelasList'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('excel_file');
        $path = $file->getRealPath();

        $reader = SimpleExcelReader::create($path, $file->getClientOriginalExtension());
        $spoutReader = $reader->getReader();

        $imported = 0;
        $defaultPassword = Hash::make('password');
        set_time_limit(300); // Allow up to 5 minutes for large files

        $processRow = function (array $rowProperties, &$headerFound, &$nameIndex, &$emailIndex, &$nisIndex, &$nisnIndex, &$kelasIndex) use (&$imported, $defaultPassword) {
            if (! $headerFound) {
                foreach ($rowProperties as $index => $value) {
                    if (is_string($value)) {
                        $lowerVal = strtolower(trim($value));
                        if (in_array($lowerVal, ['nama lengkap', 'nama', 'name'])) {
                            $nameIndex = $index;
                        } elseif (in_array($lowerVal, ['alamat email', 'email'])) {
                            $emailIndex = $index;
                        } elseif (in_array($lowerVal, ['nis'])) {
                            $nisIndex = $index;
                        } elseif (in_array($lowerVal, ['nisn'])) {
                            $nisnIndex = $index;
                        } elseif (in_array($lowerVal, ['kelas'])) {
                            $kelasIndex = $index;
                        }
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

            $nis = null;
            if ($nisIndex !== -1 && ! empty($rowProperties[$nisIndex])) {
                $nis = trim($rowProperties[$nisIndex]);
            } elseif ($nisnIndex !== -1 && ! empty($rowProperties[$nisnIndex])) {
                $nis = trim($rowProperties[$nisnIndex]);
            }

            $providedEmail = ($emailIndex !== -1 && ! empty($rowProperties[$emailIndex])) ? trim($rowProperties[$emailIndex]) : null;
            $kelasName = ($kelasIndex !== -1 && ! empty($rowProperties[$kelasIndex])) ? trim($rowProperties[$kelasIndex]) : null;

            DB::transaction(function () use ($nis, $providedEmail, $nama, $defaultPassword, $kelasName, &$imported) {
                $user = null;

                if ($nis) {
                    $profile = SiswaProfile::where('nis', $nis)->first();
                    if ($profile) {
                        $user = $profile->user;
                    }
                }

                if (! $user && $providedEmail) {
                    $user = User::where('email', $providedEmail)->first();
                }

                if (! $user) {
                    $email = $providedEmail;
                    if (! $email) {
                        $cleanName = Str::slug($nama, '');
                        $email = "{$cleanName}.".($nis ?: Str::random(4)).'@siswa.sekolah.sch.id';
                    }

                    $existingEmail = User::where('email', $email)->first();
                    if ($existingEmail) {
                        $email = "{$cleanName}.".Str::random(5).'@siswa.sekolah.sch.id';
                    }

                    $user = User::create([
                        'name' => $nama,
                        'email' => $email,
                        'password' => $defaultPassword,
                        'role' => 'siswa',
                        'is_active' => false,
                    ]);
                }

                // Cari ID kelas jika ada di Excel
                $kelasId = null;
                if ($kelasName) {
                    $kelasObj = Kelas::where('nama', 'LIKE', $kelasName)->first();

                    // Otomatis buat kelas jika belum ada di database
                    if (! $kelasObj) {
                        $tingkat = '10';
                        if (preg_match('/^12|xii/i', $kelasName)) {
                            $tingkat = '12';
                        } elseif (preg_match('/^11|xi/i', $kelasName)) {
                            $tingkat = '11';
                        }

                        $kelasObj = Kelas::create([
                            'nama' => $kelasName,
                            'tingkat' => $tingkat,
                            'tahun_ajaran' => date('Y').'/'.(date('Y') + 1),
                        ]);
                    }

                    $kelasId = $kelasObj->id;
                }

                SiswaProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nis' => $nis,
                        'kelas_id' => $kelasId,
                    ]
                );

                $imported++;
            });
        };

        if (method_exists($spoutReader, 'getSheetIterator')) {
            $spoutReader->open($path);
            foreach ($spoutReader->getSheetIterator() as $sheet) {
                $headerFound = false;
                $nameIndex = -1;
                $emailIndex = -1;
                $nisIndex = -1;
                $nisnIndex = -1;
                $kelasIndex = -1;
                foreach ($sheet->getRowIterator() as $row) {
                    $rowProperties = [];
                    foreach ($row->getCells() as $cell) {
                        $rowProperties[] = $cell->getValue();
                    }
                    $processRow($rowProperties, $headerFound, $nameIndex, $emailIndex, $nisIndex, $nisnIndex, $kelasIndex);
                }
            }
            $spoutReader->close();
        } else {
            // For CSV
            $headerFound = false;
            $nameIndex = -1;
            $emailIndex = -1;
            $nisIndex = -1;
            $nisnIndex = -1;
            $kelasIndex = -1;
            $reader->noHeaderRow()->getRows()->each(function (array $rowProperties) use ($processRow, &$headerFound, &$nameIndex, &$emailIndex, &$nisIndex, &$nisnIndex, &$kelasIndex) {
                $processRow($rowProperties, $headerFound, $nameIndex, $emailIndex, $nisIndex, $nisnIndex, $kelasIndex);
            });
        }

        return redirect()->route('admin.siswa.index')->with('success', "Berhasil mengimpor $imported siswa.");
    }
}
