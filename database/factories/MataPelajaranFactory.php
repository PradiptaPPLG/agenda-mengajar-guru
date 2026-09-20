<?php

namespace Database\Factories;

use App\Models\MataPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MataPelajaran>
 */
class MataPelajaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Mata Pelajaran '.fake()->unique()->numberBetween(1, 9999),
            'kode' => 'MP-'.fake()->unique()->numberBetween(100, 9999),
            'jenis' => 'normatif',
        ];
    }
}
