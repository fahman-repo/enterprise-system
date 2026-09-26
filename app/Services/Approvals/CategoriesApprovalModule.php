<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoriesApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'categories';
    }

    public function label(): string
    {
        return __('Categories');
    }

    public function targetLabel(): string
    {
        return __('Category');
    }

    protected function modelClass(): string
    {
        return Category::class;
    }

    protected function fields(): array
    {
        return ['parent_id', 'name', 'slug', 'description', 'sort_order', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'name' => ['required'],
            'slug' => ['required', Rule::unique('categories', 'slug')->ignore($target?->getKey())],
            'parent_id' => ['nullable', Rule::in(Category::treeOptions($target)->pluck('category.id')->all())],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof Category && $target->products()->exists()) {
            throw ValidationException::withMessages(['category' => __('This category is used by products and cannot be deleted.')]);
        }
    }

    protected function applyDelete(ApprovalRequest $request, Model $target): void
    {
        Category::query()
            ->where('parent_id', $target->getKey())
            ->update(['parent_id' => $target->parent_id]);

        $target->delete();
    }
}
