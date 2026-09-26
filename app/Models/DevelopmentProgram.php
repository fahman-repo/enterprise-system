<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\DevelopmentProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevelopmentProgram extends Model
{
    /** @use HasFactory<DevelopmentProgramFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * Event types accepted for a development program.
     *
     * @var list<string>
     */
    public const TYPES = ['training', 'seminar', 'workshop', 'ice_breaking', 'coaching', 'webinar', 'certification', 'other'];

    /**
     * Lifecycle statuses accepted for a development program.
     *
     * @var list<string>
     */
    public const STATUSES = ['planned', 'ongoing', 'completed', 'cancelled'];

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'organizer',
        'location',
        'start_date',
        'end_date',
        'capacity',
        'cost',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'capacity' => 'integer',
            'cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function auditLogName(): string
    {
        return 'development_program';
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(DevelopmentEnrollment::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'development_enrollments')
            ->withPivot(['status', 'score', 'completed_at', 'certificate_no'])
            ->withTimestamps();
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'training' => __('Training'),
            'seminar' => __('Seminar'),
            'workshop' => __('Workshop'),
            'ice_breaking' => __('Ice Breaking'),
            'coaching' => __('Coaching'),
            'webinar' => __('Webinar'),
            'certification' => __('Certification'),
            'other' => __('Other'),
            default => (string) $this->type,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'planned' => __('Planned'),
            'ongoing' => __('Ongoing'),
            'completed' => __('Completed'),
            'cancelled' => __('Cancelled'),
            default => (string) $this->status,
        };
    }

    /**
     * Build the next program code from the configured prefix and
     * padded sequence, e.g. DEV-00001. Call inside a transaction when
     * creating a program to guard against concurrent inserts.
     */
    public static function generateCode(): string
    {
        $prefix = (string) config('hr.development_program_code_prefix');
        $padding = (int) config('hr.development_program_code_padding');

        $latest = static::query()
            ->withTrashed()
            ->where('code', 'like', $prefix.'-%')
            ->lockForUpdate()
            ->pluck('code')
            ->map(fn (string $code): int => (int) substr($code, strlen($prefix) + 1))
            ->max() ?? 0;

        return $prefix.'-'.str_pad((string) ($latest + 1), $padding, '0', STR_PAD_LEFT);
    }
}
