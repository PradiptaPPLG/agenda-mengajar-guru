<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $slots = [
            ['jam_ke' => 1, 'jam_mulai' => '06:30:00', 'jam_selesai' => '07:10:00'],
            ['jam_ke' => 2, 'jam_mulai' => '07:10:00', 'jam_selesai' => '07:50:00'],
            ['jam_ke' => 3, 'jam_mulai' => '07:50:00', 'jam_selesai' => '08:30:00'],
            ['jam_ke' => 4, 'jam_mulai' => '08:30:00', 'jam_selesai' => '09:10:00'],
            ['jam_ke' => 5, 'jam_mulai' => '09:30:00', 'jam_selesai' => '10:10:00'],
            ['jam_ke' => 6, 'jam_mulai' => '10:10:00', 'jam_selesai' => '10:50:00'],
            ['jam_ke' => 7, 'jam_mulai' => '10:50:00', 'jam_selesai' => '11:30:00'],
            ['jam_ke' => 8, 'jam_mulai' => '12:30:00', 'jam_selesai' => '13:10:00'],
            ['jam_ke' => 9, 'jam_mulai' => '13:10:00', 'jam_selesai' => '13:50:00'],
            ['jam_ke' => 10, 'jam_mulai' => '13:50:00', 'jam_selesai' => '14:30:00'],
            ['jam_ke' => 11, 'jam_mulai' => '14:30:00', 'jam_selesai' => '15:10:00'],
        ];

        DB::table('master_jam_pelajarans')->truncate();

        foreach ($slots as $slot) {
            DB::table('master_jam_pelajarans')->insert([
                'jam_ke' => $slot['jam_ke'],
                'jam_mulai' => $slot['jam_mulai'],
                'jam_selesai' => $slot['jam_selesai'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op or keep slots
    }
};
