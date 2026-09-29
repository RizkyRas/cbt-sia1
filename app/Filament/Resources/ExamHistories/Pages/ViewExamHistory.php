<?php

namespace App\Filament\Resources\ExamHistories\Pages;

use App\Filament\Resources\ExamHistories\ExamHistoryResource;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Locked;

class ViewExamHistory extends Page
{
    protected static string $resource = ExamHistoryResource::class;

    protected static ?string $title = 'Detail Hasil Ujian';

    protected string $view = 'filament.resources.exam-histories.pages.view-exam-history';

    #[Locked]
    public array $summary = [];

    #[Locked]
    public array $collections = [];

    public function mount(int|string $record): void
    {
        $history = ExamHistoryResource::getEloquentQuery()
            ->with(['exam', 'student'])
            ->findOrFail($record);

        $groups = $history->reviewGroups();
        $questions = collect($groups)->pluck('soals')->collapse();

        $this->summary = [
            'student' => $history->student->name,
            'nis' => $history->student->nis,
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