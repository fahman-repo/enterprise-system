<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of products.
     */
    public function index(Request $request): View
    {
        $query = Product::query()
            ->select('products.*')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->with(['category', 'brand', 'unit']);

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'products.name',
            'products.sku',
            'products.barcode',
            'products.description',
            'categories.name',
            'brands.name',
        ]);

        $this->applyTableFilters($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'name' => 'products.name',
            'sku' => 'products.sku',
            'category' => 'categories.name',
            'brand' => 'brands.name',
            'selling_price' => 'products.selling_price',
            'stock_quantity' => 'products.stock_quantity',
            'is_active' => 'products.is_active',
            'created_at' => 'products.created_at',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $products = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('products.index', [
            'products' => $products,
            'sort' => $sort,
            'direction' => $direction,
            'categories' => Category::treeOptions(),
            'brands' => Brand::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a product.
     */
    public function create(): View
    {
        return view('products.create', $this->formOptions());
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($image = $request->file('image')) {
            $data['image_path'] = $image->store('products', 'public');
        }

        unset($data['image'], $data['remove_image']);

        Product::create($data);

        return redirect()->route('products.index')->with('status', __('Product created.'));
    }

    /**
     * Show the form for editing a product.
     */
    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);

        if ($request->boolean('remove_image') && $product->image_path) {
            Storage::disk('public')->delete($product->image_path);
            $data['image_path'] = null;
        }

        if ($image = $request->file('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $data['image_path'] = $image->store('products', 'public');
        }

        unset($data['image'], $data['remove_image']);

        $product->update($data);

        return redirect()->route('products.index')->with('status', __('Product updated.'));
    }

    /**
     * Soft delete the specified product; its image is kept for audit.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index')->with('status', __('Product deleted.'));
    }

    /**
     * Select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'categories' => Category::treeOptions(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
        ];
    }

    /**
     * Apply the category, brand, status and stock filters from the query string.
     */
    protected function applyTableFilters(Builder $query, Request $request): void
    {
        $categoryId = $request->query('category_id');

        if ($categoryId !== null && $category = Category::query()->find($categoryId)) {
            $query->whereIn('products.category_id', $category->descendantIds()->push($category->getKey())->all());
        }

        if ($brandId = $request->query('brand_id')) {
            $query->where('products.brand_id', $brandId);
        }

        if (in_array($request->query('status'), ['active', 'inactive'], true)) {
            $query->where('products.is_active', $request->query('status') === 'active');
        }

        if ($request->query('stock') === 'out') {
            $query->where('products.track_stock', true)
                ->where('products.stock_quantity', '<=', 0);
        } elseif ($request->query('stock') === 'low') {
            $query->where('products.track_stock', true)
                ->whereNotNull('products.reorder_level')
                ->where('products.stock_quantity', '>', 0)
                ->whereColumn('products.stock_quantity', '<=', 'products.reorder_level');
        }
    }

    /**
     * Validate and normalize the product payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product?->id)],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')],
            'unit_id' => ['nullable', Rule::exists('units', 'id')],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'between:0,100'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'track_stock' => ['nullable', 'boolean'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['track_stock'] = $request->boolean('track_stock');
        $data['is_active'] = $request->boolean('is_active');
        $data['stock_quantity'] ??= 0;

        if (! $data['track_stock']) {
            $data['stock_quantity'] = 0;
            $data['reorder_level'] = null;
        }

        return $data;
    }
}
