<?php

namespace App\Filament\Resources\BilancoImportResource\Pages;

use App\Filament\Resources\BilancoImportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBilancoImport extends EditRecord
{
    protected static string $resource = BilancoImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
