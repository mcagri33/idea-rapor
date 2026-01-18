<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BilancoImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'file_path',
        'donem',
        'status',
        'error_message',
        'total_rows',
        'imported_rows',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(BilancoRow::class);
    }

    /**
     * İlişkili CK set (nullable - bir bilanço import için bir CK set)
     */
    public function ckSet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CkSet::class);
    }
}
