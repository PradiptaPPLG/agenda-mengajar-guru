<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;

class KelasSiswaController extends Controller
{
    public function sync(Request $request, Kelas $kelas)
    {
        $request->validate([
            'siswa_ids' => 'array',
            'siswa_ids.*' => 'exists:siswa_profiles,id',
        ]);

        $siswaIds = $request->input('siswa_ids', []);
        
        // Update the selected students to have this kelas_id
        if (count($siswaIds) > 0) {
            SiswaProfile::whereIn('id', $siswaIds)->update(['kelas_id' => $kelas->id]);
        }

        return back()->with('success', count($siswaIds) . ' Siswa berhasil ditambahkan ke kelas.');
    }

    public function remove(Request $request, Kelas $kelas, SiswaProfile $siswa)
    {
        if ($siswa->kelas_id === $kelas->id) {
            $siswa->update(['kelas_id' => null]);
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

        $processRow = function(array $rowProperties, &$headerFound, &$nameIndex, &$emailIndex, &$nisIndex) use ($kelas, &$importedCount, $defaultPassword) {
            if (!$headerFound) {
                foreach ($rowProperties as $index => $value) {
                    if (is_string($value)) {
                        $lowerVal = strtolower(trim($value));
                        if (in_array($lowerVal, ['nama lengkap', 'nama', 'name'])) $nameIndex = $index;
                        elseif (in_array($lowerVal, ['alamat email', 'email'])) $emailIndex = $index;
                        elseif (in_array($lowerVal, ['nis'])) $nisIndex = $index;
                    }
                }
                
                if ($nameIndex !== -1) {
                    $headerFound = true;
                }
                return;
            }

            $name = isset($rowProperties[$nameIndex]) ? trim($rowProperties[$nameIndex]) : null;
            if (!$name) return;

            $nis = ($nisIndex !== -1 && isset($rowProperties[$nisIndex])) ? trim($rowProperties[$nisIndex]) : null;
            $providedEmail = ($emailIndex !== -1 && !empty($rowProperties[$emailIndex])) ? trim($rowProperties[$emailIndex]) : null;

            $user = null;

            if ($nis) {
                $profile = SiswaProfile::where('nis', $nis)->first();
                if ($profile) $user = $profile->user;
            }

            if (!$user && $providedEmail) {
                $user = User::where('email', $providedEmail)->first();
            }

            if (!$user) {
                $email = $providedEmail;
                if (!$email) {
                    $cleanName = Str::slug($name, '');
                    $email = "{$cleanName}." . ($nis ?: Str::random(4)) . "@smkn1ciamis.id";
                }

                $existingEmail = User::where('email', $email)->first();
                if ($existingEmail) {
                    $email = "{$cleanName}." . Str::random(5) . "@smkn1ciamis.id";
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $defaultPassword,
                    'role' => 'siswa',
                ]);
            }

            $profile = SiswaProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['nis' => $nis]
            );

            $profile->update([
                'kelas_id' => $kelas->id,
                'nis' => $nis ?? $profile->nis
            ]);

            $importedCount++;
        };

        if (method_exists($spoutReader, 'getSheetIterator')) {
            $spoutReader->open($path);
            foreach ($spoutReader->getSheetIterator() as $sheet) {
                $headerFound = false;
                $nameIndex = -1;
                $emailIndex = -1;
                $nisIndex = -1;
                foreach ($sheet->getRowIterator() as $row) {
                    $rowProperties = [];
                    foreach ($row->getCells() as $cell) {
                        $rowProperties[] = $cell->getValue();
                    }
                    $processRow($rowProperties, $headerFound, $nameIndex, $emailIndex, $nisIndex);
                }
            }
            $spoutReader->close();
        } else {
            // For CSV
            $headerFound = false;
            $nameIndex = -1;
            $emailIndex = -1;
            $nisIndex = -1;
            $reader->noHeaderRow()->getRows()->each(function (array $rowProperties) use ($processRow, &$headerFound, &$nameIndex, &$emailIndex, &$nisIndex) {
                $processRow($rowProperties, $headerFound, $nameIndex, $emailIndex, $nisIndex);
            });
        }

        return back()->with('success', "Berhasil mengimpor $importedCount siswa.");
    }

}
