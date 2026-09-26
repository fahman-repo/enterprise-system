<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\DevelopmentEnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevelopmentEnrollment extends Model
{
    /** @use HasFactory<DevelopmentEnrollmentFactory> */
    use Auditable, HasFactory;

    /**
     * Outcome statuses accepted for an enrollment.
     *
     * @var list<string>
     */
    public const STATUSES = ['registered', 'attended', 'completed', 'failed', 'cancelled'];

    protected $fillable = [
        'development_program_id',
        'employee_id',
        'status',
        'score',
        'completed_at',
        'certificate_no',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'completed_at' => 'date',
        ];
    }

    public function auditLogName(): string
    {
        return 'development_enrollment';
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(DevelopmentProgram::class, 'development_program_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
