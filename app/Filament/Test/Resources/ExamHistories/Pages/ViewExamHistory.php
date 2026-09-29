<?php

namespace App\Filament\Test\Resources\ExamHistories\Pages;

use App\Filament\Test\Resources\ExamHistories\ExamHistoryResource;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Locked;

class ViewExamHistory extends Page
{
    protected static string $resource = ExamHistoryResource::class;

    protected static ?string $title = 'Hasil Ujian';

    protected string $view = 'filament.test.resources.exam-histories.pages.view-exam-history';

    #[Locked]
    public array $summary = [];

    #[Locked]
    public array $collections = [];

    public function mount(int|string $record): void
    {
        // query resource sudah dibatasi: milik siswa yang login dan sudah selesai
        $history = ExamHistoryResource::getEloquentQuery()
            ->with('exam')
            ->findOrFail($record);

        $groups = $history->reviewGroups();
        $questions = collect($groups)->pluck('soals')->collapse();

        $this->summary = [
            'exam' => $history->exam->title,
            'score' => $history->score,
            'threshold' => $history->exam->threshold,
            'is_passed' => (bool) $history->is_passed,
            'finished_at' => $history->finished_at->translatedFormat('d F Y, H:i'),
            'correct' => $questions->where('is_correct', true)->count(),
            'total' => $questions->count(),
        ];

        $this->collections = $groups;
    }
}