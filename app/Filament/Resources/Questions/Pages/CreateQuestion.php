<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    protected function afterCreate(): void
    {
        $this->assignAnswerOptions();
    }

    protected function assignAnswerOptions(): void
    {
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        $this->record->answers()
            ->orderBy('id')
            ->get()
            ->each(function ($answer, $index) use ($letters) {
                $answer->update([
                    'option' => $letters[$index] ?? null,
                ]);
            });
    }
}