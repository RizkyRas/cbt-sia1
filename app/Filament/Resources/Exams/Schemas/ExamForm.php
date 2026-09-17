<?php

namespace App\Filament\Resources\Exams\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('duration')
                    ->required()
                    ->numeric(),
                TextInput::make('threshold')
                    ->required()
                    ->numeric()
                    ->default(50.0),
                Toggle::make('exact_time')
                    ->live()
                    ->required(),
                DateTimePicker::make('started_at')
                    ->required(),
                DateTimePicker::make('expired_at')
                    ->seconds(false)
                    ->native(false)
                    ->displayFormat('d F Y, H:i')
                    ->hidden(fn (Get $get): bool => $get('exact_time')),
                Toggle::make('is_available')
                    ->required()
                    ->default(true),
            ]);
    }
}