<?php

namespace App\Console\Commands;

use App\Models\GuruProfile;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

#[Signature('pengawas:buat-akun {--nip=196907121998022001} {--name=Ika Juliatiningsih, S.Pd., M.Pd} {--email=ikajuliatiningsih@sekolah.sch.id} {--jabatan=Pengawas Pembina/Cabang Dinas Pendidikan Wilayah XIII}')]
#[Description('Buat atau perbarui akun Pengawas Sekolah (Ibu Ika Juliatiningsih)')]
class BuatAkunPengawasCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $nip = (string) $this->option('nip');
        $name = (string) $this->option('name');
        $email = (string) $this->option('email');
        $jabatan = (string) $this->option('jabatan');

        $this->info("Menyiapkan akun Pengawas Sekolah untuk {$name} (NIP: {$nip})...");

        // Cari user yang sudah ada berdasarkan NIP atau Email
        $existingProfile = GuruProfile::where('nip', $nip)->first();
        $user = $existingProfile?->user ?? User::where('email', $email)->first();

        if (! $user) {
            $user = User::where('name', $name)->first();
        }

        if ($user) {
            $user->update([
                'name' => $name,
                'email' => $email,
                'role' => 'pengawas',
                'is_active' => true,
                'password' => Hash::make($nip),
            ]);
            $this->info("Akun user sudah ada (ID: {$user->id}). Berhasil diperbarui.");
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'role' => 'pengawas',
                'is_active' => true,
                'password' => Hash::make($nip),
            ]);
            $this->info("Akun user baru berhasil dibuat (ID: {$user->id}).");
        }

        GuruProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nip' => $nip,
                'kaprog_jurusan' => $jabatan,
            ]
        );

        $spatieRole = Role::firstOrCreate(['name' => 'Pengawas Pembina', 'guard_name' => 'web']);
        $user->syncRoles(['Pengawas Pembina']);

        $this->newLine();
        $this->info('=============================================');
        $this->info(' AKUN PENGAWAS SEKOLAH BERHASIL DIAKTIFKAN!  ');
        $this->info('=============================================');
        $this->line(" Nama     : {$user->name}");
        $this->line(" NIP      : {$nip}");
        $this->line(" Email    : {$user->email}");
        $this->line(" Password : {$nip} (NIP)");
        $this->line(" Role     : {$user->role} ({$user->role_label})");
        $this->line(" Jabatan  : {$jabatan}");
        $this->info('=============================================');

        return Command::SUCCESS;
    }
}
