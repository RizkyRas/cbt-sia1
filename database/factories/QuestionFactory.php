<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjects = ['bagaimana', 'apa', 'mengapa', 'kapan', 'siapa', 'di mana'];
        $topics = ['proses ini', 'kejadian tersebut', 'konsep dasar ini', 'peristiwa ini', 'hasil perhitungan ini', 'materi ini'];
        $endings = ['terjadi', 'dapat dijelaskan', 'berlangsung', 'dapat dipahami', 'diselesaikan', 'diterapkan'];

        return [
            'payload' => ucfirst(fake()->randomElement($subjects)) . ' ' . fake()->randomElement($topics) . ' ' . fake()->randomElement($endings) . '?',
            'score' => 1,
            'description' => fake()->boolean(30) ? 'Pembahasan singkat mengenai soal ini.' : null,
            'is_active' => true,
        ];
    }
}