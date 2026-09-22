<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoriesController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of categories.
     */
    public function index(Request $request): View
    {
        $query = Category::query()
            ->select('categories.*')
            ->leftJoin('categories as parents', 'parents.id', '=', 'categories.parent_id')
            ->with('parent')
            ->withCount('products');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'categories.name',
            'categories.slug',
            'categories.description',
            'parents.name',
        ]);

        $this->applyTableStatusFilter($query, $request, 'categories.is_active');

        [$sort, $direction] = $this->applyTableSort($query, [
            'name' => 'categories.name',
            'slug' => 'categories.slug',
            'parent' => 'parents.name',
            'products' => 'products_count',
            'sort_order' => 'categories.sort_order',
            'is_active' => 'categories.is_active',
        ], $request->query('sort'), $request->query('direction'), 'sort_order', 'asc', ['categories.name' => 'asc']);

        $categories = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('categories.index', ['categories' => $categories, 'sort' => $sort, 'direction' => $direction]);
    }

    /**
     * Show the form for creating a category.
     */
    public function create(): View
    {
        return view('categories.create', ['parentOptions' => Category::treeOptions()]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return redirect()->route('categories.index')->with('status', __('Category created.'));
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(Category $category): View
    {
        return view('categories.edit', [
            'category' => $category,
            'parentOptions' => Category::treeOptions($category),
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('categories.index')->with('status', __('Category updated.'));
    }

    /**
     * Remove the category; its children move up to the deleted
     * category's parent level. Categories referenced by products
     * cannot be deleted.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => __('This category is used by products and cannot be deleted.')]);
        }

        Category::query()
            ->where('parent_id', $category->getKey())
            ->update(['parent_id' => $category->parent_id]);

        $category->delete();

        return redirect()->route('categories.index')->with('status', __('Category deleted. Child categories moved up one level.'));
    }

    /**
     * Validate and normalize the category payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'parent_id' => ['nullable', Rule::in(Category::treeOptions($category)->pluck('category.id')->all())],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] ??= 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
