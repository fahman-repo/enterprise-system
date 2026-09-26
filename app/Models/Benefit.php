<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\BenefitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Benefit extends Model
{
    /** @use HasFactory<BenefitFactory> */
    use Auditable, HasFactory;

    /**
     * Benefit package types accepted for a benefit.
     *
     * @var list<string>
     */
    public const TYPES = ['medical', 'insurance', 'allowance', 'meal', 'transport', 'education', 'wellness', 'other'];

    /**
     * Limit reset periods accepted for a benefit.
     *
     * @var list<string>
     */
    public const PERIODS = ['monthly', 'quarterly', 'yearly', 'once'];

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'limit_amount',
        'period',
        'min_tenure_months',
        'requires_receipt',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'limit_amount' => 'decimal:2',
            'min_tenure_months' => 'integer',
            'requires_receipt' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function auditLogName(): string
    {
        return 'benefit';
    }

    /**
     * Grades eligible for this benefit; empty set means all grades eligible.
     */
    public function eligibleGrades(): BelongsToMany
    {
        return $this->belongsToMany(Grade::class, 'benefit_eligible_grade')->withTimestamps();
    }

    /**
     * Employment statuses eligible for this benefit; empty set means all statuses eligible.
     */
    public function eligibleEmploymentStatuses(): BelongsToMany
    {
        return $this->belongsToMany(EmploymentStatus::class, 'benefit_eligible_employment_status')->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(BenefitEnrollment::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(BenefitClaim::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'medical' => __('Medical'),
            'insurance' => __('Insurance'),
            'allowance' => __('Allowance'),
            'meal' => __('Meal'),
            'transport' => __('Transport'),
            'education' => __('Education'),
            'wellness' => __('Wellness'),
            'other' => __('Other'),
            default => (string) $this->type,
        };
    }

    public function periodLabel(): string
    {
        return match ($this->period) {
            'monthly' => __('Monthly'),
            'quarterly' => __('Quarterly'),
            'yearly' => __('Yearly'),
            'once' => __('Once'),
            default => (string) $this->period,
        };
    }
}
