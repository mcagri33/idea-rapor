<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Çalışma Kağıdı İçerik Modeli
 * CK içindeki metinsel alanlar (denetçi müdahalesi için)
 */
class CkContent extends Model
{
    use HasFactory;

    protected $table = 'ck_contents';

    protected $fillable = [
        'ck_head_id',
        'section',
        'content',
    ];

    /**
     * İlişkili CK başlığı
     */
    public function ckHead(): BelongsTo
    {
        return $this->belongsTo(CkHead::class);
    }
}
