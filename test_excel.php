<?php

use Illuminate\Contracts\Console\Kernel;
use Spatie\SimpleExcel\SimpleExcelReader;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$reader = SimpleExcelReader::create('C:/laragon/www/agenda-mengajar-guru/Daftar mapel dan guru.xlsx', 'xlsx');
$reader->getReader()->open('C:/laragon/www/agenda-mengajar-guru/Daftar mapel dan guru.xlsx');
foreach ($reader->getReader()->getSheetIterator() as $s) {
    echo 'Sheet: '.$s->getName()."\n";
    if ($s->getName() == 'Daftar Guru & Mapel diampu') {
        $i = 0;
        foreach ($s->getRowIterator() as $r) {
            print_r($r->toArray());
            $i++;
            if ($i > 5) {
                break;
            }
        }
    }
}
