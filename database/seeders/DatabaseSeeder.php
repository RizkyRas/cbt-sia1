<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'ikoyy',
            'email' => 'mr.rizkyrawr@gmail.com',
            'username' => 'ikoyy2',
            'is_staff' => true,
            // use Illuminate\Support\Facades\Hash; <-- import di atas
            'password' => Hash::make('rahasia'),
        ]);

        User::factory()->create([
            'name' => 'rizky rawrr',
            'email' => 'rizkyanugrah@gmail.com',
            'username' => 'ikoyy',
            'is_staff' => true,
            'password' => Hash::make('rahasia'),
        ]);

        // Daftar 3 mata pelajaran
        $subjects = ['PJOK', 'Sejarah', 'Matematika'];

        foreach ($subjects as $subjectName) {
            // Buat 1 mapel
            $subject = Subject::create([
                'name' => $subjectName,
                'is_active' => true,
            ]);

            // Buat 150 soal untuk mapel ini
            for ($i = 0; $i < 150; $i++) {
                $question = Question::factory()->create([
                    'subject_id' => $subject->id,
                ]);

                // Buat 4 jawaban (A-D) untuk soal ini
                $letters = ['A', 'B', 'C', 'D'];
                $correctIndex = rand(0, 3); // pilih acak jawaban mana yang benar (0-3)

                for ($j = 0; $j < 4; $j++) {
                    Answer::factory()->create([
                        'question_id' => $question->id,
                        'option' => $letters[$j],
                        'is_correct' => $j === $correctIndex,
                    ]);
                }
            }
        }
    }
}