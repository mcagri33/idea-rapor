<?php

namespace App\Filament\Resources\CkSetResource\Pages;

use App\Filament\Resources\CkSetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCkSets extends ListRecords
{
    protected static string $resource = CkSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
