<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BilancoImportResource\Pages;
use App\Models\BilancoImport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BilancoImportResource extends Resource
{
    protected static ?string $model = BilancoImport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Bilanço İmportları';

    protected static ?string $modelLabel = 'Bilanço İmport';

    protected static ?string $pluralModelLabel = 'Bilanço İmportları';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'company')
                    ->label('Firma')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('donem')
                    ->label('Dönem')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->label('Durum')
                    ->required()
                    ->disabled(),
                Forms\Components\Textarea::make('error_message')
                    ->label('Hata Mesajı')
                    ->columnSpanFull()
                    ->disabled(),
                Forms\Components\TextInput::make('total_rows')
                    ->label('Toplam Satır')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('imported_rows')
                    ->label('İmport Edilen Satır')
                    ->numeric()
                    ->disabled(),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('Tamamlanma Tarihi')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.company')
                    ->label('Firma')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('donem')
                    ->label('Dönem')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
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
                Tables\Columns\TextColumn::make('imported_rows')
                    ->label('İmport Edilen')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_rows')
                    ->label('Toplam Satır')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Tamamlanma Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'pending' => 'Beklemede',
                        'processing' => 'İşleniyor',
                        'completed' => 'Tamamlandı',
                        'failed' => 'Hata',
                    ]),
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Firma')
                    ->relationship('company', 'company'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBilancoImports::route('/'),
            'view' => Pages\ViewBilancoImport::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Sadece import ile oluşturulabilir
    }
}
