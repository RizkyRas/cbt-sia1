<?php

namespace App\Filament\Resources\ExamHistories;

use App\Filament\Resources\ExamHistories\Pages\ListExamHistories;
use App\Filament\Resources\ExamHistories\Pages\ViewExamHistory;
use App\Filament\Resources\ExamHistories\Tables\ExamHistoriesTable;
use App\Models\ExamHistory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExamHistoryResource extends Resource
{
    protected static ?string $model = ExamHistory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Hasil Ujian';

    protected static ?string $modelLabel = 'hasil ujian';

    protected static ?string $pluralModelLabel = 'Hasil Ujian';

    protected static ?string $slug = 'hasil-ujian';

    protected static ?int $navigationSort = 3;

    // admin hanya melihat ujian yang sudah selesai dikerjakan siswa
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('finished_at');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return ExamHistoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExamHistories::route('/'),
            'view' => ViewExamHistory::route('/{record}'),
        ];
    }
}