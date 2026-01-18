<?php

namespace App\Filament\Resources\CkSetResource\RelationManagers;

use App\Models\CkHead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HeadsRelationManager extends RelationManager
{
    protected static string $relationship = 'heads';

    protected static ?string $title = 'Çalışma Kağıtları';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->label('Bilanço Satırı')
                    ->relationship('bilancoRow', 'account_name', fn (Builder $query, $get) => 
                        $get('../../bilanco_import_id') 
                            ? $query->where('bilanco_import_id', $get('../../bilanco_import_id'))
                            : $query->whereRaw('1 = 0')
                    )
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('baslik')
            ->columns([
                Tables\Columns\TextColumn::make('order_no')
                    ->label('Sıra')
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
                Tables\Columns\TextColumn::make('full_path')
                    ->label('Path')
                    ->limit(50)
                    ->toggleable(),
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Serbest CK oluştururken varsayılan değerler
                        if (!isset($data['ck_type'])) {
                            $data['ck_type'] = 'serbest';
                        }
                        if (!isset($data['order_no'])) {
                            $maxOrder = CkHead::where('ck_set_id', $this->ownerRecord->id)
                                ->max('order_no') ?? 0;
                            $data['order_no'] = $maxOrder + 1;
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Görüntüle')
                    ->icon('heroicon-o-eye')
                    ->url(fn (\App\Models\CkHead $record) => 
                        \App\Filament\Resources\CkHeadResource::getUrl('view', ['record' => $record])
                    ),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order_no');
    }
}
