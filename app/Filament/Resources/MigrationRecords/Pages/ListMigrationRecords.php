<?php

namespace App\Filament\Resources\MigrationRecords\Pages;

use App\Filament\Resources\MigrationRecords\MigrationRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMigrationRecords extends ListRecords
{
    protected static string $resource = MigrationRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
