<?php

namespace App\Filament\Test\Resources\Exams\Pages;

use App\Filament\Test\Resources\Exams\ExamResource;
use App\Models\Exam;
use App\Models\ExamHistory;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

class StartingExam extends Page
{
    private const PER_PAGE = 10;

    protected static string $resource = ExamResource::class;

    protected string $view = 'filament.test.resources.exams.pages.starting-exam';

    #[Locked]
    public int $historyId;

    #[Locked]
    public array $collections = [];

    // pilihan siswa dengan format [question_id => answer_id]
    public array $selected = [];

    // halaman soal yang sedang dibuka
    public int $currentPage = 1;

    public function mount(Exam $exam): void
    {
        abort_unless($exam->is_available, 404);

        $student = auth()->user()?->students;
        abort_unless($student, 403, 'Akun ini belum terhubung dengan data siswa.');

        $history = ExamHistory::startFor($exam, $student);

        // ujian yang sudah selesai tidak bisa dibuka lagi
        if ($history->isFinished()) {
            Notification::make()
                ->title('Ujian ini sudah kamu kerjakan.')
                ->warning()
                ->send();

            $this->redirect(ExamResource::getUrl('index'));

            return;
        }

        $this->historyId = $history->id;
        $this->collections = $history->questionGroups();
        $this->selected = $history->selectedAnswers();
    }

    // semua soal dijadikan satu daftar berurutan, lengkap dengan nomor, mapel, dan halamannya
    #[Computed]
    public function questions(): array
    {
        $flat = [];
        $no = 1;

        foreach ($this->collections as $group) {
            foreach ($group['soals'] as $soal) {
                $flat[] = $soal + [
                    'no' => $no,
                    'page' => intdiv($no - 1, self::PER_PAGE) + 1,
                    'subject_id' => $group['id'],
                    'subject' => $group['name'],
                ];
                $no++;
            }
        }

        return $flat;
    }

    #[Computed]
    public function totalPages(): int
    {
        return max(1, (int) ceil(count($this->questions) / self::PER_PAGE));
    }

    // soal yang tampil di halaman aktif
    #[Computed]
    public function pageQuestions(): array
    {
        return array_slice(
            $this->questions,
            ($this->currentPage - 1) * self::PER_PAGE,
            self::PER_PAGE,
        );
    }

    #[Computed]
    public function unansweredCount(): int
    {
        return collect($this->questions)
            ->reject(fn ($soal) => isset($this->selected[$soal['id']]))
            ->count();
    }

    public function goToPage(int $page): void
    {
        $this->currentPage = min(max(1, $page), $this->totalPages);

        $this->js('window.scrollTo({ top: 0, behavior: "smooth" })');
    }

    public function nextPage(): void
    {
        $this->goToPage($this->currentPage + 1);
    }

    public function previousPage(): void
    {
        $this->goToPage($this->currentPage - 1);
    }

    // dipanggil tiap radio diklik
    public function choose(int $questionId, int $answerId): void
    {
        $this->selected[$questionId] = $answerId;

        ExamHistory::find($this->historyId)
            ?->saveAnswer($questionId, $answerId);
    }

    // tombol Selesai
    public function finish(): void
    {
        $history = ExamHistory::findOrFail($this->historyId);

        if (! $history->isFinished()) {
            $history->finish();

            Notification::make()
                ->title('Ujian selesai')
                ->body("Nilai kamu: {$history->score}")
                ->success()
                ->send();
        }

        $this->redirect(ExamResource::getUrl('index'));
    }
}