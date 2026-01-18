<?php

namespace App\Filament\Resources\BilancoImportResource\Pages;

use App\Filament\Resources\BilancoImportResource;
use App\Services\CK\CkGenerateService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;

class ViewBilancoImport extends ViewRecord
{
    protected static string $resource = BilancoImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateCk')
                ->label('CK Oluştur')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Çalışma Kağıdı Oluştur')
                ->modalDescription('Bu bilanço import\'undan çalışma kağıtları oluşturulacak. Devam etmek istiyor musunuz?')
                ->modalSubmitActionLabel('Oluştur')
                ->action(function () {
                    try {
                        $service = new CkGenerateService();
                        $ckSet = $service->generateFromBilanco($this->record->id);
                        
                        Notification::make()
                            ->title('Başarılı')
                            ->success()
                            ->body('Çalışma kağıtları başarıyla oluşturuldu. ' . $ckSet->heads()->count() . ' adet CK oluşturuldu.')
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('CK Set\'i Görüntüle')
                                    ->url(route('filament.admin.resources.ck-sets.view', $ckSet))
                                    ->button(),
                            ])
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Hata')
                            ->danger()
                            ->body('CK oluşturulurken bir hata oluştu: ' . $e->getMessage())
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->status === 'completed' && !$this->record->ckSet()->exists()),
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
