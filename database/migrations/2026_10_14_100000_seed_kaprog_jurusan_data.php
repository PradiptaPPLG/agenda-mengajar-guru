<?php

use App\Models\GuruProfile;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengisi informasi jurusan pada akun Kaprog (Kepala Program Keahlian) di SMKN 1 Ciamis:
     * - Mahani Gunawan -> Kuliner
     * - Nastiti -> PPLG
     * - Ani Herliani -> DKV
     * - Carkim -> AKL
     * - Ati Sukmawati -> Pemasaran
     * - Deasy Putri Lestari -> MPLB
     * - Alisah -> Perhotelan
     */
    public function up(): void
    {
        $kaprogRole = Role::firstOrCreate(['name' => 'Kaprog', 'guard_name' => 'web']);

        $kaprogMapping = [
            'MAHANI GUNAWAN' => 'Kuliner',
            'NASTITI' => 'PPLG',
            'ANI HERLIANI' => 'DKV',
            'CARKIM' => 'AKL',
            'ATI SUKMAWATI' => 'Pemasaran',
            'DEASY PUTRI' => 'MPLB',
            'ALISAH' => 'Perhotelan',
        ];

        foreach ($kaprogMapping as $nameKeyword => $jurusan) {
            $user = User::where('role', 'guru')
                ->where('name', 'LIKE', "%{$nameKeyword}%")
                ->first();

            if ($user) {
                if (! $user->hasRole('Kaprog')) {
                    $user->assignRole($kaprogRole);
                }

                $profile = GuruProfile::firstOrCreate(['user_id' => $user->id]);
                if (empty($profile->kaprog_jurusan)) {
                    $profile->update(['kaprog_jurusan' => $jurusan]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data rollback bersifat opsional / tidak perlu merusak profil
    }
};
