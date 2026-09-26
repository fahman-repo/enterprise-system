<?php

namespace App\Services\Approvals;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BrandsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'brands';
    }

    public function label(): string
    {
        return __('Brands');
    }

    public function targetLabel(): string
    {
        return __('Brand');
    }

    protected function modelClass(): string
    {
        return Brand::class;
    }

    protected function fields(): array
    {
        return ['name', 'slug', 'description', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'name' => ['required'],
            'slug' => ['required', Rule::unique('brands', 'slug')->ignore($target?->getKey())],
        ];
    }

    protected function assertDeletable(Model $target): void
    {
        if ($target instanceof Brand && $target->products()->exists()) {
            throw ValidationException::withMessages(['brand' => __('This brand is used by products and cannot be deleted.')]);
        }
    }
}
