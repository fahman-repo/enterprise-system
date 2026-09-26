<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\NotIn;

class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * Site categories, from the widest structure down to the smallest.
     *
     * @var list<string>
     */
    public const TYPES = ['company', 'building', 'branch', 'warehouse', 'workshop', 'factory'];

    /**
     * Categories allowed to parent each type, keeping the tree sensible.
     *
     * Branches and warehouses may sit at a factory because sales, service
     * and stock-keeping sites are commonly co-located with a plant.
     *
     * @var array<string, list<string>>
     */
    public const PARENT_TYPES = [
        'company' => [],
        'building' => ['company'],
        'branch' => ['company', 'building', 'factory'],
        'warehouse' => ['company', 'building', 'branch', 'factory'],
        'workshop' => ['company', 'building', 'branch', 'factory'],
        'factory' => ['company', 'building'],
    ];

    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'type',
        'description',
        'address',
        'city',
        'province',
        'postal_code',
        'phone',
        'email',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function auditLogName(): string
    {
        return 'site';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Every site category with its translated label, keyed by value.
     *
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            'company' => __('Company'),
            'building' => __('Building'),
            'branch' => __('Branch'),
            'warehouse' => __('Warehouse'),
            'workshop' => __('Workshop'),
            'factory' => __('Factory'),
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? (string) $this->type;
    }

    /**
     * Human readable trail from the root down to this site, excluding itself.
     */
    public function ancestorTrail(): Collection
    {
        $trail = collect();
        $parent = $this->parent;
        $visited = [$this->getKey() => true];

        while ($parent !== null && ! isset($visited[$parent->getKey()])) {
            $trail->prepend($parent);
            $visited[$parent->getKey()] = true;
            $parent = $parent->parent;
        }

        return $trail;
    }

    /**
     * Ids of every site nested below this one (excluding itself).
     */
    public function descendantIds(): Collection
    {
        $childrenByParent = static::query()
            ->orderBy('name')
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

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

    /**
     * Active sites eligible as parents (excluding self and descendants).
     */
    public static function parentOptions(?Site $site = null): Collection
    {
        $query = static::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($site) {
            $excluded = $site->descendantIds()->push($site->id)->all();

            $query->whereNotIn('id', $excluded);
        }

        return $query->get();
    }

    /**
     * Guard a submitted parent against the target's own subtree.
     */
    public static function parentRule(?Site $site): NotIn
    {
        $excluded = $site === null
            ? []
            : $site->descendantIds()->push($site->id)->all();

        return Rule::notIn($excluded);
    }

    /**
     * The categories accepted as a parent for the given type.
     *
     * @return list<string>
     */
    public static function parentTypesFor(?string $type): array
    {
        return $type === null ? [] : (self::PARENT_TYPES[$type] ?? []);
    }

    /**
     * The error message when a parent of the given type cannot hold a
     * child of the submitted type, or null when the pairing is valid.
     */
    public static function parentTypeErrorMessage(?string $type, mixed $parentId): ?string
    {
        if ($parentId === null || $parentId === '') {
            return null;
        }

        $parentType = static::query()->whereKey($parentId)->value('type');

        if ($parentType === null) {
            return null;
        }

        return in_array($parentType, self::parentTypesFor($type), true)
            ? null
            : __('A :child site cannot sit under a :parent site.', [
                'child' => (string) $type,
                'parent' => (string) $parentType,
            ]);
    }

    /**
     * Build the next site code from the configured prefix and
     * padded sequence, e.g. SIT-00001. Call inside a transaction when
     * creating a site to guard against concurrent inserts.
     */
    public static function generateCode(): string
    {
        $prefix = (string) config('sites.site_code_prefix');
        $padding = (int) config('sites.site_code_padding');

        $latest = static::query()
            ->withTrashed()
            ->where('code', 'like', $prefix.'-%')
            ->lockForUpdate()
            ->pluck('code')
            ->map(fn (string $code): int => (int) substr($code, strlen($prefix) + 1))
            ->max() ?? 0;

        return $prefix.'-'.str_pad((string) ($latest + 1), $padding, '0', STR_PAD_LEFT);
    }

    /**
     * Sites selectable on employee and org chart forms.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
