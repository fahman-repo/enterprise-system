<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\ApprovalRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequest extends Model
{
    /** @use HasFactory<ApprovalRequestFactory> */
    use Auditable, HasFactory;

    public const STATUS_PENDING = 'Pending';

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_REJECTED = 'Rejected';

    public const STATUS_CANCELLED = 'Cancelled';

    public const ACTION_CREATE = 'create';

    public const ACTION_UPDATE = 'update';

    public const ACTION_DELETE = 'delete';

    public const MODE_SEQUENTIAL = 'sequential';

    public const MODE_PARALLEL = 'parallel';

    protected $fillable = [
        'module_key',
        'action',
        'target_id',
        'proposed_payload',
        'before_payload',
        'maker_user_id',
        'maker_snapshot',
        'status',
        'current_stage',
        'matrix_configuration_version',
        'approval_mode',
        'resolution_comment',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_id' => 'integer',
            'proposed_payload' => 'array',
            'before_payload' => 'array',
            'maker_snapshot' => 'array',
            'current_stage' => 'integer',
            'matrix_configuration_version' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function auditLogName(): string
    {
        return 'approval-request';
    }

    public function auditExcept(): array
    {
        return ['proposed_payload', 'before_payload', 'maker_snapshot'];
    }

    public function maker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maker_user_id');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ApprovalRequestStage::class)->orderBy('stage_number');
    }

    public function currentStage(): ?ApprovalRequestStage
    {
        $stages = $this->relationLoaded('stages') ? $this->stages : $this->stages()->get();

        return $stages->firstWhere('stage_number', $this->current_stage);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isParallel(): bool
    {
        return ($this->approval_mode ?? self::MODE_SEQUENTIAL) === self::MODE_PARALLEL;
    }

    public function isSequential(): bool
    {
        return ! $this->isParallel();
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED, self::STATUS_CANCELLED => 'destructive',
            default => 'muted',
        };
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_CREATE => __('Create'),
            self::ACTION_UPDATE => __('Update'),
            self::ACTION_DELETE => __('Delete'),
            default => __(ucfirst($this->action)),
        };
    }
}
