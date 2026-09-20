<?php

namespace Database\Factories;

use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'X RPL '.fake()->unique()->numberBetween(1, 9999),
            'tingkat' => 10,
            'tahun_ajaran' => '2026/2027',
        ];
    }
}
