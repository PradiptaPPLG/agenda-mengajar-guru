<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Settings ─────────────────────────────────────────────────────────
        Setting::set('school_name', 'SMK Negeri 1 Ciamis');
        Setting::set('school_address', 'Jalan Jenderal Sudirman Nomor 269, Kelurahan Sindangrasa, Kecamatan Ciamis, Kabupaten Ciamis, Jawa Barat');
        Setting::set('principal_name', '');
        Setting::set('school_year', '2025/2026');
        Setting::set('semester', '1');
        Setting::set('phone', '(0265) 771204');
        Setting::set('toleransi_keterlambatan_menit', '5'); // Default toleransi 5 menit

        // ── Master Jam Pelajaran ─────────────────────────────────────────────
        $this->call(MasterJamPelajaranSeeder::class);

        // ── Role & Permission ────────────────────────────────────────────────
        $this->call(PermissionSeeder::class);

        // ── Super Admin ───────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'superadmin@sekolah.sch.id'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'role' => 'super_admin']
        );

        // ── Admin ─────────────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@sekolah.sch.id'],
            ['name' => 'Admin Sekolah', 'password' => Hash::make('password'), 'role' => 'admin']
        );

        // ── Kepala Sekolah ────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'kepsek@sekolah.sch.id'],
            ['name' => 'Kepala Sekolah', 'password' => Hash::make('password'), 'role' => 'kepala_sekolah']
        );

        // ── Petugas Piket ──────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'piket@sekolah.sch.id'],
            ['name' => 'Petugas Piket', 'password' => Hash::make('password'), 'role' => 'piket', 'is_active' => true]
        );

        // ── Tata Usaha (TU) ───────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'tu@sekolah.sch.id'],
            ['name' => 'Staf Tata Usaha', 'password' => Hash::make('password'), 'role' => 'tu', 'is_active' => true]
        );

        // ── Panggil StudentSeeder & AttendanceSeeder ─────────────────────────
        //  $this->call(StudentSeeder::class);
        //  $this->call(AttendanceSeeder::class);

        // ── Summary ───────────────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('📋 Login credentials (password: password)');
        $this->command->info('   Super Admin   : superadmin@sekolah.sch.id');
        $this->command->info('   Admin         : admin@sekolah.sch.id');
        $this->command->info('   Kepala Sekolah: kepsek@sekolah.sch.id');
        $this->command->info('   Petugas Piket : piket@sekolah.sch.id');
        $this->command->info('   Guru Reviewer : NIP 198909242014012001 / password  (Nastiti, S.Pd.)');
    }
}
