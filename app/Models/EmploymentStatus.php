<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\EmploymentStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmploymentStatus extends Model
{
    /** @use HasFactory<EmploymentStatusFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
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
        return 'employment_status';
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
