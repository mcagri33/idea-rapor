<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Çalışma Kağıdı Başlık Modeli
 * Her bir çalışma kağıdı (sayfa)
 */
class CkHead extends Model
{
    use HasFactory;

    protected $table = 'ck_heads';

    protected $fillable = [
        'ck_set_id',
        'baslik',
        'ck_type',
        'bilanco_row_id',
        'full_path',
        'order_no',
    ];

    /**
     * İlişkili CK set
     */
    public function ckSet(): BelongsTo
    {
        return $this->belongsTo(CkSet::class);
    }

    /**
     * İlişkili bilanço satırı (nullable - serbest CK'lerde NULL)
     */
    public function bilancoRow(): BelongsTo
    {
        return $this->belongsTo(BilancoRow::class);
    }

    /**
     * Bu CK'ye ait satırlar
     */
    public function lines(): HasMany
    {
        return $this->hasMany(CkLine::class);
    }

    /**
     * Bu CK'ye ait içerikler (metinsel alanlar)
     */
    public function contents(): HasMany
    {
        return $this->hasMany(CkContent::class);
    }

    /**
     * Belirli bir section için içerik getir
     */
    public function getContentBySection(string $section): ?CkContent
    {
        return $this->contents()->where('section', $section)->first();
    }
}
