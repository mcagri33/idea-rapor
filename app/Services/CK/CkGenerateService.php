<?php

namespace App\Services\CK;

use App\Models\BilancoImport;
use App\Models\BilancoRow;
use App\Models\CkSet;
use App\Models\CkHead;
use App\Models\CkLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Çalışma Kağıdı Oluşturma Servisi
 * Bilanço verilerinden otomatik CK üretimi yapar
 */
class CkGenerateService
{
    /**
     * Bilanço import'undan CK set oluştur
     * 
     * @param int $bilancoImportId Bilanço import ID
     * @return CkSet Oluşturulan CK set
     * @throws \Exception
     */
    public function generateFromBilanco(int $bilancoImportId): CkSet
    {
        DB::beginTransaction();
        
        try {
            // Bilanço import'u al
            $bilancoImport = BilancoImport::with('company')->findOrFail($bilancoImportId);
            
            // Mevcut CK set var mı kontrol et
            $existingCkSet = CkSet::where('bilanco_import_id', $bilancoImportId)->first();
            if ($existingCkSet) {
                throw new \Exception('Bu bilanço import için zaten bir CK set mevcut.');
            }
            
            // CK Set oluştur
            $ckSet = CkSet::create([
                'company_id' => $bilancoImport->company_id,
                'donem_tarihi' => $bilancoImport->donem,
                'bilanco_import_id' => $bilancoImportId,
                'status' => 'draft',
            ]);
            
            // Leaf hesapları al (child'ı olmayan hesaplar)
            $leafRows = $this->getLeafRows($bilancoImportId);
            
            // Leaf hesapları path'e göre grupla
            $groupedRows = $this->groupRowsByPath($leafRows);
            
            $orderNo = 1;
            
            // Her leaf hesap için CK Head ve Line oluştur
            foreach ($groupedRows as $path => $rows) {
                // Path'ten son hesap adını al (başlık için)
                // Path ayırıcısı " > " veya ">" olabilir
                $pathParts = preg_split('/\s*>\s*/', trim($path));
                $baslik = end($pathParts) ?: $rows[0]->account_name;
                
                // CK Head oluştur
                $ckHead = CkHead::create([
                    'ck_set_id' => $ckSet->id,
                    'baslik' => $baslik,
                    'ck_type' => 'bilanco',
                    'bilanco_row_id' => $rows[0]->id, // İlk satırın ID'si
                    'full_path' => $path,
                    'order_no' => $orderNo++,
                ]);
                
                // Her satır için CK Line oluştur
                // NOT: Tutar 0 olsa bile veya açılış boş (null) olsa bile CK mutlaka oluşturulur
                foreach ($rows as $row) {
                    // Fark hesaplama: cari ve onceki null değilse fark hesapla
                    // 0 değerleri de dahil edilir (0 - 0 = 0)
                    $fark = null;
                    if ($row->cari_donem !== null && $row->onceki_donem !== null) {
                        $fark = $row->cari_donem - $row->onceki_donem;
                    }
                    
                    // Tüm değerler (0 veya null olsa bile) kaydedilir
                    // Null değerler null olarak kalır (boş açılış için)
                    CkLine::create([
                        'ck_head_id' => $ckHead->id,
                        'satir_adi' => $row->account_name,
                        'cari' => $row->cari_donem, // 0 veya null olabilir
                        'onceki' => $row->onceki_donem, // 0 veya null olabilir
                        'acilis' => $row->acilis_bakiyeleri, // 0 veya null olabilir (boş açılış)
                        'fark' => $fark, // 0 veya null olabilir
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info("CK set oluşturuldu", [
                'ck_set_id' => $ckSet->id,
                'bilanco_import_id' => $bilancoImportId,
                'head_count' => $ckSet->heads()->count(),
            ]);
            
            return $ckSet;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("CK oluşturma hatası", [
                'bilanco_import_id' => $bilancoImportId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Leaf hesapları al (child'ı olmayan hesaplar)
     * Bir hesabın leaf olması için: başka bir hesabın path'i bu hesabın path'ini içermemeli
     * 
     * @param int $bilancoImportId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getLeafRows(int $bilancoImportId)
    {
        $allRows = BilancoRow::where('bilanco_import_id', $bilancoImportId)
            ->orderBy('path')
            ->get();
        
        $leafRows = collect();
        
        foreach ($allRows as $row) {
            $isLeaf = true;
            $currentPath = $row->path;
            
            // Bu path'in başka bir path'in parent'ı olup olmadığını kontrol et
            foreach ($allRows as $otherRow) {
                if ($otherRow->id === $row->id) {
                    continue;
                }
                
                // Eğer başka bir path bu path'i içeriyorsa, bu leaf değildir
                // Path ayırıcısı " > " veya ">" olabilir
                $separator = str_contains($currentPath, ' > ') ? ' > ' : '>';
                if (str_starts_with($otherRow->path, $currentPath . $separator)) {
                    $isLeaf = false;
                    break;
                }
            }
            
            if ($isLeaf) {
                $leafRows->push($row);
            }
        }
        
        return $leafRows;
    }
    
    /**
     * Satırları path'e göre grupla
     * Aynı path'e sahip satırları bir araya getir
     * 
     * @param \Illuminate\Support\Collection $rows
     * @return array
     */
    private function groupRowsByPath($rows): array
    {
        $grouped = [];
        
        foreach ($rows as $row) {
            $path = $row->path;
            if (!isset($grouped[$path])) {
                $grouped[$path] = [];
            }
            $grouped[$path][] = $row;
        }
        
        return $grouped;
    }
}
