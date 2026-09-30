<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder siswa kelas 10 AKL 5 (alias 10 PBS).
 *
 * Di Excel "Daftar Siswa per 21 September 2026.xlsx", kelas ini
 * tercatat sebagai "10AKL5". Dalam sistem, kelas ini dikenal sebagai
 * "10 PBS". Seeder ini memetakan keduanya secara eksplisit.
 *
 * Idempotent: siswa dengan NIS yang sudah ada di DB akan dilewati.
 */
class AddAkl5StudentsSeeder extends Seeder
{
    public function run(): void
    {
        // [nisn, nama, jenis_kelamin, usia, nama_kelas_sistem]
        $students = [
            ['3101911292', 'ADELIA NURUL WAHIDAH', 'P', 15, '10PBS'],
            ['0109389969', 'ANA YULIANA', 'P', 15, '10PBS'],
            ['0105870055', 'ANDRIAN', 'L', 15, '10PBS'],
            ['0117005929', 'ANI WAHYUNI', 'P', 14, '10PBS'],
            ['0106625868', 'ANITYA WAHIDATUL HASANAH', 'P', 15, '10PBS'],
            ['0103093552', 'AULIA PEBRIANA', 'P', 15, '10PBS'],
            ['3112261432', 'AZMI KHOIRUN NIDA', 'P', 14, '10PBS'],
            ['3109571390', 'DEFINNA AZKYA KALLENA', 'P', 15, '10PBS'],
            ['0101916537', 'DEVA GALUH SANTOSO', 'P', 15, '10PBS'],
            ['0103350289', 'DEVI GALIH SANTOSO', 'P', 15, '10PBS'],
            ['0102502251', 'ERSA LATIFUNNISA', 'P', 15, '10PBS'],
            ['0103419296', 'FAREL AKBAR', 'L', 15, '10PBS'],
            ['0107287662', 'GINA REGINA NURJANAH', 'P', 15, '10PBS'],
            ['3107938438', 'GINA WULANDARI', 'P', 15, '10PBS'],
            ['0108922400', 'HAFIZ HIKMATUL RAMDHANI', 'L', 15, '10PBS'],
            ['0111521890', 'HAZNI SITI QUDSIAH', 'P', 14, '10PBS'],
            ['0118475201', 'INDIRA SYAHPUTRI', 'P', 14, '10PBS'],
            ['0102374649', 'KAYLA DAFINA ASSARIP', 'P', 15, '10PBS'],
            ['0117470521', 'LUTFI SYAFI FAHREZA', 'L', 14, '10PBS'],
            ['0119261441', 'MARTHA KOSASIH', 'L', 14, '10PBS'],
            ['0105012560', 'MEILANI EBYELLA MULYANA', 'P', 15, '10PBS'],
            ['0106033250', 'NAILA RAIHANA', 'P', 15, '10PBS'],
            ['0102039218', 'NAYLA HUSNA KHAIRIYAH', 'P', 15, '10PBS'],
            ['0108159678', 'NAZWA APRILIA GUNAWAN', 'P', 15, '10PBS'],
            ['0109241264', 'RANA SEPTIANA RAMDANI', 'L', 15, '10PBS'],
            ['0118442640', 'REDI HERADI', 'L', 14, '10PBS'],
            ['3104757818', 'RIFANA MEILANI PUTRI', 'P', 15, '10PBS'],
            ['0115411207', 'RIVA NURLATIFA', 'P', 14, '10PBS'],
            ['0103140217', 'SELBYA SEPTIANI', 'P', 15, '10PBS'],
            ['0107344477', 'SELFI ASANIA FITRI', 'P', 15, '10PBS'],
            ['0096190190', 'SELLY SITI SALIMAH', 'P', 16, '10PBS'],
            ['3101553544', 'SERINA SHABILA', 'P', 15, '10PBS'],
            ['3115907575', 'SRI RAHMAWATI', 'P', 14, '10PBS'],
            ['0104152831', 'YUSICKA ANASTASYA AURILIANSYAH', 'P', 15, '10PBS'],
            ['0109502500', 'ZAHRA AULIA AKBAR', 'P', 15, '10PBS'],
            ['0102079173', 'ZASKIA RAHMA JELITA', 'P', 15, '10PBS'],
        ];

        // Cari kelas 10PBS secara fleksibel (mencocokkan "10 PBS" di DB)
        $kelas = Kelas::findByNameFlexible('10PBS')
            ?? Kelas::firstOrCreate(
                ['nama' => '10PBS', 'tahun_ajaran' => '2025/2026'],
                ['tingkat' => 'X', 'tahun_ajaran' => '2025/2026', 'wali_kelas_id' => null]
            );

        $inserted = 0;
        $skipped = 0;

        foreach ($students as [$nisn, $name, $gender, $age, $namaKelas]) {
            // Lewati jika NIS sudah ada (idempotent)
            if (SiswaProfile::where('nis', $nisn)->exists()) {
                $skipped++;

                continue;
            }

            $siswa = User::create([
                'name' => $name,
                'email' => null,
                'password' => Hash::make($nisn ?: 'password123'),
                'role' => 'siswa',
                'is_active' => false,
            ]);

            SiswaProfile::create([
                'user_id' => $siswa->id,
                'nis' => $nisn,
                'kelas_id' => $kelas->id,
            ]);

            $inserted++;
        }

        $this->command->info("Done: {$inserted} siswa 10 AKL 5 ditambahkan ke kelas {$kelas->nama}, {$skipped} dilewati.");
    }
}
