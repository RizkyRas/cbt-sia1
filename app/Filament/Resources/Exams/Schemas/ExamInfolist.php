<?php

namespace App\Filament\Resources\Exams\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExamInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),
                TextEntry::make('duration')
                    ->numeric(),
                TextEntry::make('threshold')
                    ->numeric(),
                IconEntry::make('exact_time')
                    ->boolean(),
                TextEntry::make('started_at')
                    ->dateTime(),
                TextEntry::make('expired_at')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('is_available')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}