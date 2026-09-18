<?php

namespace Database\Seeders;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class CleanupFakeDataSeeder extends Seeder
{
    public function run(): void
    {
        $fakeKelasIds = Kelas::whereIn('nama', ['X-IPA-1', 'X-IPA-2', 'XI-IPA-1', 'XII-IPS-1'])
            ->pluck('id')
            ->toArray();

        if (empty($fakeKelasIds)) {
            $this->command->info('Tidak ada data palsu yang perlu dihapus.');

            return;
        }

        JadwalPelajaran::whereIn('kelas_id', $fakeKelasIds)->delete();

        $profiles = SiswaProfile::whereIn('kelas_id', $fakeKelasIds)->get();
        $userIds = $profiles->pluck('user_id')->toArray();

        SiswaProfile::whereIn('kelas_id', $fakeKelasIds)->delete();
        User::whereIn('id', $userIds)->delete();
        Kelas::whereIn('id', $fakeKelasIds)->delete();

        $this->command->info('✅ Data kelas palsu (X-IPA-1, X-IPA-2, XI-IPA-1, XII-IPS-1) berhasil dihapus.');
    }
}
