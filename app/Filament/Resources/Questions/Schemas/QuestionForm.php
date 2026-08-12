<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subject_id')
                    ->relationship('subject', 'name')
                    ->required(),
                Textarea::make('payload')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('score')
                    ->required()
                    ->numeric()
                    ->default(1),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
                Repeater::make('answers')
                    ->relationship()
                    ->label('Pilihan Jawaban')
                    ->schema([
                        Select::make('option')
                            ->label('Opsi')
                            ->options([
                                'A' => 'A',
                                'B' => 'B',
                                'C' => 'C',
                                'D' => 'D',
                            ])
                            ->required(),
                        TextInput::make('text')
                            ->label('Teks jawaban')
                            ->required()
                            ->columnSpan(2),
                        Toggle::make('is_correct')
                            ->label('Jawaban benar')
                            ->live()
                            ->afterStateUpdated(function (bool $state, callable $set, callable $get) {
                                if (! $state) {
                                    return;
                                }

                                $answers = $get('../../answers');

                                foreach ($answers as $key => $answer) {
                                    $set("../../answers.{$key}.is_correct", false);
                                }

                                $set('is_correct', true);
                            }),
                    ])
                    ->columns(4)
                    ->defaultItems(4)
                    ->minItems(4)
                    ->maxItems(4)
                    ->columnSpanFull(),
            ]);
    }
}