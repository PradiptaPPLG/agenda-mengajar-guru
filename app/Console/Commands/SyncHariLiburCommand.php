<?php

namespace App\Console\Commands;

use App\Services\HariLiburSyncService;
use Illuminate\Console\Command;

class SyncHariLiburCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'libur:sync {tahun? : Tahun kalender yang akan disinkronkan (default: tahun berjalan)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi hari libur nasional Indonesia dan cuti bersama dari Public API';

    /**
     * Execute the console command.
     */
    public function handle(HariLiburSyncService $syncService): int
    {
        $tahun = (int) ($this->argument('tahun') ?: now()->year);

        $this->info("Memulai sinkronisasi hari libur nasional untuk tahun {$tahun}...");

        $result = $syncService->syncTahun($tahun);

        if ($result['success']) {
            $this->info($result['message']);

            return 0;
        }

        $this->error($result['message']);

        return 1;
    }
}
