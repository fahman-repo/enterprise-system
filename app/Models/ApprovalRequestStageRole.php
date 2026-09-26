<?php

namespace App\Models;

use Database\Factories\ApprovalRequestStageRoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequestStageRole extends Model
{
    /** @use HasFactory<ApprovalRequestStageRoleFactory> */
    use HasFactory;

    protected $fillable = [
        'approval_request_stage_id',
        'role_id',
        'role_name',
        'role_slug',
    ];

    protected function casts(): array
    {
        return [
            'role_id' => 'integer',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequestStage::class, 'approval_request_stage_id');
    }
}
