<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StudentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reg_year')
                    ->label('Stambuk'),
                TextEntry::make('nis')
                    ->label('NIS'),
                TextEntry::make('name')
                    ->label('Nama Siswa'),
                TextEntry::make('gender')
                    ->label('Jenis Kelamin')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Laki-laki' : 'Perempuan'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}