<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\ReligionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Religion extends Model
{
    /** @use HasFactory<ReligionFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function auditLogName(): string
    {
        return 'religion';
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
