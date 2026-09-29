<?php

namespace App\Filament\Test\Resources\ExamHistories;

use App\Filament\Test\Resources\ExamHistories\Pages\ListExamHistories;
use App\Filament\Test\Resources\ExamHistories\Pages\ViewExamHistory;
use App\Models\ExamHistory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExamHistoryResource extends Resource
{
    protected static ?string $model = ExamHistory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Riwayat';

    protected static ?string $modelLabel = 'riwayat ujian';

    protected static ?string $pluralModelLabel = 'Riwayat Ujian';

    protected static ?string $slug = 'riwayat';

    protected static ?int $navigationSort = 2;

    // siswa hanya boleh melihat riwayatnya sendiri, dan hanya ujian yang sudah selesai
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('student_id', auth()->user()?->students?->id)
            ->whereNotNull('finished_at');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('exam')->latest('finished_at'))
            ->columns([
                TextColumn::make('exam.title')
                    ->label('Ujian')
                    ->searchable(),
                TextColumn::make('score')
                    ->label('Nilai')
                    ->sortable(),
                TextColumn::make('is_passed')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Lulus' : 'Gagal')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                TextColumn::make('finished_at')
                    ->label('Selesai')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordUrl(fn (ExamHistory $record) => ExamHistoryResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('lihat')
                    ->label('Lihat hasil')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (ExamHistory $record) => ExamHistoryResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('Belum ada riwayat ujian');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExamHistories::route('/'),
            'view' => ViewExamHistory::route('/{record}'),
        ];
    }
}