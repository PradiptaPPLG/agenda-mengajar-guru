<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        $processRow = function (array $rowProperties, &$headerFound, &$nameIndex, &$nisIndex, &$nisnIndex, &$kelasIndex) use (&$imported, $defaultPassword) {
            if (! $headerFound) {
                foreach ($rowProperties as $index => $value) {
                    if (is_string($value)) {
                        $lowerVal = strtolower(trim($value));
                        if (in_array($lowerVal, ['nama lengkap', 'nama', 'name'])) {
                            $nameIndex = $index;
                        } elseif (in_array($lowerVal, ['nis', 'nipd', 'no. induk'])) {
                            $nisIndex = $index;
                        } elseif (in_array($lowerVal, ['nisn'])) {
                            $nisnIndex = $index;
                        } elseif (in_array($lowerVal, ['kelas', 'rombel saat ini', 'rombel'])) {
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

            $kelasName = ($kelasIndex !== -1 && ! empty($rowProperties[$kelasIndex])) ? trim((string) $rowProperties[$kelasIndex]) : null;

            DB::transaction(function () use ($nis, $nama, $defaultPassword, $kelasName, &$imported) {
                $user = null;

                // 1. Cari berdasarkan NIS via SiswaProfile (termasuk trashed)
                if ($nis) {
                    $profile = SiswaProfile::where('nis', $nis)->first();
                    if ($profile) {
                        $user = User::withTrashed()->find($profile->user_id);
                        if ($user) {
                            if ($user->trashed()) {
                                $user->restore();
                            }
                        } else {
                            $profile->delete();
                        }
                    }
                }

                // 2. Jika belum ketemu, cari via nama & role siswa (termasuk trashed)
                if (! $user) {
                    $candidateUser = User::withTrashed()
                        ->where('role', 'siswa')
                        ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($nama))])
                        ->first();

                    if ($candidateUser) {
                        $user = $candidateUser;
                        if ($user->trashed()) {
                            $user->restore();
                        }
                    }
                }

                if ($user) {
                    $user->update([
                        'name' => $nama,
                        'email' => null, // Siswa tidak menggunakan email
                    ]);
                } else {
                    $user = User::create([
                        'name' => $nama,
                        'email' => null, // Siswa tidak menggunakan email
                        'password' => $defaultPassword,
                        'role' => 'siswa',
                        'is_active' => false,
                    ]);
                }

                // Cari ID kelas jika ada di Excel
                $kelasId = null;
                if ($kelasName) {
                    $kelasObj = Kelas::findByNameFlexible($kelasName, true);
                    if ($kelasObj && $kelasObj->trashed()) {
                        $kelasObj->restore();
                    }

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

                // Bersihkan konflik NIS di SiswaProfile jika ada profile lain yang memakai NIS ini
                if ($nis) {
                    $conflictingProfile = SiswaProfile::where('nis', $nis)->where('user_id', '!=', $user->id)->first();
                    if ($conflictingProfile) {
                        $conflictingUser = User::withTrashed()->find($conflictingProfile->user_id);
                        if (! $conflictingUser || $conflictingUser->trashed()) {
                            $conflictingProfile->delete();
                        } else {
                            $conflictingProfile->update(['nis' => null]);
                        }
                    }
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
                $nisIndex = -1;
                $nisnIndex = -1;
                $kelasIndex = -1;
                foreach ($sheet->getRowIterator() as $row) {
                    $rowProperties = [];
                    foreach ($row->getCells() as $cell) {
                        $rowProperties[] = $cell->getValue();
                    }
                    $processRow($rowProperties, $headerFound, $nameIndex, $nisIndex, $nisnIndex, $kelasIndex);
                }
            }
            $spoutReader->close();
        } else {
            // For CSV
            $headerFound = false;
            $nameIndex = -1;
            $nisIndex = -1;
            $nisnIndex = -1;
            $kelasIndex = -1;
            $reader->noHeaderRow()->getRows()->each(function (array $rowProperties) use ($processRow, &$headerFound, &$nameIndex, &$nisIndex, &$nisnIndex, &$kelasIndex) {
                $processRow($rowProperties, $headerFound, $nameIndex, $nisIndex, $nisnIndex, $kelasIndex);
            });
        }

        return redirect()->route('admin.siswa.index')->with('success', "Berhasil mengimpor $imported siswa.");
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'template_siswa_').'.xlsx';
        $writer = SimpleExcelWriter::create($tempPath);

        $writer->addRow([
            'Nama Lengkap' => 'Ahmad Budi',
            'NISN' => '0051234567',
            'NIS' => '21221001',
            'Kelas' => '10 PPLG 1',
        ]);
        $writer->addRow([
            'Nama Lengkap' => 'Siti Aisyah',
            'NISN' => '0067654321',
            'NIS' => '21221002',
            'Kelas' => '10 PPLG 1',
        ]);

        $writer->close();

        return response()->download($tempPath, 'template-import-siswa.xlsx')->deleteFileAfterSend(true);
    }
}
