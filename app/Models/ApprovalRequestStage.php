<?php

namespace App\Models;

use Database\Factories\ApprovalRequestStageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequestStage extends Model
{
    /** @use HasFactory<ApprovalRequestStageFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'Pending';

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_REJECTED = 'Rejected';

    protected $fillable = [
        'approval_request_id',
        'stage_number',
        'name',
        'status',
        'decided_by_user_id',
        'decided_by_snapshot',
        'comment',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'stage_number' => 'integer',
            'decided_by_snapshot' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(ApprovalRequestStageRole::class, 'approval_request_stage_id');
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'destructive',
            default => 'muted',
        };
    }
}
