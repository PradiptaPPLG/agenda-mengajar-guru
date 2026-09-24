<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Spatie\SimpleExcel\SimpleExcelReader;

$files = ['Daftar Siswa per 21 September 2026.xlsx'];

foreach ($files as $file) {
    echo "\n\n--- FILE: $file ---\n";
    try {
        $rows = SimpleExcelReader::create(base_path($file))->skip(5)->take(3)->getRows()->toArray();
        print_r($rows);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}
