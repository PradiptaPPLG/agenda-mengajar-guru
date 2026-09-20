<?php

namespace Database\Factories;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JadwalPelajaran>
 */
class JadwalPelajaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kelas_id' => Kelas::factory(),
            'guru_id' => User::factory()->state(['role' => 'guru']),
            'mata_pelajaran_id' => MataPelajaran::factory(),
            'hari' => 1,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
        ];
    }
}
