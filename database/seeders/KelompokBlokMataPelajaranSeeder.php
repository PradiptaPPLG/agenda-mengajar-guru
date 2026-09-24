<?php

namespace Database\Seeders;

use App\Models\JadwalPelajaran;
use App\Models\MataPelajaran;
use Illuminate\Database\Seeder;

/**
 * Seeder ini mengupdate kolom kelompok_blok pada tabel mata_pelajarans
 * dan menyinkronkan ke jadwal_pelajarans berdasarkan klasifikasi
 * Kelompok A (Umum) dan Kelompok B (Produktif/Kejuruan) sesuai kurikulum SMK.
 *
 * Kelompok A (Umum/Normatif): PAI, PKN, BAHASA INDONESIA/INGGRIS/SUNDA, PJOK, SENI, SEJARAH, MATEMATIKA, IPAS
 * Kelompok B (Produktif/Kejuruan): INFORMATIKA, KKA, DPK, KK, PKK, MP (Mapil)
 * Reguler (tidak sistem blok): BP/BK, Apel, Pembiasaan, Upacara
 */
class KelompokBlokMataPelajaranSeeder extends Seeder
{
    /**
     * Klasifikasikan mata pelajaran ke kelompok blok berdasarkan nama dan kodenya.
     */
    public function classify(string $nama, ?string $kode = ''): string
    {
        $namaClean = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $nama ?? ''));
        $kodeClean = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $kode ?? ''));
        $combined = $namaClean.' '.$kodeClean;

        // 1. Reguler khusus (Bimbingan Konseling, Pembiasaan, Apel, Upacara)
        if (preg_match('/(BP|BK|BIMBINGANKONSELING|APEL|PEMBIASAAN|UPACARA)/i', $combined)) {
            return 'reguler';
        }

        // 2. Kelompok B: Produktif / Kejuruan / Pilihan Kejuruan
        if (
            preg_match('/(DPK|PKK|PRODUKKREATIF)/i', $combined) ||
            preg_match('/(^KK|KKRPL|KKDKV|KKAKL|KKPM|KKMPLB|KKHTL|KKKLN|KKPBS|KKPBD)/i', $kodeClean) ||
            preg_match('/(^MP|MPPBS|MPPBD|MAPIL)/i', $kodeClean) ||
            preg_match('/(INFOR|SIMDIG|KKA|KODING|PPLG|RPL)/i', $combined)
        ) {
            return 'kelompok_b';
        }

        // 3. Kelompok A: Umum / Normatif / Adaptif
        if (
            preg_match('/(PAI|AGAMA|PPKN|PPK|PANCASILA|INDO|BIND|INGG|BING|MAT|MTK|SEJ|SEJARAH|PJOK|PENJAS|SBD|SENIBUDAYA|SUNDA|MULOK|IPAS)/i', $combined)
        ) {
            return 'kelompok_a';
        }

        return 'reguler';
    }

    public function run(): void
    {
        $updatedMapel = ['kelompok_a' => 0, 'kelompok_b' => 0, 'reguler' => 0];
        $updatedJadwal = ['kelompok_a' => 0, 'kelompok_b' => 0, 'reguler' => 0];

        MataPelajaran::withTrashed()->each(function (MataPelajaran $mapel) use (&$updatedMapel, &$updatedJadwal) {
            $kelompok = $this->classify($mapel->nama, $mapel->kode);

            $mapel->update(['kelompok_blok' => $kelompok]);
            $updatedMapel[$kelompok]++;

            // Sinkronkan ke jadwal pelajaran yang menggunakan mata pelajaran ini
            $affectedJadwal = JadwalPelajaran::where('mata_pelajaran_id', $mapel->id)->update([
                'kelompok_blok' => $kelompok,
            ]);

            $updatedJadwal[$kelompok] += $affectedJadwal;
        });

        $this->command->info('✅ Kelompok blok mata pelajaran & jadwal berhasil disinkronkan:');
        $this->command->table(
            ['Kelompok', 'Jumlah Mapel', 'Jumlah Jadwal Terkait'],
            [
                ['Kelompok A (Umum)', $updatedMapel['kelompok_a'], $updatedJadwal['kelompok_a']],
                ['Kelompok B (Produktif)', $updatedMapel['kelompok_b'], $updatedJadwal['kelompok_b']],
                ['Reguler (tidak sistem blok)', $updatedMapel['reguler'], $updatedJadwal['reguler']],
            ]
        );
    }
}
