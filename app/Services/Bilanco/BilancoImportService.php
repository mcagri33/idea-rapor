<?php

namespace App\Services\Bilanco;

use App\Models\BilancoImport;
use App\Models\BilancoRow;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BilancoImportService
{
    /**
     * Import bilanço from Excel file
     *
     * @param BilancoImport $bilancoImport
     * @param string $filePath
     * @return array{imported: int, total: int}
     */
    public function import(BilancoImport $bilancoImport, string $filePath): array
    {
        $bilancoImport->update([
            'status' => 'processing',
            'file_path' => $filePath,
        ]);

        try {
            // Excel dosyasını okurken open_basedir hatası oluşabilir
            // PhpSpreadsheet ZIP arşivini açarken geçici dosyalar oluşturur
            // Bu yüzden Excel::toArray() de hata verebilir
            
            $data = null;
            $worksheet = null;
            $tempFile = null;
            
            // Dosya zaten storage/app içindeyse direkt oku
            // Değilse izin verilen bir dizine kopyala
            $isInStorage = str_starts_with($filePath, storage_path('app'));
            
            if (!$isInStorage) {
                // Dosyayı storage/app/temp'e kopyala
                $tempDir = storage_path('app/temp');
                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }
                
                $tempFile = $tempDir . '/' . 'bilanco_' . uniqid() . '_' . basename($filePath);
                
                if (!copy($filePath, $tempFile)) {
                    throw new \Exception('Excel dosyası kopyalanamadı. Dosya izinlerini kontrol edin.');
                }
                
                $filePath = $tempFile;
            }
            
            try {
                // Excel dosyasını oku
                $data = Excel::toArray([], $filePath);
                
                // PhpSpreadsheet ile worksheet'i al (indent için - opsiyonel)
                try {
                    $spreadsheet = IOFactory::load($filePath);
                    $worksheet = $spreadsheet->getActiveSheet();
                } catch (\Exception $e) {
                    // Worksheet alınamazsa null kalır, sadece string indent kullanılır
                    Log::debug('PhpSpreadsheet worksheet load skipped', [
                        'error' => $e->getMessage(),
                    ]);
                }
            } finally {
                // Geçici dosyayı temizle
                if ($tempFile && file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
            
            if (empty($data) || empty($data[0])) {
                throw new \Exception('Excel dosyası boş veya okunamadı');
            }

            $sheet = $data[0];
            $pathStack = []; // Hiyerarşi stack'i
            $importedCount = 0;
            $totalRows = 0;

            // İlk satır başlık olabilir, atla
            $startRow = $this->detectHeaderRow($sheet);

            for ($i = $startRow; $i < count($sheet); $i++) {
                $row = $sheet[$i];

                // Hesap adı (A kolonu)
                $accountNameRaw = $row[0] ?? '';
                $accountName = trim($accountNameRaw);
                
                if (empty($accountName)) {
                    continue;
                }

                $totalRows++;

                // Tutar kolonları (B, C, D)
                $cariDonem = $this->parseDecimal($row[1] ?? null);
                $oncekiDonem = $this->parseDecimal($row[2] ?? null);
                $acilisBakiyeleri = $this->parseDecimal($row[3] ?? null);

                // Tüm tutarlar boşsa -> HEADER/GROUP
                $allEmpty = $cariDonem === null && $oncekiDonem === null && $acilisBakiyeleri === null;

                // Hiyerarşi seviyesini belirle (hem boşluk hem de Excel indent)
                // worksheet null ise sadece string indent kullanılır
                $level = $this->calculateLevel($accountNameRaw, $worksheet ?? null, $i + 1);

                if ($allEmpty) {
                    // GROUP satır - Path stack'i güncelle
                    $pathStack = $this->updatePathStack($pathStack, $accountName, $level);
                } else {
                    // LEAF satır - path oluştur ve kaydet
                    $path = $this->buildPath($pathStack, $accountName);
                    // LEAF satırının level'i calculateLevel'dan gelen değer
                    // (parent stack uzunluğu değil, hesap adının gerçek indent seviyesi)

                    BilancoRow::create([
                        'bilanco_import_id' => $bilancoImport->id,
                        'account_name' => $this->cleanAccountName($accountName),
                        'path' => $path,
                        'level' => $level,
                        'cari_donem' => $cariDonem,
                        'onceki_donem' => $oncekiDonem,
                        'acilis_bakiyeleri' => $acilisBakiyeleri,
                    ]);

                    $importedCount++;
                }
            }

            $bilancoImport->update([
                'status' => 'completed',
                'total_rows' => $totalRows,
                'imported_rows' => $importedCount,
                'completed_at' => now(),
            ]);

            Log::info('Bilanço import completed', [
                'bilanco_import_id' => $bilancoImport->id,
                'imported_rows' => $importedCount,
                'total_rows' => $totalRows,
            ]);

            return [
                'imported' => $importedCount,
                'total' => $totalRows,
            ];
        } catch (\Exception $e) {
            $bilancoImport->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Bilanço import failed', [
                'bilanco_import_id' => $bilancoImport->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Detect header row (skip if exists)
     */
    protected function detectHeaderRow(array $sheet): int
    {
        // İlk satırı kontrol et - eğer başlık satırı gibi görünüyorsa atla
        if (!empty($sheet[0])) {
            $firstRow = $sheet[0];
            // "Hesap Adı" veya benzer bir başlık varsa
            if (!empty($firstRow[0]) && (
                stripos($firstRow[0], 'hesap') !== false ||
                stripos($firstRow[0], 'account') !== false
            )) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * Calculate hierarchy level from account name (indentation)
     * Önce string'deki boşluklara bak, yoksa Excel cell indent'ine bak
     */
    protected function calculateLevel(string $accountNameRaw, $worksheet = null, int $rowIndex = 0): int
    {
        // 1. Önce string'deki baş boşlukları kontrol et
        preg_match('/^(\s*)/', $accountNameRaw, $matches);
        $spaces = strlen($matches[1] ?? '');
        
        if ($spaces > 0) {
            // Her 2 boşluk = 1 level
            return (int) floor($spaces / 2);
        }
        
        // 2. Eğer boşluk yoksa, Excel cell indent'ini kontrol et
        if ($worksheet && $rowIndex > 0) {
            try {
                $cell = $worksheet->getCell('A' . $rowIndex);
                $style = $worksheet->getStyle('A' . $rowIndex);
                $alignment = $style->getAlignment();
                $indent = $alignment->getIndent();
                
                if ($indent > 0) {
                    return (int) $indent;
                }
            } catch (\Exception $e) {
                // Hata durumunda 0 döndür
            }
        }
        
        // 3. Hiçbiri yoksa level 0
        return 0;
    }

    /**
     * Update path stack based on current level
     */
    protected function updatePathStack(array $pathStack, string $accountName, int $level): array
    {
        // Seviye kadar eleman bırak
        $pathStack = array_slice($pathStack, 0, $level);
        
        // Yeni eleman ekle
        $cleanName = $this->cleanAccountName($accountName);
        $pathStack[] = $cleanName;
        
        return $pathStack;
    }

    /**
     * Build path string from stack
     */
    protected function buildPath(array $pathStack, string $accountName): string
    {
        $path = $pathStack;
        $path[] = $this->cleanAccountName($accountName);
        return implode(' > ', $path);
    }

    /**
     * Clean account name (remove leading/trailing spaces)
     */
    protected function cleanAccountName(string $accountName): string
    {
        return trim($accountName);
    }

    /**
     * Parse decimal value from Excel cell
     */
    protected function parseDecimal($value): ?float
    {
        if ($value === null || $value === '' || $value === 0) {
            return null;
        }

        // String ise temizle ve parse et
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        $floatValue = (float) $value;
        
        return $floatValue == 0 ? null : $floatValue;
    }
}
