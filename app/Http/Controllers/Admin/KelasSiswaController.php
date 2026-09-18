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
            'excel_file' => 'required|file|mimes:xlsx,csv|max:10240',
        ]);

        $path = $request->file('excel_file')->getRealPath();
        $reader = SimpleExcelReader::create($path);

        $importedCount = 0;

        $reader->getRows()->each(function (array $rowProperties) use ($kelas, &$importedCount) {
            // Find columns flexibly
            $nameKey = $this->findKey($rowProperties, ['nama lengkap', 'nama', 'name']);
            $emailKey = $this->findKey($rowProperties, ['alamat email', 'email']);
            $nisKey = $this->findKey($rowProperties, ['nis']);
            $genderKey = $this->findKey($rowProperties, ['jenis kelamin', 'jk', 'gender']);
            $phoneKey = $this->findKey($rowProperties, ['no.hp', 'hp', 'phone', 'telepon']);

            if (!$nameKey || empty($rowProperties[$nameKey])) {
                return; // Skip if no name
            }

            $name = $rowProperties[$nameKey];
            
            // Prepare email
            $email = null;
            if ($emailKey && !empty($rowProperties[$emailKey])) {
                $email = $rowProperties[$emailKey];
            } else {
                // Generate a dummy email if none provided
                $cleanName = Str::slug($name, '');
                $randomStr = Str::random(4);
                $email = "{$cleanName}.{$randomStr}@smkn1ciamis.id";
            }

            // Check if user exists by email
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                // Check if we have NIS, maybe we can link by NIS
                $nis = $nisKey ? $rowProperties[$nisKey] : null;
                $existingProfile = null;
                
                if ($nis) {
                    $existingProfile = SiswaProfile::where('nis', $nis)->first();
                    if ($existingProfile) {
                        $user = $existingProfile->user;
                    }
                }

                if (!$user) {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make('password'),
                        'role' => 'siswa',
                    ]);
                }
            }

            // Create or update Siswa Profile
            $nis = $nisKey ? $rowProperties[$nisKey] : null;
            $profile = SiswaProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['nis' => $nis]
            );

            // Assign to this class
            $profile->update([
                'kelas_id' => $kelas->id,
                'nis' => $nis ?? $profile->nis
            ]);

            $importedCount++;
        });

        return back()->with('success', "Berhasil mengimpor $importedCount siswa.");
    }

    private function findKey(array $row, array $possibleNames)
    {
        foreach ($row as $key => $value) {
            $lowerKey = strtolower(trim($key));
            foreach ($possibleNames as $possibleName) {
                if (str_contains($lowerKey, $possibleName)) {
                    return $key;
                }
            }
        }
        return null;
    }
}
