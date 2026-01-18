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
                
                // Başarılı oldu, worksheet'i al (indent için - opsiyonel)
                if (!empty($data)) {
                    try {
                        $spreadsheet = IOFactory::load($filePath);
                        $worksheet = $spreadsheet->getActiveSheet();
                    } catch (\Exception $e) {
                        // Worksheet alınamazsa null kalır, sadece string indent kullanılır
                        Log::debug('PhpSpreadsheet worksheet load skipped', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Sadece open_basedir hatası varsa alternatif yöntem dene
                if (str_contains($e->getMessage(), 'open_basedir') || 
                    str_contains($e->getMessage(), '/xl/worksheets/') ||
                    str_contains($e->getMessage(), 'file_exists()')) {
                    
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
                    // Diğer hatalar için direkt fırlat
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
        $zipOpened = false;
        
        try {
            Log::info('Excel ZIP okuma başlatıldı', ['file' => $filePath]);
            
            // ZIP'i aç
            $zip = new \ZipArchive();
            $result = $zip->open($filePath, \ZipArchive::RDONLY);
            
            if ($result !== true) {
                Log::error('Excel ZIP açılamadı', [
                    'result' => $result,
                    'file' => $filePath,
                    'error_codes' => [
                        \ZipArchive::ER_OK => 'ER_OK',
                        \ZipArchive::ER_MULTIDISK => 'ER_MULTIDISK',
                        \ZipArchive::ER_RENAME => 'ER_RENAME',
                        \ZipArchive::ER_CLOSE => 'ER_CLOSE',
                        \ZipArchive::ER_SEEK => 'ER_SEEK',
                        \ZipArchive::ER_READ => 'ER_READ',
                        \ZipArchive::ER_WRITE => 'ER_WRITE',
                        \ZipArchive::ER_CRC => 'ER_CRC',
                        \ZipArchive::ER_ZIPCLOSED => 'ER_ZIPCLOSED',
                        \ZipArchive::ER_NOENT => 'ER_NOENT',
                        \ZipArchive::ER_EXISTS => 'ER_EXISTS',
                        \ZipArchive::ER_OPEN => 'ER_OPEN',
                        \ZipArchive::ER_TMPOPEN => 'ER_TMPOPEN',
                        \ZipArchive::ER_ZLIB => 'ER_ZLIB',
                        \ZipArchive::ER_MEMORY => 'ER_MEMORY',
                        \ZipArchive::ER_CHANGED => 'ER_CHANGED',
                        \ZipArchive::ER_COMPNOTSUPP => 'ER_COMPNOTSUPP',
                        \ZipArchive::ER_EOF => 'ER_EOF',
                        \ZipArchive::ER_INVAL => 'ER_INVAL',
                        \ZipArchive::ER_NOZIP => 'ER_NOZIP',
                        \ZipArchive::ER_INTERNAL => 'ER_INTERNAL',
                        \ZipArchive::ER_INCONS => 'ER_INCONS',
                        \ZipArchive::ER_REMOVE => 'ER_REMOVE',
                        \ZipArchive::ER_DELETED => 'ER_DELETED',
                    ],
                ]);
                return null;
            }
            
            $zipOpened = true;
            Log::info('ZIP açıldı', ['file' => $filePath]);
            
            // Geçici dizine çıkar (izin verilen dizin)
            $extractDir = storage_path('app/temp/excel_extract_' . uniqid());
            if (!is_dir($extractDir)) {
                if (!@mkdir($extractDir, 0755, true)) {
                    Log::error('Geçici dizin oluşturulamadı', ['dir' => $extractDir]);
                    $zip->close();
                    $zipOpened = false;
                    return null;
                }
            }
            
            Log::info('Geçici dizin oluşturuldu', ['dir' => $extractDir]);
            
            // ZIP'i çıkar
            if (!$zip->extractTo($extractDir)) {
                Log::error('ZIP çıkarılamadı', ['dir' => $extractDir]);
                $zip->close();
                $zipOpened = false;
                return null;
            }
            
            Log::info('ZIP çıkarıldı', ['dir' => $extractDir]);
            
            // ZIP'i kapat
            $zip->close();
            $zipOpened = false;
            $zip = null;
            
            // Shared strings dosyasını oku
            $sharedStrings = [];
            $sharedStringsFile = $extractDir . '/xl/sharedStrings.xml';
            if (file_exists($sharedStringsFile)) {
                Log::info('Shared strings dosyası bulundu', ['file' => $sharedStringsFile]);
                $sharedStringsXml = @simplexml_load_file($sharedStringsFile);
                if ($sharedStringsXml) {
                    // Excel namespace'i
                    $sharedStringsXml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                    
                    // si (string item) elementlerini bul
                    $siElements = $sharedStringsXml->xpath('//x:si');
                    if (empty($siElements)) {
                        // Namespace olmadan dene
                        $siElements = $sharedStringsXml->xpath('//si');
                    }
                    
                    foreach ($siElements as $si) {
                        // t (text) elementini bul
                        $tElements = $si->xpath('.//x:t');
                        if (empty($tElements)) {
                            $tElements = $si->xpath('.//t');
                        }
                        
                        $text = '';
                        foreach ($tElements as $t) {
                            $text .= (string)$t;
                        }
                        $sharedStrings[] = $text;
                    }
                    Log::info('Shared strings okundu', ['count' => count($sharedStrings)]);
                } else {
                    Log::warning('Shared strings XML okunamadı', ['file' => $sharedStringsFile]);
                }
            } else {
                Log::info('Shared strings dosyası yok (normal olabilir)', ['file' => $sharedStringsFile]);
            }
            
            // İlk worksheet'i oku
            $worksheetFile = $extractDir . '/xl/worksheets/sheet1.xml';
            if (!file_exists($worksheetFile)) {
                // sheet1.xml yoksa, worksheets dizinindeki ilk dosyayı bul
                $worksheetsDir = $extractDir . '/xl/worksheets';
                if (is_dir($worksheetsDir)) {
                    $files = glob($worksheetsDir . '/*.xml');
                    Log::info('Worksheets dizinindeki dosyalar', ['files' => $files, 'dir' => $worksheetsDir]);
                    if (!empty($files)) {
                        $worksheetFile = $files[0];
                    }
                } else {
                    Log::error('Worksheets dizini bulunamadı', ['dir' => $worksheetsDir]);
                }
            }
            
            if (!file_exists($worksheetFile)) {
                Log::error('Worksheet XML dosyası bulunamadı', [
                    'extract_dir' => $extractDir,
                    'expected_file' => $extractDir . '/xl/worksheets/sheet1.xml',
                    'worksheets_dir_exists' => is_dir($extractDir . '/xl/worksheets'),
                ]);
                return null;
            }
            
            Log::info('Worksheet XML dosyası bulundu', ['file' => $worksheetFile]);
            
            // Worksheet XML'ini oku
            $worksheetXml = @simplexml_load_file($worksheetFile);
            if (!$worksheetXml) {
                $errors = libxml_get_errors();
                Log::error('Worksheet XML okunamadı', [
                    'file' => $worksheetFile,
                    'errors' => array_map(fn($e) => $e->message, $errors),
                ]);
                return null;
            }
            
            // Excel namespace'i
            $worksheetXml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            
            $data = [];
            
            // Satırları oku - xpath ile
            $rows = $worksheetXml->xpath('//x:row');
            if (empty($rows)) {
                // Namespace olmadan dene
                $rows = $worksheetXml->xpath('//row');
            }
            
            if (empty($rows)) {
                // sheetData içinde dene
                $sheetData = $worksheetXml->xpath('//x:sheetData/x:row');
                if (empty($sheetData)) {
                    $sheetData = $worksheetXml->xpath('//sheetData/row');
                }
                $rows = $sheetData;
            }
            
            if (empty($rows)) {
                Log::error('Worksheet XML\'de satır bulunamadı', [
                    'file' => $worksheetFile,
                    'xml_structure' => substr($worksheetXml->asXML(), 0, 500), // İlk 500 karakter
                ]);
                return null;
            }
            
            Log::info('Satırlar bulundu', ['satir_sayisi' => count($rows)]);
            
            // Namespace'i bir kez kaydet
            $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            
            foreach ($rows as $row) {
                $rowData = [];
                $rowNum = (int)($row['r'] ?? 0);
                
                // Hücreleri oku - direkt foreach ile (SimpleXML otomatik handle eder)
                $cells = [];
                
                // Önce namespace ile dene
                $rowChildren = $row->children($ns);
                if ($rowChildren && isset($rowChildren->c)) {
                    foreach ($rowChildren->c as $cell) {
                        $cells[] = $cell;
                    }
                }
                
                // Eğer boşsa, namespace olmadan dene
                if (empty($cells)) {
                    $rowChildrenNoNs = $row->children();
                    if ($rowChildrenNoNs && isset($rowChildrenNoNs->c)) {
                        foreach ($rowChildrenNoNs->c as $cell) {
                            $cells[] = $cell;
                        }
                    }
                }
                
                // Eğer hala boşsa, direkt row->c ile dene
                if (empty($cells) && isset($row->c)) {
                    foreach ($row->c as $cell) {
                        $cells[] = $cell;
                    }
                }
                
                Log::debug('Satır hücreleri', [
                    'row_num' => $rowNum,
                    'cell_count' => count($cells),
                ]);
                
                foreach ($cells as $cell) {
                    $cellRef = (string)($cell['r'] ?? '');
                    $cellType = (string)($cell['t'] ?? '');
                    $cellValue = null;
                    
                    // Hücre değerini al (v elementi) - direkt erişim
                    if (isset($cell->v)) {
                        $cellValue = (string)$cell->v;
                    } else {
                        // Namespace ile dene
                        $cellChildren = $cell->children($ns);
                        if ($cellChildren && isset($cellChildren->v)) {
                            $cellValue = (string)$cellChildren->v;
                        } else {
                            // Namespace olmadan dene
                            $cellChildrenNoNs = $cell->children();
                            if ($cellChildrenNoNs && isset($cellChildrenNoNs->v)) {
                                $cellValue = (string)$cellChildrenNoNs->v;
                            }
                        }
                    }
                    
                    // Shared string ise
                    if ($cellValue !== null && $cellType === 's' && isset($sharedStrings[(int)$cellValue])) {
                        $cellValue = $sharedStrings[(int)$cellValue];
                    }
                    
                    // Kolon index'ini hesapla (A=0, B=1, ...)
                    if (!empty($cellRef)) {
                        preg_match('/^([A-Z]+)(\d+)$/', $cellRef, $matches);
                        if (!empty($matches[1])) {
                            $col = $this->columnToIndex($matches[1]);
                            $rowData[$col] = $cellValue;
                        }
                    }
                }
                
                // Eksik kolonları null ile doldur (en az 4 kolon: A, B, C, D)
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
            
            if (empty($data)) {
                Log::warning('Excel ZIP okundu ama satır verisi boş', [
                    'file' => $filePath,
                    'extract_dir' => $extractDir,
                ]);
                return null;
            }
            
            Log::info('Excel ZIP okuma başarılı', [
                'satir_sayisi' => count($data),
                'file' => $filePath,
            ]);
            
            return [$data];
            
        } catch (\Exception $e) {
            Log::error('Excel ZIP okuma hatası', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        } finally {
            // ZIP'i kapat (eğer açıksa)
            if ($zipOpened && $zip instanceof \ZipArchive) {
                try {
                    $zip->close();
                } catch (\Exception $e) {
                    // ZIP zaten kapatılmış olabilir, hata yok say
                }
            }
            
            // Geçici dizini temizle
            if ($extractDir && is_dir($extractDir)) {
                try {
                    $this->deleteDirectory($extractDir);
                } catch (\Exception $e) {
                    Log::warning('Geçici dizin temizlenemedi', [
                        'dir' => $extractDir,
                        'error' => $e->getMessage(),
                    ]);
                }
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
