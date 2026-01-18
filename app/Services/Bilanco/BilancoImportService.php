<?php

namespace App\Services\Bilanco;

use App\Models\BilancoImport;
use App\Models\BilancoRow;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Settings;

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
            
            // PhpSpreadsheet'in geçici dizin ayarını yap
            // open_basedir kısıtlaması nedeniyle izin verilen bir dizin kullan
            $tempDir = storage_path('app/temp/phpspreadsheet');
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0755, true);
            }
            
            // PHP'nin geçici dizin environment variable'ını ayarla
            // PhpSpreadsheet ZIP arşivini açarken sys_get_temp_dir() kullanır
            // Bu fonksiyon TMPDIR environment variable'ını kontrol eder
            if (is_dir($tempDir) && is_writable($tempDir)) {
                // Environment variable'ı ayarla
                putenv('TMPDIR=' . $tempDir);
                putenv('TMP=' . $tempDir);
                putenv('TEMP=' . $tempDir);
                
                // PhpSpreadsheet ayarları
                Settings::setLibXmlLoaderOptions(LIBXML_DTDLOAD | LIBXML_DTDATTR);
            }
            
            $data = null;
            $worksheet = null;
            
            try {
                // Excel dosyasını oku
                // PhpSpreadsheet artık izin verilen dizini kullanacak
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
            } catch (\Exception $e) {
                // open_basedir hatası devam ederse, alternatif yöntem dene
                if (str_contains($e->getMessage(), 'open_basedir') || 
                    str_contains($e->getMessage(), '/xl/worksheets/')) {
                    
                    Log::warning('Excel read failed due to open_basedir, trying alternative method', [
                        'error' => $e->getMessage(),
                        'file_path' => $filePath,
                    ]);
                    
                    // Alternatif: Excel dosyasını ZIP olarak aç ve XML'leri oku
                    $data = $this->readExcelAsZip($filePath);
                    
                    if (empty($data)) {
                        throw new \Exception('Excel dosyası okunamadı. Alternatif yöntem de başarısız oldu.');
                    }
                } else {
                    throw $e;
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

    /**
     * Excel dosyasını ZIP olarak aç ve XML'leri oku (open_basedir hatası için alternatif)
     * 
     * @param string $filePath
     * @return array|null
     */
    protected function readExcelAsZip(string $filePath): ?array
    {
        // Excel dosyası aslında bir ZIP arşivi
        // İçinde xl/worksheets/sheet1.xml gibi XML dosyaları var
        
        $zip = null;
        $extractDir = null;
        
        try {
            // ZIP'i aç
            $zip = new \ZipArchive();
            $result = $zip->open($filePath);
            
            if ($result !== true) {
                Log::error('Excel ZIP açılamadı', ['result' => $result]);
                return null;
            }
            
            // Geçici dizine çıkar (izin verilen dizin)
            $extractDir = storage_path('app/temp/excel_extract_' . uniqid());
            if (!is_dir($extractDir)) {
                @mkdir($extractDir, 0755, true);
            }
            
            // ZIP'i çıkar
            $zip->extractTo($extractDir);
            $zip->close();
            
            // Shared strings dosyasını oku
            $sharedStrings = [];
            $sharedStringsFile = $extractDir . '/xl/sharedStrings.xml';
            if (file_exists($sharedStringsFile)) {
                $sharedStringsXml = simplexml_load_file($sharedStringsFile);
                if ($sharedStringsXml) {
                    $namespaces = $sharedStringsXml->getNamespaces(true);
                    $ns = $namespaces[''] ?? '';
                    
                    foreach ($sharedStringsXml->children($ns)->si as $si) {
                        $t = (string)($si->t ?? '');
                        $sharedStrings[] = $t;
                    }
                }
            }
            
            // İlk worksheet'i oku
            $worksheetFile = $extractDir . '/xl/worksheets/sheet1.xml';
            if (!file_exists($worksheetFile)) {
                // sheet1.xml yoksa, worksheets dizinindeki ilk dosyayı bul
                $worksheetsDir = $extractDir . '/xl/worksheets';
                if (is_dir($worksheetsDir)) {
                    $files = glob($worksheetsDir . '/*.xml');
                    if (!empty($files)) {
                        $worksheetFile = $files[0];
                    }
                }
            }
            
            if (!file_exists($worksheetFile)) {
                Log::error('Worksheet XML dosyası bulunamadı');
                return null;
            }
            
            // Worksheet XML'ini oku
            $worksheetXml = simplexml_load_file($worksheetFile);
            if (!$worksheetXml) {
                Log::error('Worksheet XML okunamadı');
                return null;
            }
            
            $namespaces = $worksheetXml->getNamespaces(true);
            $ns = $namespaces[''] ?? '';
            
            $data = [];
            
            // Satırları oku
            foreach ($worksheetXml->children($ns)->sheetData->row as $row) {
                $rowData = [];
                $rowNum = (int)$row['r'];
                
                // Hücreleri oku
                foreach ($row->c as $cell) {
                    $cellRef = (string)$cell['r'];
                    $cellType = (string)$cell['t'];
                    $cellValue = null;
                    
                    // Hücre değerini al
                    if (isset($cell->v)) {
                        $cellValue = (string)$cell->v;
                        
                        // Shared string ise
                        if ($cellType === 's' && isset($sharedStrings[(int)$cellValue])) {
                            $cellValue = $sharedStrings[(int)$cellValue];
                        }
                    }
                    
                    // Kolon index'ini hesapla (A=0, B=1, ...)
                    preg_match('/^([A-Z]+)(\d+)$/', $cellRef, $matches);
                    if (!empty($matches[1])) {
                        $col = $this->columnToIndex($matches[1]);
                        $rowData[$col] = $cellValue;
                    }
                }
                
                // Eksik kolonları null ile doldur
                $maxCol = !empty($rowData) ? max(array_keys($rowData)) : 3;
                for ($i = 0; $i <= $maxCol; $i++) {
                    if (!isset($rowData[$i])) {
                        $rowData[$i] = null;
                    }
                }
                
                // Sıralı array'e çevir
                ksort($rowData);
                $data[] = array_values($rowData);
            }
            
            return [$data];
            
        } catch (\Exception $e) {
            Log::error('Excel ZIP okuma hatası', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
            ]);
            return null;
        } finally {
            // Temizlik
            if ($zip) {
                @$zip->close();
            }
            if ($extractDir && is_dir($extractDir)) {
                // Dizini recursive olarak sil
                $this->deleteDirectory($extractDir);
            }
        }
    }

    /**
     * Excel kolon harfini index'e çevir (A=0, B=1, ..., Z=25, AA=26, ...)
     */
    protected function columnToIndex(string $column): int
    {
        $index = 0;
        $column = strtoupper($column);
        $length = strlen($column);
        
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($column[$i]) - ord('A') + 1);
        }
        
        return $index - 1;
    }

    /**
     * Dizini recursive olarak sil
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        
        return @rmdir($dir);
    }
}
