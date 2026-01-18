<?php

namespace App\Filament\Resources\CkHeadResource\Pages;

use App\Filament\Resources\CkHeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCkHeads extends ListRecords
{
    protected static string $resource = CkHeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
