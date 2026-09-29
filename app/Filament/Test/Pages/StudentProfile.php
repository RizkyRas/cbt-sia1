<?php

namespace App\Filament\Test\Pages;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Override;

class StudentProfile extends EditProfile
{
    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Foto Profil')
                    ->schema([
                        FileUpload::make('photo_path')
                            ->label('Foto')
                            ->avatar()
                            ->image()
                            ->disk('public')
                            ->directory('avatars')
                            ->maxSize(2048)
                            ->helperText('Format JPG atau PNG, maksimal 2 MB.'),
                    ]),

                Section::make('Ganti Password')
                    ->description('Kosongkan bagian ini kalau tidak ingin mengganti password.')
                    ->schema([
                        $this->getCurrentPasswordFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ]),
            ]);
    }
}