<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'division_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'division_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function auditLogName(): string
    {
        return 'department';
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function orgUnits(): HasMany
    {
        return $this->hasMany(OrgUnit::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
