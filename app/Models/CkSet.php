<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Çalışma Kağıdı Set Modeli
 * Bir firma + dönem için CK paketi
 */
class CkSet extends Model
{
    use HasFactory;

    protected $table = 'ck_sets';

    protected $fillable = [
        'company_id',
        'donem_tarihi',
        'bilanco_import_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'donem_tarihi' => 'date',
        ];
    }

    /**
     * İlişkili firma
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * İlişkili bilanço import (nullable)
     */
    public function bilancoImport(): BelongsTo
    {
        return $this->belongsTo(BilancoImport::class);
    }

    /**
     * Bu set'e ait CK başlıkları
     */
    public function heads(): HasMany
    {
        return $this->hasMany(CkHead::class)->orderBy('order_no');
    }
}
