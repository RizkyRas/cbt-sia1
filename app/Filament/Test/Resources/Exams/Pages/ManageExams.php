<?php

namespace App\Filament\Test\Resources\Exams\Pages;

use App\Filament\Test\Resources\Exams\ExamResource;
use Filament\Resources\Pages\ManageRecords;

class ManageExams extends ManageRecords
{
    protected static string $resource = ExamResource::class;

    // siswa tidak punya tombol di header (tombol Buat Ujian dihapus)
    protected function getHeaderActions(): array
    {
        return [];
    }
}