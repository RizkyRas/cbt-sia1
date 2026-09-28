<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
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