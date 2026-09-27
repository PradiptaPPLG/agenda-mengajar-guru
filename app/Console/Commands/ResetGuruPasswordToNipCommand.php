<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('guru:reset-password-nip {--check : Hanya cek status password guru terhadap NIP tanpa mengubah}')]
#[Description('Reset semua password akun guru menjadi NIP masing-masing')]
class ResetGuruPasswordToNipCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $gurus = User::where('role', 'guru')->with('guruProfile')->get();

        if ($this->option('check')) {
            $matchCount = 0;
            $mismatchCount = 0;
            foreach ($gurus as $guru) {
                $nip = trim((string) ($guru->guruProfile?->nip ?? ''));
                if (! $nip || $nip === '-') {
                    continue;
                }
                $cleanNip = preg_replace('/\s+/', '', $nip);
                if (Hash::check($cleanNip, $guru->password)) {
                    $matchCount++;
                } else {
                    $mismatchCount++;
                    $this->warn("Password mismatch: {$guru->name} (NIP: {$cleanNip})");
                }
            }
            $this->info("Check complete! Cocok dengan NIP: {$matchCount}, Tidak cocok: {$mismatchCount}");

            return Command::SUCCESS;
        }

        $this->info("Menemukan {$gurus->count()} akun guru...");

        $successCount = 0;
        $skippedCount = 0;

        foreach ($gurus as $guru) {
            $nip = trim((string) ($guru->guruProfile?->nip ?? ''));

            if ($nip === '' || $nip === '-') {
                $this->warn("⚠️  Guru: {$guru->name} (ID: {$guru->id}) tidak memiliki NIP yang valid. Password dilewati.");
                $skippedCount++;

                continue;
            }

            // Bersihkan NIP dari spasi tidak sengaja
            $cleanNip = preg_replace('/\s+/', '', $nip);

            $guru->password = Hash::make($cleanNip);
            $guru->save();

            $this->line("✅ Guru: {$guru->name} | NIP: {$cleanNip} | Password berhasil diatur ke NIP");
            $successCount++;
        }

        $this->newLine();
        $this->info("Selesai! Berhasil mereset {$successCount} password guru ke NIP.");
        if ($skippedCount > 0) {
            $this->warn("Terdapat {$skippedCount} guru yang dilewati karena NIP kosong.");
        }

        return Command::SUCCESS;
    }
}
