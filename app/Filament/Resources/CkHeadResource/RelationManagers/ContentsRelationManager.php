<?php

namespace App\Filament\Resources\CkHeadResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * CK İçerikleri Relation Manager
 * Metinsel alanlar için rich text editor
 */
class ContentsRelationManager extends RelationManager
{
    protected static string $relationship = 'contents';

    protected static ?string $title = 'İçerikler';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('section')
                    ->label('Bölüm')
                    ->options([
                        'aciklama' => 'Açıklama',
                        'denetim_proseduru' => 'Denetim Prosedürü',
                        'bulgular' => 'Bulgular',
                        'sonuc' => 'Sonuç',
                    ])
                    ->required()
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(),
                Forms\Components\RichEditor::make('content')
                    ->label('İçerik')
                    ->nullable()
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'link',
                        'bulletList',
                        'orderedList',
                        'blockquote',
                        'codeBlock',
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('section')
            ->columns([
                Tables\Columns\TextColumn::make('section')
                    ->label('Bölüm')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'aciklama' => 'Açıklama',
                        'denetim_proseduru' => 'Denetim Prosedürü',
                        'bulgular' => 'Bulgular',
                        'sonuc' => 'Sonuç',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aciklama' => 'info',
                        'denetim_proseduru' => 'warning',
                        'bulgular' => 'danger',
                        'sonuc' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('content')
                    ->label('İçerik')
                    ->limit(100)
                    ->html()
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('section')
                    ->label('Bölüm')
                    ->options([
                        'aciklama' => 'Açıklama',
                        'denetim_proseduru' => 'Denetim Prosedürü',
                        'bulgular' => 'Bulgular',
                        'sonuc' => 'Sonuç',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Varsayılan section seçimi
                        if (!isset($data['section'])) {
                            $existingSections = $this->ownerRecord->contents()->pluck('section')->toArray();
                            $allSections = ['aciklama', 'denetim_proseduru', 'bulgular', 'sonuc'];
                            $availableSections = array_diff($allSections, $existingSections);
                            if (!empty($availableSections)) {
                                $data['section'] = reset($availableSections);
                            }
                        }
                        return $data;
                    }),
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
            ->defaultSort('section');
    }
}
