<?php

namespace App\Filament\Resources\ExamHistories\Pages;

use App\Filament\Resources\ExamHistories\ExamHistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListExamHistories extends ListRecords
{
    protected static string $resource = ExamHistoryResource::class;
}