<?php

namespace App\Models;

use Database\Factories\ApprovalMatrixStageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ApprovalMatrixStage extends Model
{
    /** @use HasFactory<ApprovalMatrixStageFactory> */
    use HasFactory;

    protected $fillable = [
        'approval_matrix_id',
        'stage_number',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'stage_number' => 'integer',
        ];
    }

    public function matrix(): BelongsTo
    {
        return $this->belongsTo(ApprovalMatrix::class, 'approval_matrix_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'approval_matrix_stage_roles')->withTimestamps();
    }
}
