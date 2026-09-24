<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Spatie\SimpleExcel\SimpleExcelReader;

$file = 'Daftar Siswa per 21 September 2026.xlsx';
echo "\n\n--- FILE: $file ---\n";
try {
    $rows = SimpleExcelReader::create(base_path($file))->noHeaderRow()->skip(3)->take(1)->getRows()->toArray();
    print_r($rows);
} catch (Exception $e) {
    echo $e->getMessage();
}

$file2 = 'Daftar mapel dan guru.xlsx';
echo "\n\n--- FILE: $file2 ---\n";
try {
    $rows = SimpleExcelReader::create(base_path($file2))->noHeaderRow()->skip(0)->take(5)->getRows()->toArray();
    print_r($rows);
} catch (Exception $e) {
    echo $e->getMessage();
}
