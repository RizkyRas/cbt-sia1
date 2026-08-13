<?php

namespace App\Filament\Resources\MigrationRecords\Pages;

use App\Filament\Resources\MigrationRecords\MigrationRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMigrationRecord extends EditRecord
{
    protected static string $resource = MigrationRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
