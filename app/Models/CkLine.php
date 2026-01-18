<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Çalışma Kağıdı Satır Modeli
 * CK içindeki tablo satırları
 */
class CkLine extends Model
{
    use HasFactory;

    protected $table = 'ck_lines';

    protected $fillable = [
        'ck_head_id',
        'satir_adi',
        'cari',
        'onceki',
        'acilis',
        'fark',
    ];

    protected function casts(): array
    {
        return [
            'cari' => 'decimal:2',
            'onceki' => 'decimal:2',
            'acilis' => 'decimal:2',
            'fark' => 'decimal:2',
        ];
    }

    /**
     * İlişkili CK başlığı
     */
    public function ckHead(): BelongsTo
    {
        return $this->belongsTo(CkHead::class);
    }
}
