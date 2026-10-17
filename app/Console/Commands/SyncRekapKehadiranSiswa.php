<?php

namespace App\Console\Commands;

use App\Models\RekapKehadiranSiswa;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncRekapKehadiranSiswa extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rekap:sync-siswa {--truncate : Kosongkan tabel rekap sebelum sinkronisasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi dan backfill data rekap_kehadiran_siswas dari data historis presensi';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai sinkronisasi tabel rekap_kehadiran_siswas...');

        if ($this->option('truncate')) {
            RekapKehadiranSiswa::truncate();
            $this->warn('Tabel rekap_kehadiran_siswas telah dikosongkan.');
        }

        $defaultTa = Setting::getTahunAjaranAktif();
        $defaultSem = Setting::getSemesterAktif();

        // Ambil kombinasi unik siswa, kelas, tahun ajaran, dan semester
        $combinations = DB::table('kehadiran_siswas as ks')
            ->join('pertemuans as p', 'ks.pertemuan_id', '=', 'p.id')
            ->join('jadwal_pelajarans as j', 'p.jadwal_id', '=', 'j.id')
            ->whereNull('p.deleted_at')
            ->select([
                'ks.siswa_id',
                'j.kelas_id',
                DB::raw("COALESCE(NULLIF(p.tahun_ajaran, ''), NULLIF(j.tahun_ajaran, ''), '{$defaultTa}') as tahun_ajaran"),
                DB::raw("COALESCE(NULLIF(p.semester, ''), NULLIF(j.semester, ''), '{$defaultSem}') as semester"),
            ])
            ->distinct()
            ->get();

        $totalCount = $combinations->count();
        $this->info("Ditemukan {$totalCount} kombinasi siswa-kelas-periode untuk disinkronkan.");

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        foreach ($combinations as $combo) {
            RekapKehadiranSiswa::syncForSiswa(
                (int) $combo->siswa_id,
                (int) $combo->kelas_id,
                (string) $combo->tahun_ajaran,
                (string) $combo->semester
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Sinkronisasi rekap_kehadiran_siswas selesai dengan sukses!');

        return self::SUCCESS;
    }
}
