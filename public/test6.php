<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Spatie\SimpleExcel\SimpleExcelReader;

$file = 'Daftar Siswa per 21 September 2026.xlsx';
echo "\n\n--- FILE: $file ---\n";
try {
    $rows = SimpleExcelReader::create(base_path($file))->noHeaderRow()->take(8)->getRows()->toArray();
    print_r($rows);
} catch (Exception $e) {
    echo $e->getMessage();
}
