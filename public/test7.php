<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Spatie\SimpleExcel\SimpleExcelReader;

$file = 'Daftar mapel dan guru.xlsx';
echo "\n\n--- FILE: $file ---\n";
try {
    $reader = SimpleExcelReader::create(base_path($file));
    $reader->getReader()->open(base_path($file));
    foreach ($reader->getReader()->getSheetIterator() as $sheet) {
        echo 'SHEET: '.$sheet->getName()."\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
