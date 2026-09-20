<?php

namespace App\Models;

use App\Services\PermissionService;
use Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Menu extends Model
{
    /** @use HasFactory<MenuFactory> */
    use HasFactory;

    /**
     * Action flags available per menu item.
     *
     * @var list<string>
     */
    public const ACTIONS = ['view', 'create', 'update', 'delete'];

    /**
     * Icon components available under <x-icon.*>.
     *
     * @var list<string>
     */
    public const ICONS = [
        'layout-dashboard',
        'users',
        'settings',
        'menu',
        'sun',
        'moon',
        'log-out',
        'alert-circle',
        'chevron-down',
    ];

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'icon',
        'route_name',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menu')
            ->withPivot(['can_view', 'can_create', 'can_update', 'can_delete'])
            ->withTimestamps();
    }

    /**
     * Ids of every menu nested below this one (excluding itself).
     */
    public function descendantIds(): Collection
    {
        $childrenByParent = Menu::query()->orderBy('sort_order')->orderBy('name')->get()->groupBy('parent_id');

        $ids = collect();
        $queue = collect([$this->id]);

        while ($queue->isNotEmpty()) {
            $current = $queue->shift();

            foreach ($childrenByParent->get($current, collect()) as $child) {
                $ids->push($child->id);
                $queue->push($child->id);
            }
        }

        return $ids;
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(PermissionService::class)->flush());
        static::deleted(fn () => app(PermissionService::class)->flush());
    }
}
