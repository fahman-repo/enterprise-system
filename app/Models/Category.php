<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
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

    public function auditLogName(): string
    {
        return 'category';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Every category in depth-first order as [category, depth] pairs,
     * optionally excluding a category and its descendants.
     *
     * @return Collection<int, array{category: Category, depth: int}>
     */
    public static function treeOptions(?Category $except = null): Collection
    {
        $categories = static::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($except !== null) {
            $excluded = $except->descendantIds()->push($except->getKey());
            $categories = $categories->whereNotIn('id', $excluded)->values();
        }

        $childrenByParent = $categories->groupBy('parent_id');
        $tree = collect();

        $walk = function (?int $parentId, int $depth) use (&$walk, &$tree, $childrenByParent): void {
            foreach ($childrenByParent->get($parentId, collect()) as $category) {
                $tree->push(['category' => $category, 'depth' => $depth]);
                $walk($category->getKey(), $depth + 1);
            }
        };

        $walk(null, 0);

        return $tree;
    }

    /**
     * Ids of every category nested below this one (excluding itself).
     */
    public function descendantIds(): Collection
    {
        $childrenByParent = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
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
}
