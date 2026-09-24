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

class KelasSiswaController extends Controller
{
    public function sync(Request $request, Kelas $kelas)
    {
        $request->validate([
            'siswa_ids' => 'array',
            'siswa_ids.*' => 'exists:siswa_profiles,id',
        ]);

        $siswaIds = $request->input('siswa_ids', []);

        // Update the selected students to have this kelas_id atomically
        DB::transaction(function () use ($siswaIds, $kelas) {
            if (count($siswaIds) > 0) {
                SiswaProfile::whereIn('id', $siswaIds)->update(['kelas_id' => $kelas->id]);
            }
        });

        return back()->with('success', count($siswaIds).' Siswa berhasil ditambahkan ke kelas.');
    }

    public function remove(Request $request, Kelas $kelas, SiswaProfile $siswa)
    {
        if ($siswa->kelas_id === $kelas->id) {
            DB::transaction(function () use ($siswa) {
                $siswa->update(['kelas_id' => null]);
            });

            return back()->with('success', 'Siswa berhasil dikeluarkan dari kelas.');
        }

        return back()->with('error', 'Siswa tidak ditemukan di kelas ini.');
    }

    public function import(Request $request, Kelas $kelas)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $path = $request->file('excel_file')->getRealPath();
        $extension = $request->file('excel_file')->getClientOriginalExtension();
        $reader = SimpleExcelReader::create($path, $extension);
        $spoutReader = $reader->getReader();

        $importedCount = 0;

        $defaultPassword = Hash::make('password');
        set_time_limit(300); // Allow up to 5 minutes for large files

        $processRow = function (array $rowProperties, &$headerFound, &$nameIndex, &$nisIndex) use ($kelas, &$importedCount, $defaultPassword) {
            if (! $headerFound) {
                foreach ($rowProperties as $index => $value) {
                    if (is_string($value)) {
                        $lowerVal = strtolower(trim($value));
                        if (in_array($lowerVal, ['nama lengkap', 'nama', 'name'])) {
                            $nameIndex = $index;
                        } elseif (in_array($lowerVal, ['nis'])) {
                            $nisIndex = $index;
                        }
                    }
                }

                if ($nameIndex !== -1) {
                    $headerFound = true;
                }

                return;
            }

            $name = isset($rowProperties[$nameIndex]) ? trim($rowProperties[$nameIndex]) : null;
            if (! $name) {
                return;
            }

            $nis = ($nisIndex !== -1 && isset($rowProperties[$nisIndex])) ? trim($rowProperties[$nisIndex]) : null;

            DB::transaction(function () use ($nis, $name, $defaultPassword, $kelas, &$importedCount) {
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
                        ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($name))])
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
                        'name' => $name,
                        'email' => null, // Siswa tidak menggunakan email
                    ]);
                } else {
                    $user = User::create([
                        'name' => $name,
                        'email' => null, // Siswa tidak menggunakan email
                        'password' => $defaultPassword,
                        'role' => 'siswa',
                    ]);
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

                $profile = SiswaProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    ['nis' => $nis]
                );

                $profile->update([
                    'kelas_id' => $kelas->id,
                    'nis' => $nis ?? $profile->nis,
                ]);

                $importedCount++;
            });
        };

        if (method_exists($spoutReader, 'getSheetIterator')) {
            $spoutReader->open($path);
            foreach ($spoutReader->getSheetIterator() as $sheet) {
                $headerFound = false;
                $nameIndex = -1;
                $nisIndex = -1;
                foreach ($sheet->getRowIterator() as $row) {
                    $rowProperties = [];
                    foreach ($row->getCells() as $cell) {
                        $rowProperties[] = $cell->getValue();
                    }
                    $processRow($rowProperties, $headerFound, $nameIndex, $nisIndex);
                }
            }
            $spoutReader->close();
        } else {
            // For CSV
            $headerFound = false;
            $nameIndex = -1;
            $nisIndex = -1;
            $reader->noHeaderRow()->getRows()->each(function (array $rowProperties) use ($processRow, &$headerFound, &$nameIndex, &$nisIndex) {
                $processRow($rowProperties, $headerFound, $nameIndex, $nisIndex);
            });
        }

        return back()->with('success', "Berhasil mengimpor $importedCount siswa.");
    }

    public function downloadTemplate(Kelas $kelas): BinaryFileResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'template_siswa_kelas_').'.xlsx';
        $writer = SimpleExcelWriter::create($tempPath);

        $writer->addRow([
            'Nama Lengkap' => 'Ahmad Budi',
            'NIS' => '21221001',
        ]);
        $writer->addRow([
            'Nama Lengkap' => 'Siti Aisyah',
            'NIS' => '21221002',
        ]);

        $writer->close();

        return response()->download($tempPath, 'template-import-siswa-kelas.xlsx')->deleteFileAfterSend(true);
    }
}
