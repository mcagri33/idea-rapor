<?php

namespace App\Filament\Resources\CkSetResource\Pages;

use App\Filament\Resources\CkSetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCkSet extends EditRecord
{
    protected static string $resource = CkSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
