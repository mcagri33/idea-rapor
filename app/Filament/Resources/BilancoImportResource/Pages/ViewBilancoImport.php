<?php

namespace App\Filament\Resources\BilancoImportResource\Pages;

use App\Filament\Resources\BilancoImportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewBilancoImport extends ViewRecord
{
    protected static string $resource = BilancoImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('İmport Bilgileri')
                    ->schema([
                        Infolists\Components\TextEntry::make('company.company')
                            ->label('Firma'),
                        Infolists\Components\TextEntry::make('donem')
                            ->label('Dönem'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Durum')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'completed' => 'success',
                                'processing' => 'warning',
                                'failed' => 'danger',
                                'pending' => 'secondary',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'completed' => 'Tamamlandı',
                                'processing' => 'İşleniyor',
                                'failed' => 'Hata',
                                'pending' => 'Beklemede',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('imported_rows')
                            ->label('İmport Edilen Satır'),
                        Infolists\Components\TextEntry::make('total_rows')
                            ->label('Toplam Satır'),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->label('Tamamlanma Tarihi')
                            ->dateTime('d.m.Y H:i'),
                        Infolists\Components\TextEntry::make('error_message')
                            ->label('Hata Mesajı')
                            ->visible(fn ($record) => !empty($record->error_message)),
                    ])
                    ->columns(2),
            ]);
    }
}
