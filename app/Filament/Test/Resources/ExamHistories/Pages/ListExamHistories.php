<?php

namespace App\Filament\Test\Resources\ExamHistories\Pages;

use App\Filament\Test\Resources\ExamHistories\ExamHistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListExamHistories extends ListRecords
{
    protected static string $resource = ExamHistoryResource::class;
}