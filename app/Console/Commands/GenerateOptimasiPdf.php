<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class GenerateOptimasiPdf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:generate-optimasi-pdf {--path= : Lokasi output file PDF}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghasilkan dokumen PDF laporan arsitektur optimasi performa sistem (Optimasi.pdf)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai pembuatan dokumen Optimasi.pdf...');

        $outputPath = $this->option('path') ?: base_path('Optimasi.pdf');

        $pdf = Pdf::loadView('reports.optimasi_pdf')
            ->setPaper('a4', 'portrait')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
            ]);

        $pdf->save($outputPath);

        $size = filesize($outputPath);
        $sizeKb = round($size / 1024, 2);

        $this->info("Dokumen PDF berhasil dibuat di: {$outputPath} ({$sizeKb} KB)");

        return 0;
    }
}
