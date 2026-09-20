<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\EducationLevelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationLevel extends Model
{
    /** @use HasFactory<EducationLevelFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'level',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function auditLogName(): string
    {
        return 'education_level';
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
