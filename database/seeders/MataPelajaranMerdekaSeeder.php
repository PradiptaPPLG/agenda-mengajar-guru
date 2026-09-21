<?php

namespace Database\Seeders;

use App\Models\MataPelajaran;
use Illuminate\Database\Seeder;

class MataPelajaranMerdekaSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Kelompok Mata Pelajaran Umum (Normatif & Adaptif) ─────────────────
        $umum = [
            ['nama' => 'Pendidikan Agama dan Budi Pekerti', 'kode' => 'PAIBP', 'jenis' => 'umum'],
            ['nama' => 'Pendidikan Pancasila', 'kode' => 'PPKN', 'jenis' => 'umum'],
            ['nama' => 'Bahasa Indonesia', 'kode' => 'BIND', 'jenis' => 'umum'],
            ['nama' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan (PJOK)', 'kode' => 'PJOK', 'jenis' => 'umum'],
            ['nama' => 'Sejarah', 'kode' => 'SEJ', 'jenis' => 'umum'],
            ['nama' => 'Seni Budaya / Seni Rupa', 'kode' => 'SB', 'jenis' => 'umum'],
            ['nama' => 'Matematika', 'kode' => 'MTK', 'jenis' => 'umum'],
            ['nama' => 'Bahasa Inggris', 'kode' => 'BING', 'jenis' => 'umum'],
            ['nama' => 'Informatika', 'kode' => 'INF', 'jenis' => 'umum'],
            ['nama' => 'Projek IPAS (Ilmu Pengetahuan Alam dan Sosial)', 'kode' => 'IPAS', 'jenis' => 'umum'],
            ['nama' => 'Muatan Lokal (Bahasa Sunda)', 'kode' => 'MULOK', 'jenis' => 'umum'],
        ];

        // ── 2. Kelompok Mata Pelajaran Kejuruan (Produktif) ──────────────────────
        $produktif = [
            // AKL
            ['nama' => 'Praktikum Akuntansi Perusahaan', 'kode' => 'PAK-AKL', 'jenis' => 'produktif'],
            ['nama' => 'Komputer Akuntansi', 'kode' => 'KMP-AKL', 'jenis' => 'produktif'],
            ['nama' => 'Administrasi Pajak', 'kode' => 'PAJ-AKL', 'jenis' => 'produktif'],
            ['nama' => 'Akuntansi dan Keuangan Lembaga', 'kode' => 'AKL', 'jenis' => 'produktif'],

            // MPLB
            ['nama' => 'Pengelolaan Arsip', 'kode' => 'ARS-MPLB', 'jenis' => 'produktif'],
            ['nama' => 'Korespondensi', 'kode' => 'KOR-MPLB', 'jenis' => 'produktif'],
            ['nama' => 'Teknologi Perkantoran', 'kode' => 'TEK-MPLB', 'jenis' => 'produktif'],
            ['nama' => 'Manajemen Perkantoran', 'kode' => 'MPLB', 'jenis' => 'produktif'],

            // Pemasaran (PM)
            ['nama' => 'Penataan Produk', 'kode' => 'PROD-PM', 'jenis' => 'produktif'],
            ['nama' => 'Negosiasi Bisnis', 'kode' => 'NEG-PM', 'jenis' => 'produktif'],
            ['nama' => 'Digital Marketing', 'kode' => 'MKT-PM', 'jenis' => 'produktif'],
            ['nama' => 'Pemasaran (Bismen)', 'kode' => 'PM', 'jenis' => 'produktif'],

            // Kuliner
            ['nama' => 'Pengolahan Makanan', 'kode' => 'KLN-BOGA', 'jenis' => 'produktif'],
            ['nama' => 'Tata Boga', 'kode' => 'KLN-TATA', 'jenis' => 'produktif'],
            ['nama' => 'Ilmu Gizi', 'kode' => 'KLN-GIZI', 'jenis' => 'produktif'],
            ['nama' => 'Kebersihan dan Sanitasi Makanan', 'kode' => 'KLN-SAN', 'jenis' => 'produktif'],
            ['nama' => 'Kuliner / Jasa Boga', 'kode' => 'KLN', 'jenis' => 'produktif'],

            // Perhotelan
            ['nama' => 'Layanan Akomodasi', 'kode' => 'HTL-AKOM', 'jenis' => 'produktif'],
            ['nama' => 'Housekeeping', 'kode' => 'HTL-HK', 'jenis' => 'produktif'],
            ['nama' => 'Front Office', 'kode' => 'HTL-FO', 'jenis' => 'produktif'],
            ['nama' => 'Perhotelan', 'kode' => 'HTL', 'jenis' => 'produktif'],

            // PPLG / RPL
            ['nama' => 'Pemrograman Web dan Bergerak', 'kode' => 'PPLG-PWB', 'jenis' => 'produktif'],
            ['nama' => 'Basis Data', 'kode' => 'BD', 'jenis' => 'produktif'],
            ['nama' => 'Desain UI/UX', 'kode' => 'PPLG-UIUX', 'jenis' => 'produktif'],
            ['nama' => 'Rekayasa Perangkat Lunak', 'kode' => 'RPL', 'jenis' => 'produktif'],
            ['nama' => 'Pemrograman Web', 'kode' => 'PWB', 'jenis' => 'produktif'],

            // DKV
            ['nama' => 'Desain Grafis', 'kode' => 'DKV-DG', 'jenis' => 'produktif'],
            ['nama' => 'Videografi', 'kode' => 'DKV-VID', 'jenis' => 'produktif'],
            ['nama' => 'Animasi', 'kode' => 'DKV-ANIM', 'jenis' => 'produktif'],
            ['nama' => 'Teknik Sunting Video', 'kode' => 'DKV-EDIT', 'jenis' => 'produktif'],
            ['nama' => 'Desain Komunikasi Visual', 'kode' => 'DKV', 'jenis' => 'produktif'],

            // Kejuruan Lanjutan
            ['nama' => 'Produk Kreatif dan Kewirausahaan (PKK)', 'kode' => 'PKK', 'jenis' => 'produktif'],
        ];

        // Update existing records jenis
        MataPelajaran::whereIn('kode', ['MTK', 'BIND', 'BING', 'PPK', 'SEJ', 'INF', 'PAIBP', 'PPKN', 'PJOK', 'SB', 'IPAS', 'MULOK'])->update(['jenis' => 'umum']);
        MataPelajaran::whereIn('kode', ['RPL', 'BD', 'PWB', 'DKV', 'AKL', 'PM', 'MPLB', 'HTL', 'KLN'])->update(['jenis' => 'produktif']);

        foreach (array_merge($umum, $produktif) as $item) {
            MataPelajaran::updateOrCreate(['kode' => $item['kode']], $item);
        }
    }
}
