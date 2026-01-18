<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Firmalar';

    protected static ?string $modelLabel = 'Firma';

    protected static ?string $pluralModelLabel = 'Firmalar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company')
                    ->label('Şirket')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-posta')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('synced_at')
                    ->label('Son Senkron')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),

                Tables\Columns\TextColumn::make('external_id')
                    ->label('External ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiflik')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\Action::make('download_template')
                    ->label('Bilanço Excel Örneği')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function () {
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\BilancoTemplateExport(),
                            'bilanco_ornek.xlsx'
                        );
                    }),
                Tables\Actions\Action::make('import_bilanco')
                    ->label('Bilanço Import Et')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('file')
                            ->label('Excel Dosyası')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->required()
                            ->disk('local')
                            ->directory('bilanco-imports'),
                        \Filament\Forms\Components\DatePicker::make('donem')
                            ->label('Dönem')
                            ->required()
                            ->format('Y-m-d')
                            ->displayFormat('d.m.Y'),
                    ])
                    ->action(function (array $data, \Illuminate\Database\Eloquent\Model $record) {
                        $filePath = storage_path('app/' . $data['file']);
                        $donem = $data['donem'];

                        try {
                            $bilancoImport = \App\Models\BilancoImport::create([
                                'company_id' => $record->id,
                                'donem' => $donem,
                                'status' => 'pending',
                            ]);

                            $importService = app(\App\Services\Bilanco\BilancoImportService::class);
                            $result = $importService->import($bilancoImport, $filePath);

                            \Filament\Notifications\Notification::make()
                                ->title('Bilanço başarıyla import edildi')
                                ->body("Toplam {$result['total']} satır işlendi, {$result['imported']} satır kaydedildi.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Import hatası')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                //
            ]);
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
            'index' => Pages\ListCompanies::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Super-admin rolü kontrolü (permission yoksa bile erişim verebilir)
        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return true;
        }

        // Permission kontrolü (permission henüz yüklenmemiş olabilir)
        try {
            return $user->hasPermissionTo('companies.viewAny', 'web') 
                || $user->hasPermissionTo('companies.*', 'web');
        } catch (\Exception $e) {
            // Permission henüz oluşturulmamışsa false döndür
            return false;
        }
    }
}
