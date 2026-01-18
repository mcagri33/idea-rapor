<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CkHeadResource\Pages;
use App\Filament\Resources\CkHeadResource\RelationManagers;
use App\Models\CkHead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CkHeadResource extends Resource
{
    protected static ?string $model = CkHead::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Çalışma Kağıtları';

    protected static ?string $modelLabel = 'Çalışma Kağıdı';

    protected static ?string $pluralModelLabel = 'Çalışma Kağıtları';

    protected static bool $shouldRegisterNavigation = false; // CkSet içinden erişilecek

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('ck_set_id')
                    ->relationship('ckSet', 'id', fn ($query) => 
                        $query->with('company')->latest()
                    )
                    ->label('CK Set')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                        $record->company->company . ' - ' . $record->donem_tarihi->format('d.m.Y')
                    ),
                Forms\Components\TextInput::make('baslik')
                    ->label('Başlık')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('ck_type')
                    ->label('CK Tipi')
                    ->options([
                        'bilanco' => 'Bilanço',
                        'serbest' => 'Serbest',
                    ])
                    ->required()
                    ->default('serbest')
                    ->reactive()
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                        $state === 'serbest' ? $set('bilanco_row_id', null) : null
                    ),
                Forms\Components\Select::make('bilanco_row_id')
                    ->relationship('bilancoRow', 'account_name')
                    ->label('Bilanço Satırı')
                    ->nullable()
                    ->visible(fn ($get) => $get('ck_type') === 'bilanco')
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('full_path')
                    ->label('Tam Path')
                    ->nullable()
                    ->visible(fn ($get) => $get('ck_type') === 'bilanco')
                    ->disabled(),
                Forms\Components\TextInput::make('order_no')
                    ->label('Sıra No')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ckSet.company.company')
                    ->label('Firma')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('baslik')
                    ->label('Başlık')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('ck_type')
                    ->label('Tip')
                    ->colors([
                        'primary' => 'bilanco',
                        'success' => 'serbest',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'bilanco' => 'Bilanço',
                        'serbest' => 'Serbest',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('order_no')
                    ->label('Sıra')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lines_count')
                    ->label('Satır Sayısı')
                    ->counts('lines')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ck_type')
                    ->label('Tip')
                    ->options([
                        'bilanco' => 'Bilanço',
                        'serbest' => 'Serbest',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            RelationManagers\LinesRelationManager::class,
            RelationManagers\ContentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCkHeads::route('/'),
            'create' => Pages\CreateCkHead::route('/create'),
            'view' => Pages\ViewCkHead::route('/{record}'),
            'edit' => Pages\EditCkHead::route('/{record}/edit'),
        ];
    }
}
