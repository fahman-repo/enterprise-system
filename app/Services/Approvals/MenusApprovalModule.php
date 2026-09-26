<?php

namespace App\Services\Approvals;

use App\Models\Menu;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class MenusApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'menus';
    }

    public function label(): string
    {
        return __('Menus');
    }

    public function targetLabel(): string
    {
        return __('Menu');
    }

    protected function modelClass(): string
    {
        return Menu::class;
    }

    protected function fields(): array
    {
        return ['name', 'slug', 'parent_id', 'icon', 'route_name', 'sort_order', 'is_active'];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'name' => ['required'],
            'slug' => ['required', Rule::unique('menus', 'slug')->ignore($target?->getKey())],
            'parent_id' => ['nullable', Rule::in(Menu::parentOptions($target)->pluck('id')->all())],
            'icon' => ['nullable', Rule::in(Menu::ICONS)],
            'route_name' => [
                'nullable',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (filled($value) && ! Route::has($value)) {
                        $fail(__('The route name :value does not exist.', ['value' => $value]));
                    }
                },
            ],
        ];
    }
}
