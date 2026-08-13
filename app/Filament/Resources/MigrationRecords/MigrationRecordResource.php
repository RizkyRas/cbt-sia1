<?php

namespace App\Filament\Resources\MigrationRecords;

use App\Filament\Resources\MigrationRecords\Pages\CreateMigrationRecord;
use App\Filament\Resources\MigrationRecords\Pages\EditMigrationRecord;
use App\Filament\Resources\MigrationRecords\Pages\ListMigrationRecords;
use App\Filament\Resources\MigrationRecords\Schemas\MigrationRecordForm;
use App\Filament\Resources\MigrationRecords\Tables\MigrationRecordsTable;
use App\Models\MigrationRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MigrationRecordResource extends Resource
{
    protected static ?string $model = MigrationRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'migration';

    public static function form(Schema $schema): Schema
    {
        return MigrationRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MigrationRecordsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMigrationRecords::route('/'),
            'create' => CreateMigrationRecord::route('/create'),
            'edit' => EditMigrationRecord::route('/{record}/edit'),
        ];
    }
}
