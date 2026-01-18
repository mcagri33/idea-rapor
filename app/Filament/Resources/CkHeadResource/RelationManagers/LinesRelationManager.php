<?php

namespace App\Filament\Resources\CkHeadResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * CK Satırları Relation Manager
 * Inline editing ile tablo satırlarını düzenleme
 */
class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'Satırlar';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('satir_adi')
                    ->label('Satır Adı')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('cari')
                    ->label('Cari Dönem')
                    ->numeric()
                    ->step(0.01)
                    ->nullable(),
                Forms\Components\TextInput::make('onceki')
                    ->label('Önceki Dönem')
                    ->numeric()
                    ->step(0.01)
                    ->nullable(),
                Forms\Components\TextInput::make('acilis')
                    ->label('Açılış Bakiyeleri')
                    ->numeric()
                    ->step(0.01)
                    ->nullable(),
                Forms\Components\TextInput::make('fark')
                    ->label('Fark')
                    ->numeric()
                    ->step(0.01)
                    ->disabled()
                    ->dehydrated(false)
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $cari = $get('cari') ?? 0;
                        $onceki = $get('onceki') ?? 0;
                        $set('fark', $cari - $onceki);
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('satir_adi')
            ->columns([
                Tables\Columns\TextColumn::make('satir_adi')
                    ->label('Satır Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cari')
                    ->label('Cari')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: ',',
                        thousandsSeparator: '.',
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('onceki')
                    ->label('Önceki')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: ',',
                        thousandsSeparator: '.',
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('acilis')
                    ->label('Açılış')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: ',',
                        thousandsSeparator: '.',
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('fark')
                    ->label('Fark')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: ',',
                        thousandsSeparator: '.',
                    )
                    ->color(fn ($record) => ($record->fark ?? 0) >= 0 ? 'success' : 'danger')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id');
    }
}
