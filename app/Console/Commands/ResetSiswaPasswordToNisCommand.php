<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('siswa:reset-password-nis {--check : Hanya cek status password siswa terhadap NIS tanpa mengubah}')]
#[Description('Reset semua password akun siswa menjadi NIS masing-masing')]
class ResetSiswaPasswordToNisCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $siswas = User::where('role', 'siswa')->with('siswaProfile')->get();

        if ($this->option('check')) {
            $matchCount = 0;
            $mismatchCount = 0;
            foreach ($siswas as $siswa) {
                $nis = trim((string) ($siswa->siswaProfile?->nis ?? ''));
                if (! $nis || $nis === '-') {
                    continue;
                }
                $cleanNis = preg_replace('/\s+/', '', $nis);
                if (Hash::check($cleanNis, $siswa->password)) {
                    $matchCount++;
                } else {
                    $mismatchCount++;
                    $this->warn("Password mismatch: {$siswa->name} (NIS: {$cleanNis})");
                }
            }
            $this->info("Check complete! Cocok dengan NIS: {$matchCount}, Tidak cocok: {$mismatchCount}");

            return Command::SUCCESS;
        }

        $this->info("Menemukan {$siswas->count()} akun siswa...");

        $successCount = 0;
        $skippedCount = 0;
        $progressBar = $this->output->createProgressBar($siswas->count());
        $progressBar->start();

        foreach ($siswas as $siswa) {
            $nis = trim((string) ($siswa->siswaProfile?->nis ?? ''));

            if ($nis === '' || $nis === '-') {
                $skippedCount++;
                $progressBar->advance();

                continue;
            }

            // Bersihkan NIS dari spasi tidak sengaja
            $cleanNis = preg_replace('/\s+/', '', $nis);

            $siswa->password = Hash::make($cleanNis);
            $siswa->save();

            $successCount++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info("Selesai! Berhasil mereset {$successCount} password siswa ke NIS.");
        if ($skippedCount > 0) {
            $this->warn("Terdapat {$skippedCount} siswa yang dilewati karena NIS kosong.");
        }

        return Command::SUCCESS;
    }
}
