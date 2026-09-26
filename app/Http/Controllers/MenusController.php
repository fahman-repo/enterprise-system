<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\Menu;
use App\Services\ApprovalWorkflowService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MenusController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of menus.
     */
    public function index(Request $request): View
    {
        $query = Menu::query()
            ->select('menus.*')
            ->leftJoin('menus as parents', 'parents.id', '=', 'menus.parent_id')
            ->with('parent');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'menus.name',
            'menus.slug',
            'menus.route_name',
            'parents.name',
        ]);

        $this->applyTableStatusFilter($query, $request, 'menus.is_active');

        [$sort, $direction] = $this->applyTableSort($query, [
            'name' => 'menus.name',
            'slug' => 'menus.slug',
            'parent' => 'parents.name',
            'route_name' => 'menus.route_name',
            'sort_order' => 'menus.sort_order',
            'is_active' => 'menus.is_active',
        ], $request->query('sort'), $request->query('direction'), 'sort_order', 'asc', ['menus.name' => 'asc']);

        $menus = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('menus.index', ['menus' => $menus, 'sort' => $sort, 'direction' => $direction]);
    }

    /**
     * Show the form for creating a menu item.
     */
    public function create(): View
    {
        return view('menus.create', ['parentOptions' => Menu::parentOptions()]);
    }

    /**
     * Store a newly created menu item.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('menus')) {
            $workflow->submit($request->user(), 'menus', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()->route('menus.index')->with('status', __('Menu change request submitted for approval.'));
        }

        Menu::create($data);

        return redirect()->route('menus.index')->with('status', __('Menu created.'));
    }

    /**
     * Show the form for editing a menu item.
     */
    public function edit(Menu $menu): View
    {
        return view('menus.edit', [
            'menu' => $menu,
            'parentOptions' => Menu::parentOptions($menu),
        ]);
    }

    /**
     * Update the specified menu item.
     */
    public function update(Request $request, Menu $menu, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $menu);

        if ($workflow->isApprovalActive('menus')) {
            $workflow->submit($request->user(), 'menus', ApprovalRequest::ACTION_UPDATE, $menu->id, $data);

            return redirect()->route('menus.index')->with('status', __('Menu change request submitted for approval.'));
        }

        $menu->update($data);

        return redirect()->route('menus.index')->with('status', __('Menu updated.'));
    }

    /**
     * Remove the menu item; children are reparented to the top level
     * by the database and role assignments cascade.
     */
    public function destroy(Menu $menu, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($workflow->isApprovalActive('menus')) {
            $workflow->submit(request()->user(), 'menus', ApprovalRequest::ACTION_DELETE, $menu->id, []);

            return redirect()->route('menus.index')->with('status', __('Menu change request submitted for approval.'));
        }

        $menu->delete();

        return redirect()->route('menus.index')->with('status', __('Menu deleted. Child items moved to the top level.'));
    }

    /**
     * Validate and normalize the menu payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Menu $menu = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('menus', 'slug')->ignore($menu?->id)],
            'parent_id' => ['nullable', Rule::in(Menu::parentOptions($menu)->pluck('id')->all())],
            'icon' => ['nullable', 'string', Rule::in(Menu::ICONS)],
            'route_name' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (filled($value) && ! Route::has($value)) {
                        $fail(__('The route name :value does not exist.', ['value' => $value]));
                    }
                },
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] ??= 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
