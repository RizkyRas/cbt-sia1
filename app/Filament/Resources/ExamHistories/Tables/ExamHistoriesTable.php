<?php

namespace App\Filament\Resources\ExamHistories\Tables;

use App\Filament\Resources\ExamHistories\ExamHistoryResource;
use App\Models\Exam;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExamHistoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['student', 'exam'])
                ->latest('finished_at'))
            ->columns([
                TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.nis')
                    ->label('NIS')
                    ->searchable(),
                TextColumn::make('exam.title')
                    ->label('Ujian')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('score')
                    ->label('Nilai')
                    ->numeric()
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
            ->defaultSort('finished_at', 'desc')
            ->filters([
                SelectFilter::make('exam_id')
                    ->label('Ujian')
                    ->options(fn () => Exam::query()->pluck('title', 'id')),
                TernaryFilter::make('is_passed')
                    ->label('Status')
                    ->trueLabel('Lulus')
                    ->falseLabel('Gagal')
                    ->placeholder('Semua status'),
            ])
            ->recordUrl(fn ($record) => ExamHistoryResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('lihat')
                    ->label('Lihat detail')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn ($record) => ExamHistoryResource::getUrl('view', ['record' => $record])),
            ]);
    }
}