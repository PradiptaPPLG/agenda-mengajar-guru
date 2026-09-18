<?php

namespace Database\Seeders;

use App\Models\GuruProfile;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Setting;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Settings ─────────────────────────────────────────────────────────
        Setting::set('school_name', 'SMK Negeri 1 Garut');
        Setting::set('school_address', 'Jl. Cimanuk No. 309, Garut');
        Setting::set('principal_name', 'Drs. H. Dudung Abdul Rohman, M.M.Pd.');
        Setting::set('school_year', '2025/2026');
        Setting::set('semester', '1');
        Setting::set('phone', '(0262) 233-045');

        // ── Mata Pelajaran ────────────────────────────────────────────────────
        $mapels = [
            ['nama' => 'Matematika',                  'kode' => 'MTK'],
            ['nama' => 'Bahasa Indonesia',             'kode' => 'BIND'],
            ['nama' => 'Bahasa Inggris',               'kode' => 'BING'],
            ['nama' => 'Pendidikan Pancasila',         'kode' => 'PPK'],
            ['nama' => 'Sejarah',                     'kode' => 'SEJ'],
            ['nama' => 'Informatika',                  'kode' => 'INF'],
            ['nama' => 'Rekayasa Perangkat Lunak',     'kode' => 'RPL'],
            ['nama' => 'Basis Data',                   'kode' => 'BD'],
            ['nama' => 'Pemrograman Web',              'kode' => 'PWB'],
            ['nama' => 'Desain Komunikasi Visual',     'kode' => 'DKV'],
            ['nama' => 'Akuntansi',                    'kode' => 'AKL'],
            ['nama' => 'Pemasaran',                    'kode' => 'PM'],
            ['nama' => 'Manajemen Perkantoran',        'kode' => 'MPLB'],
            ['nama' => 'Perhotelan',                   'kode' => 'HTL'],
            ['nama' => 'Kuliner',                      'kode' => 'KLN'],
        ];

        foreach ($mapels as $mapel) {
            MataPelajaran::firstOrCreate(['kode' => $mapel['kode']], $mapel);
        }

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
            ['name' => 'Drs. H. Dudung Abdul Rohman, M.M.Pd.', 'password' => Hash::make('password'), 'role' => 'kepala_sekolah']
        );

        // ── Guru Reviewer: Nastiti, S.Pd. ────────────────────────────────────
        $nastiti = User::firstOrCreate(
            ['name' => 'Nastiti, S.Pd.', 'role' => 'guru'],
            ['email' => null, 'password' => Hash::make('password'), 'role' => 'guru', 'is_active' => true]
        );
        GuruProfile::firstOrCreate(
            ['user_id' => $nastiti->id],
            ['nip' => '198909242014012001']
        );

        // ── Kelas 12 RPL (untuk reviewer siswa) ──────────────────────────────
        $kelas12RPL = Kelas::firstOrCreate(
            ['nama' => '12RPL', 'tahun_ajaran' => '2025/2026'],
            ['tingkat' => 'XII', 'tahun_ajaran' => '2025/2026', 'wali_kelas_id' => null]
        );

        // ── Jadwal Nastiti: Informatika & RPL untuk kelas 11PPLG dan 12RPL ──
        $mapelINF = MataPelajaran::where('kode', 'INF')->first();
        $mapelRPL = MataPelajaran::where('kode', 'RPL')->first();
        $kelas11PPLG = Kelas::where('nama', '11PPLG')->first();

        if ($mapelINF && $kelas11PPLG) {
            JadwalPelajaran::firstOrCreate(
                ['kelas_id' => $kelas11PPLG->id, 'guru_id' => $nastiti->id, 'hari' => 1, 'jam_mulai' => '07:00'],
                ['mata_pelajaran_id' => $mapelINF->id, 'jam_selesai' => '08:30']
            );
            JadwalPelajaran::firstOrCreate(
                ['kelas_id' => $kelas11PPLG->id, 'guru_id' => $nastiti->id, 'hari' => 3, 'jam_mulai' => '07:00'],
                ['mata_pelajaran_id' => $mapelRPL->id, 'jam_selesai' => '08:30']
            );
        }

        if ($mapelINF) {
            JadwalPelajaran::firstOrCreate(
                ['kelas_id' => $kelas12RPL->id, 'guru_id' => $nastiti->id, 'hari' => 2, 'jam_mulai' => '07:00'],
                ['mata_pelajaran_id' => $mapelINF->id, 'jam_selesai' => '08:30']
            );
            JadwalPelajaran::firstOrCreate(
                ['kelas_id' => $kelas12RPL->id, 'guru_id' => $nastiti->id, 'hari' => 4, 'jam_mulai' => '07:00'],
                ['mata_pelajaran_id' => $mapelRPL->id, 'jam_selesai' => '08:30']
            );
        }

        // ── Siswa Reviewer: Pradipta Endra Maulana ────────────────────────────
        $pradipta = User::firstOrCreate(
            ['name' => 'Pradipta Endra Maulana', 'role' => 'siswa'],
            ['email' => null, 'password' => Hash::make('password'), 'role' => 'siswa', 'is_active' => true]
        );
        // Pastikan is_active = true untuk akun reviewer ini
        $pradipta->update(['is_active' => true]);

        SiswaProfile::firstOrCreate(
            ['user_id' => $pradipta->id],
            ['nis' => '1234512345', 'kelas_id' => $kelas12RPL->id]
        );

        // ── Panggil StudentSeeder (data siswa kelas 11) ───────────────────────
        $this->call(StudentSeeder::class);

        // ── Summary ───────────────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('📋 Login credentials (password: password)');
        $this->command->info('   Super Admin   : superadmin@sekolah.sch.id');
        $this->command->info('   Admin         : admin@sekolah.sch.id');
        $this->command->info('   Kepala Sekolah: kepsek@sekolah.sch.id');
        $this->command->info('   Guru Reviewer : NIP 198909242014012001 / password  (Nastiti, S.Pd.)');
        $this->command->info('   Siswa Reviewer: NIS 1234512345           / password  (Pradipta Endra Maulana, 12RPL)');
    }
}
