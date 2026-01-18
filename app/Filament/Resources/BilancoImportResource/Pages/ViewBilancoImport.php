<?php

namespace App\Filament\Resources\BilancoImportResource\Pages;

use App\Filament\Resources\BilancoImportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ViewBilancoImport extends ViewRecord implements HasTable
{
    use InteractsWithTable;

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
                        Infolists\Components\BadgeEntry::make('status')
                            ->label('Durum')
                            ->colors([
                                'success' => 'completed',
                                'warning' => 'processing',
                                'danger' => 'failed',
                                'secondary' => 'pending',
                            ])
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

    public function table(Table $table): Table
    {
        return $table
            ->query(\App\Models\BilancoRow::query()->where('bilanco_import_id', $this->record->id))
            ->columns([
                Tables\Columns\TextColumn::make('account_name')
                    ->label('Hesap Adı')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('path')
                    ->label('Path')
                    ->searchable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('level')
                    ->label('Seviye')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cari_donem')
                    ->label('Cari Dönem')
                    ->money('TRY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('onceki_donem')
                    ->label('Önceki Dönem')
                    ->money('TRY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('acilis_bakiyeleri')
                    ->label('Açılış Bakiyeleri')
                    ->money('TRY')
                    ->sortable(),
            ])
            ->defaultSort('level', 'asc')
            ->paginated([25, 50, 100]);
    }
}
