<?php

namespace App\Filament\Resources\BilancoImportResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RowsRelationManager extends RelationManager
{
    protected static string $relationship = 'rows';

    protected static ?string $title = 'Bilanço İçeriği';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('account_name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('account_name')
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
            ->filters([
                //
            ])
            ->headerActions([
                // Create/Edit kapalı - sadece görüntüleme
            ])
            ->actions([
                // Edit/Delete kapalı - sadece görüntüleme
            ])
            ->bulkActions([
                // Bulk actions kapalı
            ])
            ->defaultSort('level', 'asc')
            ->paginated([25, 50, 100]);
    }
}
