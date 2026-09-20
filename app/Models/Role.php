<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Services\PermissionService;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function auditLogName(): string
    {
        return 'role';
    }

    /**
     * Users assigned this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Menus with the permission flags granted to this role.
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'role_menu')
            ->withPivot(['can_view', 'can_create', 'can_update', 'can_delete'])
            ->withTimestamps();
    }

    /**
     * Save the role and sync its permission matrix within one audited
     * change, recording the permission diff when the matrix changed.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, bool>>|null  $permissions
     */
    public function withPermissions(array $attributes, ?array $permissions = null): static
    {
        $this->fill($attributes);
        $this->save();

        if ($permissions === null) {
            return $this;
        }

        $before = $this->permissionsSnapshot();
        $this->menus()->sync($permissions);
        app(PermissionService::class)->flush();
        $after = $this->permissionsSnapshot();

        if ($before !== $after) {
            $causer = Auth::user();

            activity('role')
                ->event('updated')
                ->performedOn($this)
                ->withProperties([
                    'permissions_before' => $before,
                    'permissions_after' => $after,
                    'permissions_changed' => true,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'causer' => $causer === null ? null : [
                        'id' => $causer->getKey(),
                        'name' => $causer->name,
                        'email' => $causer->email,
                    ],
                ])
                ->log('Role permissions updated');
        }

        return $this;
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(PermissionService::class)->flush());
        static::deleted(fn () => app(PermissionService::class)->flush());
    }

    /**
     * Current permission-matrix rows keyed by menu id.
     *
     * @return array<int, array<string, bool>>
     */
    public function permissionsSnapshot(): array
    {
        if (! $this->exists || ! Schema::hasTable('role_menu')) {
            return [];
        }

        return DB::table('role_menu')
            ->where('role_id', $this->getKey())
            ->orderBy('menu_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (int) $row->menu_id => [
                    'view' => (bool) $row->can_view,
                    'create' => (bool) $row->can_create,
                    'update' => (bool) $row->can_update,
                    'delete' => (bool) $row->can_delete,
                ],
            ])
            ->all();
    }
}
