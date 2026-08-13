<?php

namespace App\Filament\Resources\MigrationRecords\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MigrationRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('migration')
                    ->label('Nama Migration')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('batch')
                    ->label('Batch')
                    ->required()
                    ->numeric(),
            ]);
    }
}