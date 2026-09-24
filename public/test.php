<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Spatie\SimpleExcel\SimpleExcelReader;

$files = ['Daftar mapel dan guru.xlsx', 'Daftar Siswa per 21 September 2026.xlsx', 'Jadwal_Guru_Ganjil_26-27_VALIDASI.xlsx'];

foreach ($files as $file) {
    echo "\n\n--- FILE: $file ---\n";
    try {
        $rows = SimpleExcelReader::create(base_path($file))->take(3)->getRows()->toArray();
        print_r($rows);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}
