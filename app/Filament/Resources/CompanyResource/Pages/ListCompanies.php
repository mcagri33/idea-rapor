<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Artisan;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->label('IDEA\'dan Firmaları Güncelle')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Firmaları Güncelle')
                ->modalDescription('IDEA API\'den tüm firmalar çekilecek ve veritabanı güncellenecek.')
                ->action(function () {
                    Artisan::call('idea:sync-companies');
                    $output = Artisan::output();

                    \Filament\Notifications\Notification::make()
                        ->title('Firmalar başarıyla güncellendi')
                        ->body($output)
                        ->success()
                        ->send();
                }),
        ];
    }
}
