<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\GradeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    /** @use HasFactory<GradeFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'level',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function auditLogName(): string
    {
        return 'grade';
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
