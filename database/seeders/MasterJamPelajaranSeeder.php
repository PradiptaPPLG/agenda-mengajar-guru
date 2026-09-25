<?php

namespace Database\Seeders;

use App\Models\MasterJamPelajaran;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk jam pelajaran riil sekolah SMK:
 * Jam 1 : 06:30 - 07:10
 * Jam 2 : 07:10 - 07:50
 * Jam 3 : 07:50 - 08:30
 * Jam 4 : 08:30 - 09:10
 * [Istirahat 1: 09:10 - 09:30]
 * Jam 5 : 09:30 - 10:10
 * Jam 6 : 10:10 - 10:50
 * Jam 7 : 10:50 - 11:30
 * [Istirahat 2 & Sholat: 11:30 - 12:30]
 * Jam 8 : 12:30 - 13:10
 * Jam 9 : 13:10 - 13:50
 * Jam 10: 13:50 - 14:30
 * Jam 11: 14:30 - 15:10
 */
class MasterJamPelajaranSeeder extends Seeder
{
    public function run(): void
    {
        $slots = [
            1 => ['jam_mulai' => '06:30:00', 'jam_selesai' => '07:10:00'],
            2 => ['jam_mulai' => '07:10:00', 'jam_selesai' => '07:50:00'],
            3 => ['jam_mulai' => '07:50:00', 'jam_selesai' => '08:30:00'],
            4 => ['jam_mulai' => '08:30:00', 'jam_selesai' => '09:10:00'],
            5 => ['jam_mulai' => '09:30:00', 'jam_selesai' => '10:10:00'],
            6 => ['jam_mulai' => '10:10:00', 'jam_selesai' => '10:50:00'],
            7 => ['jam_mulai' => '10:50:00', 'jam_selesai' => '11:30:00'],
            8 => ['jam_mulai' => '12:30:00', 'jam_selesai' => '13:10:00'],
            9 => ['jam_mulai' => '13:10:00', 'jam_selesai' => '13:50:00'],
            10 => ['jam_mulai' => '13:50:00', 'jam_selesai' => '14:30:00'],
            11 => ['jam_mulai' => '14:30:00', 'jam_selesai' => '15:10:00'],
        ];

        // Hapus jam di luar 1-11 jika ada
        MasterJamPelajaran::where('jam_ke', '>', count($slots))->delete();

        foreach ($slots as $jamKe => $times) {
            MasterJamPelajaran::updateOrCreate(
                ['jam_ke' => $jamKe],
                [
                    'jam_mulai' => $times['jam_mulai'],
                    'jam_selesai' => $times['jam_selesai'],
                ]
            );
        }
    }
}
