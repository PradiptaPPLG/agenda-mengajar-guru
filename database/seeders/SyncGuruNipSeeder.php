<?php

namespace Database\Seeders;

use App\Models\GuruProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SyncGuruNipSeeder extends Seeder
{
    /**
     * Run the database seeds to update teacher NIPs safely without duplication.
     */
    public function run(): void
    {
        $nipMappings = [
            'ERVIN MAULANA, A.Md.Par.' => '199606272025211116',
            'IQBAL FAUZI LISYANTO, S.Par.' => '199610022025211081',
            'SITI NURASYIAH, S.Pd.' => '199506162025212010',
            'DELA HIKMATUL AMBIA, S.Pd. Hj.' => '199005042025212167',
            'DINI APRIANI NURRAMDAN, S.Pd.' => '199004092025212141',
            'ARIF ZAPAR SIDIK, ST.' => '198909182025211135',
            'MAULINA FAJRIN, S.Par.' => '199806252025212074',
            'MAMAN NUROHMAN, S.Pd.' => '199506162025211004',
        ];

        DB::transaction(function () use ($nipMappings) {
            foreach ($nipMappings as $name => $nip) {
                $user = User::findMatchingGuru($name);
                if ($user) {
                    GuruProfile::updateOrCreate(
                        ['user_id' => $user->id],
                        ['nip' => $nip]
                    );
                }
            }
        });
    }
}
