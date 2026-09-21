<?php

namespace Database\Seeders;

use App\Models\MasterJamPelajaran;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MasterJamPelajaranSeeder extends Seeder
{
    public function run(): void
    {
        $startTime = Carbon::createFromTime(7, 0); // 07:00
        $durationMinutes = 40;

        for ($i = 1; $i <= 10; $i++) {
            $endTime = (clone $startTime)->addMinutes($durationMinutes);

            MasterJamPelajaran::updateOrCreate(
                ['jam_ke' => $i],
                [
                    'jam_mulai' => $startTime->format('H:i:s'),
                    'jam_selesai' => $endTime->format('H:i:s'),
                ]
            );

            $startTime = $endTime;
        }
    }
}
