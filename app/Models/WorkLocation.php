<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\WorkLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkLocation extends Model
{
    /** @use HasFactory<WorkLocationFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'city',
        'province',
        'postal_code',
        'phone',
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
        return 'work_location';
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
