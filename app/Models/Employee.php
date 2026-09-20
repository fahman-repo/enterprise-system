<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * Gender values accepted for an employee.
     *
     * @var list<string>
     */
    public const GENDERS = ['male', 'female'];

    protected $fillable = [
        'user_id',
        'employee_number',
        'name',
        'gender',
        'birth_place',
        'birth_date',
        'religion_id',
        'marital_status_id',
        'education_level_id',
        'email',
        'phone',
        'identity_number',
        'npwp',
        'bpjs_kesehatan',
        'bpjs_ketenagakerjaan',
        'address',
        'city',
        'province',
        'postal_code',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'division_id',
        'department_id',
        'org_unit_id',
        'position_id',
        'grade_id',
        'work_location_id',
        'employment_status_id',
        'join_date',
        'end_date',
        'probation_end_date',
        'photo_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'birth_date' => 'date',
            'religion_id' => 'integer',
            'marital_status_id' => 'integer',
            'education_level_id' => 'integer',
            'division_id' => 'integer',
            'department_id' => 'integer',
            'org_unit_id' => 'integer',
            'position_id' => 'integer',
            'grade_id' => 'integer',
            'work_location_id' => 'integer',
            'employment_status_id' => 'integer',
            'join_date' => 'date',
            'end_date' => 'date',
            'probation_end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function auditLogName(): string
    {
        return 'employee';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function employmentStatus(): BelongsTo
    {
        return $this->belongsTo(EmploymentStatus::class);
    }

    public function genderLabel(): string
    {
        return match ($this->gender) {
            'male' => __('Male'),
            'female' => __('Female'),
            default => (string) $this->gender,
        };
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    /**
     * Build the next employee number from the configured prefix and
     * padded sequence, e.g. EMP-00001. Call inside a transaction when
     * creating an employee to guard against concurrent inserts.
     */
    public static function generateEmployeeNumber(): string
    {
        $prefix = (string) config('hr.employee_number_prefix');
        $padding = (int) config('hr.employee_number_padding');

        $latest = static::query()
            ->withTrashed()
            ->where('employee_number', 'like', $prefix.'-%')
            ->lockForUpdate()
            ->pluck('employee_number')
            ->map(fn (string $number): int => (int) substr($number, strlen($prefix) + 1))
            ->max() ?? 0;

        return $prefix.'-'.str_pad((string) ($latest + 1), $padding, '0', STR_PAD_LEFT);
    }
}
