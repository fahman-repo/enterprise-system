<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
        'allows_decimal',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'allows_decimal' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function auditLogName(): string
    {
        return 'unit';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
