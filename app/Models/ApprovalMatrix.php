<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\ApprovalMatrixFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalMatrix extends Model
{
    /** @use HasFactory<ApprovalMatrixFactory> */
    use Auditable, HasFactory;

    public const MODE_SEQUENTIAL = 'sequential';

    public const MODE_PARALLEL = 'parallel';

    public const MODES = [self::MODE_SEQUENTIAL, self::MODE_PARALLEL];

    protected $fillable = [
        'module_key',
        'is_active',
        'configuration_version',
        'mode',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'configuration_version' => 'integer',
        ];
    }

    public function auditLogName(): string
    {
        return 'approval-matrix';
    }

    public function makerRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'approval_matrix_maker_roles')->withTimestamps();
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ApprovalMatrixStage::class)->orderBy('stage_number');
    }
}
