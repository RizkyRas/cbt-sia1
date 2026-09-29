<?php

namespace App\Filament\Test\Resources\Exams;

use App\Filament\Test\Resources\ExamHistories\ExamHistoryResource;
use App\Filament\Test\Resources\Exams\Pages\ManageExams;
use App\Filament\Test\Resources\Exams\Pages\StartingExam;
use App\Models\Exam;
use App\Models\ExamHistory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Sesi Ujian';

    protected static ?string $modelLabel = 'Ujian';

    // siswa tidak boleh membuat ujian (itu tugas admin)
    public static function canCreate(): bool
    {
        return false;
    }

    // [exam_id => history_id] untuk ujian yang sudah selesai dikerjakan siswa yang login
    // di-cache per request supaya tidak query berulang untuk tiap baris
    protected static function finishedHistories(): Collection
    {
        static $cache = null;

        return $cache ??= ExamHistory::query()
            ->where('student_id', auth()->user()?->students?->id)
            ->whereNotNull('finished_at')
            ->pluck('id', 'exam_id');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $now = now();
                $query
                    ->where('is_available', true)
                    ->where(function (Builder $query) use ($now) {
                        $query
                            // tepat waktu: tampil sampai started_at + durasi habis
                            ->where(fn (Builder $query) => $query
                                ->where('exact_time', true)
                                ->whereRaw(
                                    'DATE_ADD(started_at, INTERVAL duration MINUTE) >= ?',
                                    [$now]
                                ))
                            // tidak tepat waktu: tampil sampai expired_at (kosong = tanpa batas)
                            ->orWhere(fn (Builder $query) => $query
                                ->where('exact_time', false)
                                ->where(fn (Builder $query) => $query
                                    ->whereNull('expired_at')
                                    ->orWhere('expired_at', '>=', $now)));
                    });
            })
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Ujian')
                    ->searchable(),
                TextColumn::make('duration')
                    ->label('Durasi')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('threshold')
                    ->label('Min. Score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d F Y, H:i:s')
                    ->sortable(),
            ])
            ->recordActions([
                // belum dikerjakan: tombol mulai
                Action::make('start')
                    ->label('Mulai ujian')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('primary')
                    ->button()
                    ->visible(fn ($record) => ! static::finishedHistories()->has($record->id))
                    ->disabled(fn ($record) =>
                        $record->started_at >= now()
                    )
                    ->url(fn ($record) =>
                        route(
                            StartingExam::getRouteName(),
                            ['exam' => $record]
                        )
                    ),

                // sudah dikerjakan: tanda selesai (tidak bisa diklik)
                Action::make('done')
                    ->label('Sudah dikerjakan')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->button()
                    ->disabled()
                    ->visible(fn ($record) => static::finishedHistories()->has($record->id)),

                // sudah dikerjakan: lihat hasil di halaman riwayat
                Action::make('result')
                    ->label('Lihat hasil')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->button()
                    ->visible(fn ($record) => static::finishedHistories()->has($record->id))
                    ->url(fn ($record) => ExamHistoryResource::getUrl('view', [
                        'record' => static::finishedHistories()->get($record->id),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageExams::route('/'),
            'mulai' => StartingExam::route('/{exam}/mulai'),
        ];
    }
}