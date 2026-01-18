<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BilancoRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'bilanco_import_id',
        'account_name',
        'path',
        'level',
        'cari_donem',
        'onceki_donem',
        'acilis_bakiyeleri',
    ];

    protected function casts(): array
    {
        return [
            'cari_donem' => 'decimal:2',
            'onceki_donem' => 'decimal:2',
            'acilis_bakiyeleri' => 'decimal:2',
        ];
    }

    public function bilancoImport(): BelongsTo
    {
        return $this->belongsTo(BilancoImport::class);
    }
}
