<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'name',
        'company',
        'email',
        'is_active',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function bilancoImports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BilancoImport::class);
    }
}
