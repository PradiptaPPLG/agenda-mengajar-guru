<?php

namespace Database\Seeders;

use App\Models\MataPelajaran;
use Illuminate\Database\Seeder;

/**
 * Seeder ini mengupdate kolom kelompok_blok pada tabel mata_pelajarans
 * berdasarkan klasifikasi Kelompok A (Umum) dan Kelompok B (Produktif/Kejuruan)
 * sesuai kurikulum SMK Merdeka Belajar.
 *
 * Kelompok A (Umum/Normatif): PAI, PKN, BAHASA, PJOK, SENI, SEJARAH, MATEMATIKA, IPAS
 * Kelompok B (Produktif/Kejuruan): INFORMATIKA, KKA, DPK, KK, PKK, MAPIL
 * Semua lainnya: reguler (tidak termasuk dalam sistem blok)
 */
class KelompokBlokMataPelajaranSeeder extends Seeder
{
    /**
     * Kode / kata kunci nama mapel yang termasuk Kelompok A (Umum/Normatif).
     *
     * @var string[]
     */
    protected array $kelompokAKode = [
        'MTK', 'BIND', 'BING', 'PAIBP', 'PPK', 'PPKN', 'SEJ',
        'PJOK', 'SB', 'IPAS', 'MULOK',
    ];

    /**
     * Kode / kata kunci nama mapel yang termasuk Kelompok B (Produktif/Kejuruan).
     * Mencakup semua mapel kejuruan dari semua jurusan.
     *
     * @var string[]
     */
    protected array $kelompokBKode = [
        // Informatika & PPLG
        'INF', 'RPL', 'BD', 'PWB', 'PPLG-PWB', 'PPLG-UIUX',
        // DKV
        'DKV', 'DKV-DG', 'DKV-VID', 'DKV-ANIM', 'DKV-EDIT',
        // AKL (Akuntansi)
        'AKL', 'PAK-AKL', 'KMP-AKL', 'PAJ-AKL',
        // Pemasaran
        'PM', 'PROD-PM', 'NEG-PM', 'MKT-PM',
        // MPLB (Manajemen Perkantoran)
        'MPLB', 'ARS-MPLB', 'KOR-MPLB', 'TEK-MPLB',
        // Kuliner/Boga
        'KLN', 'KLN-BOGA', 'KLN-TATA', 'KLN-GIZI', 'KLN-SAN',
        // Perhotelan
        'HTL', 'HTL-AKOM', 'HTL-HK', 'HTL-FO',
        // PKK (mapel produktif lintas jurusan)
        'PKK',
    ];

    public function run(): void
    {
        $updated = ['kelompok_a' => 0, 'kelompok_b' => 0, 'reguler' => 0];

        MataPelajaran::withTrashed()->each(function (MataPelajaran $mapel) use (&$updated) {
            if (in_array($mapel->kode, $this->kelompokAKode)) {
                $mapel->update(['kelompok_blok' => 'kelompok_a']);
                $updated['kelompok_a']++;
            } elseif (in_array($mapel->kode, $this->kelompokBKode)) {
                $mapel->update(['kelompok_blok' => 'kelompok_b']);
                $updated['kelompok_b']++;
            } else {
                $mapel->update(['kelompok_blok' => 'reguler']);
                $updated['reguler']++;
            }
        });

        $this->command->info('✅ Kelompok blok mata pelajaran berhasil diupdate:');
        $this->command->table(
            ['Kelompok', 'Jumlah Mapel'],
            [
                ['Kelompok A (Umum)', $updated['kelompok_a']],
                ['Kelompok B (Produktif)', $updated['kelompok_b']],
                ['Reguler (tidak sistem blok)', $updated['reguler']],
            ]
        );
    }
}
