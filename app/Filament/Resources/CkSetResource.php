<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CkSetResource\Pages;
use App\Filament\Resources\CkSetResource\RelationManagers;
use App\Models\CkSet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CkSetResource extends Resource
{
    protected static ?string $model = CkSet::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Çalışma Kağıdı Setleri';

    protected static ?string $modelLabel = 'Çalışma Kağıdı Seti';

    protected static ?string $pluralModelLabel = 'Çalışma Kağıdı Setleri';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'company')
                    ->label('Firma')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\DatePicker::make('donem_tarihi')
                    ->label('Dönem Tarihi')
                    ->required()
                    ->displayFormat('d.m.Y')
                    ->native(false),
                Forms\Components\Select::make('bilanco_import_id')
                    ->relationship('bilancoImport', 'donem', fn ($query, $get) => 
                        $query->where('company_id', $get('company_id'))
                    )
                    ->label('Bilanço İmport')
                    ->nullable()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('status')
                    ->label('Durum')
                    ->options([
                        'draft' => 'Taslak',
                        'completed' => 'Tamamlandı',
                    ])
                    ->required()
                    ->default('draft'),
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
                Tables\Columns\TextColumn::make('donem_tarihi')
                    ->label('Dönem')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Taslak',
                        'completed' => 'Tamamlandı',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('heads_count')
                    ->label('CK Sayısı')
                    ->counts('heads')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bilancoImport.donem')
                    ->label('Bilanço Dönem')
                    ->sortable()
                    ->toggleable(),
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
                        'draft' => 'Taslak',
                        'completed' => 'Tamamlandı',
                    ]),
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Firma')
                    ->relationship('company', 'company'),
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
            RelationManagers\HeadsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCkSets::route('/'),
            'create' => Pages\CreateCkSet::route('/create'),
            'view' => Pages\ViewCkSet::route('/{record}'),
            'edit' => Pages\EditCkSet::route('/{record}/edit'),
        ];
    }
}
