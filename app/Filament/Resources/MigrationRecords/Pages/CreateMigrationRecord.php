<?php

namespace App\Filament\Resources\MigrationRecords\Pages;

use App\Filament\Resources\MigrationRecords\MigrationRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMigrationRecord extends CreateRecord
{
    protected static string $resource = MigrationRecordResource::class;
}
