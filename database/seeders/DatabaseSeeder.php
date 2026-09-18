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
        Setting::set('school_name', 'SMA Negeri 1 Demo');
        Setting::set('school_address', 'Jl. Pendidikan No. 1, Kota Demo');
        Setting::set('principal_name', 'Drs. Budi Santoso, M.Pd.');
        Setting::set('school_year', '2025/2026');
        Setting::set('semester', '1');
        Setting::set('phone', '(021) 123-4567');

        // ── Mata Pelajaran ────────────────────────────────────────────────────
        $mapels = [
            ['nama' => 'Matematika',          'kode' => 'MTK'],
            ['nama' => 'Bahasa Indonesia',    'kode' => 'BIND'],
            ['nama' => 'Bahasa Inggris',      'kode' => 'BING'],
            ['nama' => 'Fisika',              'kode' => 'FIS'],
            ['nama' => 'Kimia',               'kode' => 'KIM'],
            ['nama' => 'Biologi',             'kode' => 'BIO'],
            ['nama' => 'Sejarah',             'kode' => 'SEJ'],
            ['nama' => 'Pendidikan Pancasila', 'kode' => 'PPK'],
        ];

        foreach ($mapels as $mapel) {
            MataPelajaran::firstOrCreate(['kode' => $mapel['kode']], $mapel);
        }

        $mataPelajarans = MataPelajaran::all();

        // ── Super Admin ───────────────────────────────────────────────────────
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@sekolah.sch.id'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'role' => 'super_admin']
        );

        // ── Admin ─────────────────────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@sekolah.sch.id'],
            ['name' => 'Admin Sekolah', 'password' => Hash::make('password'), 'role' => 'admin']
        );

        // ── Kepala Sekolah ────────────────────────────────────────────────────
        $kepsek = User::firstOrCreate(
            ['email' => 'kepsek@sekolah.sch.id'],
            ['name' => 'Drs. Budi Santoso', 'password' => Hash::make('password'), 'role' => 'kepala_sekolah']
        );

        // ── Guru ──────────────────────────────────────────────────────────────
        $guruData = [
            ['name' => 'Andi Wijaya, S.Pd.',   'nip' => '198501012010011001'],
            ['name' => 'Siti Rahayu, S.Pd.',   'nip' => '198602022010012002'],
            ['name' => 'Budi Hartono, M.Pd.',  'nip' => '197803032008011003'],
            ['name' => 'Dewi Kusuma, S.Pd.',   'nip' => '199001012015012004'],
        ];

        $gurus = [];
        foreach ($guruData as $data) {
            $guru = User::firstOrCreate(
                ['name' => $data['name']],
                ['email' => null, 'password' => Hash::make('password'), 'role' => 'guru']
            );
            GuruProfile::firstOrCreate(['user_id' => $guru->id], ['nip' => $data['nip']]);
            $gurus[] = $guru;
        }

        // ── Kelas ─────────────────────────────────────────────────────────────
        $kelasData = [
            ['nama' => 'X-IPA-1', 'tingkat' => 'X',   'tahun_ajaran' => '2025/2026', 'wali_kelas_id' => $gurus[0]->id],
            ['nama' => 'X-IPA-2', 'tingkat' => 'X',   'tahun_ajaran' => '2025/2026', 'wali_kelas_id' => $gurus[1]->id],
            ['nama' => 'XI-IPA-1', 'tingkat' => 'XI',  'tahun_ajaran' => '2025/2026', 'wali_kelas_id' => $gurus[2]->id],
            ['nama' => 'XII-IPS-1', 'tingkat' => 'XII', 'tahun_ajaran' => '2025/2026', 'wali_kelas_id' => $gurus[3]->id],
        ];

        $kelasList = [];
        foreach ($kelasData as $data) {
            $kelasList[] = Kelas::firstOrCreate(['nama' => $data['nama'], 'tahun_ajaran' => $data['tahun_ajaran']], $data);
        }

        // ── Siswa ─────────────────────────────────────────────────────────────
        $siswaData = [
            // X-IPA-1
            ['name' => 'Ahmad Fauzi',    'nis' => '2025001', 'kelas' => $kelasList[0]],
            ['name' => 'Bella Putri',    'nis' => '2025002', 'kelas' => $kelasList[0]],
            ['name' => 'Candra Permana', 'nis' => '2025003', 'kelas' => $kelasList[0]],
            ['name' => 'Dina Maharani',  'nis' => '2025004', 'kelas' => $kelasList[0]],
            // X-IPA-2
            ['name' => 'Eko Prasetyo',   'nis' => '2025005', 'kelas' => $kelasList[1]],
            ['name' => 'Fitri Lestari',  'nis' => '2025006', 'kelas' => $kelasList[1]],
        ];

        foreach ($siswaData as $data) {
            $siswa = User::firstOrCreate(
                ['name' => $data['name']],
                ['email' => null, 'password' => Hash::make('password'), 'role' => 'siswa', 'is_active' => false]
            );
            SiswaProfile::firstOrCreate(
                ['user_id' => $siswa->id],
                ['nis' => $data['nis'], 'kelas_id' => $data['kelas']->id]
            );
        }

        // ── Jadwal ────────────────────────────────────────────────────────────
        // Each day: guru[0]=Senin, guru[1]=Selasa, etc. for kelas[0]
        $jadwalData = [
            // X-IPA-1 jadwals
            ['kelas_id' => $kelasList[0]->id, 'guru_id' => $gurus[0]->id, 'mata_pelajaran_id' => $mataPelajarans->find(MataPelajaran::where('kode', 'MTK')->first()?->id)?->id ?? $mataPelajarans->first()->id, 'hari' => 1, 'jam_mulai' => '07:00', 'jam_selesai' => '08:30'],
            ['kelas_id' => $kelasList[0]->id, 'guru_id' => $gurus[1]->id, 'mata_pelajaran_id' => $mataPelajarans->find(MataPelajaran::where('kode', 'BIND')->first()?->id)?->id ?? $mataPelajarans->first()->id, 'hari' => 1, 'jam_mulai' => '08:30', 'jam_selesai' => '10:00'],
            ['kelas_id' => $kelasList[0]->id, 'guru_id' => $gurus[2]->id, 'mata_pelajaran_id' => $mataPelajarans->find(MataPelajaran::where('kode', 'FIS')->first()?->id)?->id ?? $mataPelajarans->first()->id, 'hari' => 2, 'jam_mulai' => '07:00', 'jam_selesai' => '08:30'],
            ['kelas_id' => $kelasList[0]->id, 'guru_id' => $gurus[3]->id, 'mata_pelajaran_id' => $mataPelajarans->find(MataPelajaran::where('kode', 'KIM')->first()?->id)?->id ?? $mataPelajarans->first()->id, 'hari' => 3, 'jam_mulai' => '07:00', 'jam_selesai' => '08:30'],
            ['kelas_id' => $kelasList[0]->id, 'guru_id' => $gurus[0]->id, 'mata_pelajaran_id' => $mataPelajarans->find(MataPelajaran::where('kode', 'MTK')->first()?->id)?->id ?? $mataPelajarans->first()->id, 'hari' => 4, 'jam_mulai' => '07:00', 'jam_selesai' => '08:30'],
            ['kelas_id' => $kelasList[0]->id, 'guru_id' => $gurus[1]->id, 'mata_pelajaran_id' => $mataPelajarans->find(MataPelajaran::where('kode', 'BING')->first()?->id)?->id ?? $mataPelajarans->first()->id, 'hari' => 5, 'jam_mulai' => '07:00', 'jam_selesai' => '08:30'],

            // X-IPA-2
            ['kelas_id' => $kelasList[1]->id, 'guru_id' => $gurus[2]->id, 'mata_pelajaran_id' => $mataPelajarans->find(MataPelajaran::where('kode', 'FIS')->first()?->id)?->id ?? $mataPelajarans->first()->id, 'hari' => 1, 'jam_mulai' => '07:00', 'jam_selesai' => '08:30'],
            ['kelas_id' => $kelasList[1]->id, 'guru_id' => $gurus[3]->id, 'mata_pelajaran_id' => $mataPelajarans->find(MataPelajaran::where('kode', 'BIO')->first()?->id)?->id ?? $mataPelajarans->first()->id, 'hari' => 2, 'jam_mulai' => '07:00', 'jam_selesai' => '08:30'],
        ];

        foreach ($jadwalData as $data) {
            if ($data['mata_pelajaran_id']) {
                JadwalPelajaran::firstOrCreate(
                    ['kelas_id' => $data['kelas_id'], 'guru_id' => $data['guru_id'], 'hari' => $data['hari'], 'jam_mulai' => $data['jam_mulai']],
                    $data
                );
            }
        }

        $this->command->info('✅ Demo data seeded successfully!');
        $this->command->info('');
        $this->command->info('📋 Login credentials (password: password)');
        $this->command->info('   Super Admin  : superadmin@sekolah.sch.id');
        $this->command->info('   Admin        : admin@sekolah.sch.id');
        $this->command->info('   Kepala Sekolah: kepsek@sekolah.sch.id');
        $this->command->info('   Guru 1       : 198501012010011001 (NIP)');
        $this->command->info('   Guru 2       : 198602022010012002 (NIP)');
        $this->command->info('   Siswa 1      : 2025001 (NIS)');
        $this->command->info('   Siswa 2      : 2025002 (NIS)');
    }
}
