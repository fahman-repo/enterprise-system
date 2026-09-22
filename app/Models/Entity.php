<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\EntityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Model
{
    /** @use HasFactory<EntityFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * Type values accepted for an entity.
     *
     * @var list<string>
     */
    public const TYPES = ['company', 'personal'];

    /**
     * Role values accepted for an entity.
     *
     * @var list<string>
     */
    public const ROLES = ['vendor', 'customer', 'both'];

    protected $fillable = [
        'code',
        'name',
        'type',
        'role',
        'npwp',
        'identity_number',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'postal_code',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function auditLogName(): string
    {
        return 'entity';
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'company' => __('Company'),
            'personal' => __('Personal'),
            default => (string) $this->type,
        };
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'vendor' => __('Vendor'),
            'customer' => __('Customer'),
            'both' => __('Vendor & customer'),
            default => (string) $this->role,
        };
    }

    /**
     * Build the next entity code from the configured prefix and
     * padded sequence, e.g. ENT-00001. Call inside a transaction when
     * creating an entity to guard against concurrent inserts.
     */
    public static function generateCode(): string
    {
        $prefix = (string) config('entities.entity_code_prefix');
        $padding = (int) config('entities.entity_code_padding');

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
