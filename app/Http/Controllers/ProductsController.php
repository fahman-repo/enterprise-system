<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ApprovalWorkflowService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        $summaryRow = Product::query()
            ->toBase()
            ->selectRaw('count(*) as total_products')
            ->selectRaw('count(case when products.is_active then 1 end) as active_products')
            ->selectSub(
                Product::query()->lowStock()->selectRaw('count(*)')->toBase(),
                'low_stock_products',
            )
            ->selectSub(
                Product::query()->outOfStock()->selectRaw('count(*)')->toBase(),
                'out_of_stock_products',
            )
            ->first();

        /**
         * @var array{total_products: int, active_products: int, low_stock_products: int, out_of_stock_products: int} $summary
         */
        $summary = [
            'total_products' => (int) ($summaryRow->total_products ?? 0),
            'active_products' => (int) ($summaryRow->active_products ?? 0),
            'low_stock_products' => (int) ($summaryRow->low_stock_products ?? 0),
            'out_of_stock_products' => (int) ($summaryRow->out_of_stock_products ?? 0),
        ];

        return view('products.index', [
            'products' => $products,
            'summary' => $summary,
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
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('products')) {
            unset($data['image'], $data['remove_image']);

            $storedPath = null;

            if ($image = $request->file('image')) {
                $data['image_path'] = $storedPath = $image->store('products', 'public');
            }

            try {
                $workflow->submit($request->user(), 'products', ApprovalRequest::ACTION_CREATE, null, $data);
            } catch (ValidationException $exception) {
                if ($storedPath !== null) {
                    Storage::disk('public')->delete($storedPath);
                }

                throw $exception;
            }

            return redirect()->route('products.index')
                ->with('status', __('Product change request submitted for approval.'));
        }

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
    public function update(Request $request, Product $product, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $product);

        if ($workflow->isApprovalActive('products')) {
            unset($data['image'], $data['remove_image']);

            $data['image_path'] = $product->image_path;

            if ($request->boolean('remove_image')) {
                $data['image_path'] = null;
            }

            $storedPath = null;

            if ($image = $request->file('image')) {
                $data['image_path'] = $storedPath = $image->store('products', 'public');
            }

            try {
                $workflow->submit($request->user(), 'products', ApprovalRequest::ACTION_UPDATE, $product->id, $data);
            } catch (ValidationException $exception) {
                if ($storedPath !== null) {
                    Storage::disk('public')->delete($storedPath);
                }

                throw $exception;
            }

            return redirect()->route('products.index')
                ->with('status', __('Product change request submitted for approval.'));
        }

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
    public function destroy(Product $product, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($workflow->isApprovalActive('products')) {
            $workflow->submit(request()->user(), 'products', ApprovalRequest::ACTION_DELETE, $product->id, []);

            return redirect()->route('products.index')
                ->with('status', __('Product change request submitted for approval.'));
        }

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
     *
     * The list UI allows a single active filter; only the first matching
     * parameter is applied so stale URLs cannot combine filters.
     */
    protected function applyTableFilters(Builder $query, Request $request): void
    {
        $categoryId = $request->query('category_id');

        if ($categoryId !== null && $categoryId !== '') {
            if ($category = Category::query()->find($categoryId)) {
                $query->whereIn('products.category_id', $category->descendantIds()->push($category->getKey())->all());
            }

            return;
        }

        if ($brandId = $request->query('brand_id')) {
            $query->where('products.brand_id', $brandId);

            return;
        }

        if (in_array($request->query('status'), ['active', 'inactive'], true)) {
            $query->where('products.is_active', $request->query('status') === 'active');

            return;
        }

        if ($request->query('stock') === 'out') {
            $query->outOfStock();

            return;
        }

        if ($request->query('stock') === 'low') {
            $query->lowStock();
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
