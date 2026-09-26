<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\Auditable;
use App\Services\PermissionService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, Notifiable;

    /**
     * Whether the current save is changing the password; the audit
     * entry records the fact without ever storing the hash.
     */
    protected bool $passwordChanged = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function auditLogName(): string
    {
        return 'user';
    }

    public function auditExcept(): array
    {
        return ['password', 'remember_token', 'email_verified_at'];
    }

    public function auditProperties(): array
    {
        return ['password_changed' => $this->passwordChanged];
    }

    /**
     * The single role assigned to this user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    protected static function booted(): void
    {
        static::updating(function (User $user) {
            $user->passwordChanged = $user->isDirty('password');
        });
    }

    /**
     * Whether this user's role grants the given action on the given menu.
     */
    public function canAccess(string $menuSlug, string $action): bool
    {
        if (! $this->role_id) {
            return false;
        }

        return app(PermissionService::class)->can($this->role_id, $menuSlug, $action);
    }

    /**
     * Whether reassigning this user's own role would strip the ability
     * to manage roles or menus, locking the user out.
     */
    public function locksOut(?int $newRoleId): bool
    {
        $currentlyManages = $this->canAccess('roles', 'update') || $this->canAccess('menus', 'update');

        if (! $currentlyManages) {
            return false;
        }

        if (! $newRoleId) {
            return true;
        }

        $permissions = app(PermissionService::class)->permissionsForRole($newRoleId);

        return ! (($permissions['roles']['update'] ?? false) || ($permissions['menus']['update'] ?? false));
    }

    /**
     * Whether deleting this user would leave no one able to manage
     * the roles or menus modules.
     */
    public function isLastAdministrator(): bool
    {
        if (! $this->role_id) {
            return false;
        }

        $service = app(PermissionService::class);

        $adminRoleIds = Role::query()->pluck('id')->filter(fn (int $roleId) => $service->can($roleId, 'roles', 'view')
            || $service->can($roleId, 'roles', 'update')
            || $service->can($roleId, 'menus', 'view')
            || $service->can($roleId, 'menus', 'update'));

        if (! $adminRoleIds->contains($this->role_id)) {
            return false;
        }

        return self::query()
            ->where('id', '!=', $this->id)
            ->whereIn('role_id', $adminRoleIds)
            ->doesntExist();
    }
}
