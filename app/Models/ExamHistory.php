<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExamHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'score' => 'decimal:2',
        'is_passed' => 'boolean',
    ];

    // relasi inverse ke model Exam
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    // relasi inverse ke model Student
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // relasi 1 to many dengan model ExamHistoryAnswer
    public function answers(): HasMany
    {
        return $this->hasMany(ExamHistoryAnswer::class);
    }

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }

    // mulai ujian: buat history saat pertama kali, soal langsung dikunci per siswa
    public static function startFor(Exam $exam, Student $student): self
    {
        return DB::transaction(function () use ($exam, $student) {
            $history = static::firstOrCreate(
                ['exam_id' => $exam->id, 'student_id' => $student->id],
                ['started_at' => now()],
            );

            if ($history->wasRecentlyCreated) {
                $history->generateQuestions($exam);
            }

            return $history;
        });
    }

    // undi soal per mapel sesuai qty, lalu simpan ke exam_history_answers
    public function generateQuestions(Exam $exam): void
    {
        $order = 0;

        foreach ($exam->subjects as $mapel) {
            $questionIds = $mapel->questions()
                ->where('is_active', true)
                ->inRandomOrder()
                ->limit($mapel->pivot->qty)
                ->pluck('id');

            foreach ($questionIds as $questionId) {
                $this->answers()->create([
                    'question_id' => $questionId,
                    'sort_order' => $order++,
                ]);
            }
        }
    }

    // urutan pilihan teracak tapi stabil per siswa (sama di halaman ujian dan riwayat)
    protected function sortAnswers(Collection $answers, int $questionId): Collection
    {
        return $answers
            ->sortBy(fn ($answer) => crc32($this->id . '-' . $questionId . '-' . $answer->id))
            ->values();
    }

    // susun data soal untuk halaman ujian (tanpa is_correct)
    public function questionGroups(): array
    {
        $rows = $this->answers()
            ->with([
                'question' => fn ($query) => $query->withTrashed()->with([
                    'subject' => fn ($subject) => $subject->withTrashed(),
                    'answers' => fn ($answers) => $answers->where('is_active', true),
                ]),
            ])
            ->orderBy('sort_order')
            ->get();

        return $rows
            ->groupBy(fn ($row) => $row->question->subject_id)
            ->map(function ($group) {
                $subject = $group->first()->question->subject;

                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'soals' => $group->map(function ($row) {
                        $question = $row->question;

                        return [
                            'id' => $question->id,
                            'payload' => $question->payload,
                            'answers' => $this->sortAnswers($question->answers, $question->id)
                                ->map(fn ($answer) => [
                                    'id' => $answer->id,
                                    'text' => $answer->text,
                                ])
                                ->values()
                                ->all(),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    // susun data soal untuk halaman riwayat (dengan tanda jawaban benar dan pilihan siswa)
    public function reviewGroups(): array
    {
        $rows = $this->answers()
            ->with([
                'question' => fn ($query) => $query->withTrashed()->with([
                    'subject' => fn ($subject) => $subject->withTrashed(),
                    'answers' => fn ($answers) => $answers->where('is_active', true),
                ]),
            ])
            ->orderBy('sort_order')
            ->get();

        return $rows
            ->groupBy(fn ($row) => $row->question->subject_id)
            ->map(function ($group) {
                $subject = $group->first()->question->subject;

                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'soals' => $group->map(function ($row) {
                        $question = $row->question;

                        $answers = $this->sortAnswers($question->answers, $question->id)
                            ->map(fn ($answer) => [
                                'id' => $answer->id,
                                'text' => $answer->text,
                                'is_correct' => (bool) $answer->is_correct,
                                'is_chosen' => (int) $answer->id === (int) $row->answer_id,
                            ])
                            ->values()
                            ->all();

                        return [
                            'id' => $question->id,
                            'payload' => $question->payload,
                            'description' => $question->description,
                            'is_answered' => $row->answer_id !== null,
                            'is_correct' => collect($answers)
                                ->contains(fn ($a) => $a['is_chosen'] && $a['is_correct']),
                            'answers' => $answers,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    // jawaban yang sudah dipilih dengan format [question_id => answer_id]
    public function selectedAnswers(): array
    {
        return $this->answers()
            ->whereNotNull('answer_id')
            ->pluck('answer_id', 'question_id')
            ->all();
    }

    // simpan pilihan siswa untuk satu soal
    public function saveAnswer(int $questionId, int $answerId): void
    {
        if ($this->isFinished()) {
            return;
        }

        // pilihan harus benar-benar milik soal tersebut
        $valid = Answer::where('id', $answerId)
            ->where('question_id', $questionId)
            ->exists();

        if (! $valid) {
            return;
        }

        $this->answers()
            ->where('question_id', $questionId)
            ->update(['answer_id' => $answerId]);
    }

    // selesaikan ujian: hitung nilai (persen) lalu bandingkan dengan threshold
    public function finish(): void
    {
        if ($this->isFinished()) {
            return;
        }

        $rows = $this->answers()
            ->with([
                'question' => fn ($query) => $query->withTrashed(),
                'answer' => fn ($query) => $query->withTrashed(),
            ])
            ->get();

        $totalScore = 0;
        $earnedScore = 0;

        foreach ($rows as $row) {
            $totalScore += $row->question->score;

            if ($row->answer?->is_correct) {
                $earnedScore += $row->question->score;
            }
        }

        $score = $totalScore > 0
            ? round($earnedScore / $totalScore * 100, 2)
            : 0;

        $this->update([
            'finished_at' => now(),
            'score' => $score,
            'is_passed' => $score >= (float) $this->exam->threshold,
        ]);
    }
}