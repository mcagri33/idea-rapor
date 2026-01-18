<?php

namespace App\Filament\Resources\CkSetResource\Pages;

use App\Filament\Resources\CkSetResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCkSet extends ViewRecord
{
    protected static string $resource = CkSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
