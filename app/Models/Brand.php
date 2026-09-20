<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function auditLogName(): string
    {
        return 'brand';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
