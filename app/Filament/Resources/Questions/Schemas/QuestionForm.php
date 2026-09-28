<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns()
                    ->columnSpanFull()
                    ->schema([
                        Select::make('subject_id')
                            ->label('Pelajaran')
                            ->relationship('subject', 'name')
                            ->native(false)
                            ->preload()
                            ->required(),

                        TextInput::make('score')
                            ->label('Bobot Nilai')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(0),

                        // Bagian Soal
                        RichEditor::make('payload')
                            ->label('Pertanyaan')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('question-images')
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Keterangan tambahan')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->inline(false)
                            ->default(true)
                            ->required(),
                    ]),

                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('answers')
                            ->relationship()
                            ->label('Pilihan Jawaban')
                            ->addActionLabel('Tambah Pilihan baru')
                            ->defaultItems(1)
                            ->minItems(2)
                            ->maxItems(8)
                            ->columns(2)
                            ->itemLabel(function (string $uuid, Repeater $component): string {
                                $keys = array_keys($component->getRawState() ?? []);
                                $index = array_search($uuid, $keys, true);

                                return 'Pilihan ' . chr(65 + (int) $index);
                            })
                            ->schema([
                                TextInput::make('text')
                                    ->label('Deskripsi pilihan')
                                    ->required()
                                    ->columnSpanFull(),

                                Toggle::make('is_active')
                                    ->label('Tersedia')
                                    ->default(true),

                                Toggle::make('is_correct')
                                    ->label('Jawaban benar'),
                            ]),
                    ]),
            ]);
    }
}