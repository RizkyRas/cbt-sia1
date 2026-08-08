<?php

namespace Database\Factories;

use App\Models\Answer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Answer>
 */
class AnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $words = ['jawaban', 'pilihan', 'opsi', 'hasil', 'nilai', 'proses', 'metode', 'cara', 'konsep', 'langkah'];

        return [
            'text' => ucfirst(fake()->randomElement($words)) . ' ' . fake()->numberBetween(1, 100),
            'is_correct' => false,
            'is_active' => true,
        ];
    }
}