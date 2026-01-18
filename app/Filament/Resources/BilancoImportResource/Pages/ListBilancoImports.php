<?php

namespace App\Filament\Resources\BilancoImportResource\Pages;

use App\Filament\Resources\BilancoImportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBilancoImports extends ListRecords
{
    protected static string $resource = BilancoImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
