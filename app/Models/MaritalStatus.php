<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\MaritalStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaritalStatus extends Model
{
    /** @use HasFactory<MaritalStatusFactory> */
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
        return 'marital_status';
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
