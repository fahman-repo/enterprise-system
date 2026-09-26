<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\BenefitClaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BenefitClaim extends Model
{
    /** @use HasFactory<BenefitClaimFactory> */
    use Auditable, HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_PAID = 'paid';

    /**
     * Claim statuses that can still be edited or deleted.
     *
     * @var list<string>
     */
    public const RECOVERABLE = ['pending'];

    /**
     * Claim statuses that consume the enrollment balance.
     *
     * @var list<string>
     */
    public const CONSUMING = ['approved', 'paid'];

    /**
     * All lifecycle statuses accepted for a benefit claim.
     *
     * @var list<string>
     */
    public const STATUSES = ['pending', 'approved', 'rejected', 'cancelled', 'paid'];

    protected $fillable = [
        'benefit_id',
        'benefit_enrollment_id',
        'employee_id',
        'claim_date',
        'amount',
        'description',
        'receipt_path',
        'status',
        'resolution_comment',
        'decided_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'benefit_id' => 'integer',
            'benefit_enrollment_id' => 'integer',
            'employee_id' => 'integer',
            'claim_date' => 'date',
            'amount' => 'decimal:2',
            'decided_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function auditLogName(): string
    {
        return 'benefit_claim';
    }

    public function benefit(): BelongsTo
    {
        return $this->belongsTo(Benefit::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(BenefitEnrollment::class, 'benefit_enrollment_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED, self::STATUS_PAID => 'success',
            self::STATUS_REJECTED, self::STATUS_CANCELLED => 'destructive',
            default => 'muted',
        };
    }
}
