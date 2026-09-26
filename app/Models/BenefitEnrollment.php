<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\Carbon;
use Database\Factories\BenefitEnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BenefitEnrollment extends Model
{
    /** @use HasFactory<BenefitEnrollmentFactory> */
    use Auditable, HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Lifecycle statuses accepted for a benefit enrollment.
     *
     * @var list<string>
     */
    public const STATUSES = ['active', 'suspended', 'expired', 'cancelled'];

    protected $fillable = [
        'benefit_id',
        'employee_id',
        'status',
        'effective_from',
        'effective_to',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'benefit_id' => 'integer',
            'employee_id' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function auditLogName(): string
    {
        return 'benefit_enrollment';
    }

    public function benefit(): BelongsTo
    {
        return $this->belongsTo(Benefit::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(BenefitClaim::class, 'benefit_enrollment_id');
    }

    /**
     * Whether the enrollment can back a new claim today. There is no
     * expiry cron in v1: a past effective_to counts as unusable here and
     * renders an Expired badge; the status column only changes manually
     * or through the approval flow.
     */
    public function isUsable(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if (! $this->benefit?->is_active) {
            return false;
        }

        if (! $this->employee || ! $this->employee->is_active) {
            return false;
        }

        $today = Carbon::today();
        $from = $this->effective_from instanceof Carbon ? $this->effective_from->startOfDay() : Carbon::parse($this->effective_from)->startOfDay();

        if ($today->lt($from)) {
            return false;
        }

        if ($this->effective_to !== null) {
            $to = $this->effective_to instanceof Carbon ? $this->effective_to->endOfDay() : Carbon::parse($this->effective_to)->endOfDay();

            if ($today->gt($to)) {
                return false;
            }
        }

        return true;
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_SUSPENDED => 'secondary',
            self::STATUS_EXPIRED => 'muted',
            self::STATUS_CANCELLED => 'destructive',
            default => 'muted',
        };
    }
}
