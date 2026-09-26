<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\Brand;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrandsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of brands.
     */
    public function index(Request $request): View
    {
        $query = Brand::query()->withCount('products');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'name',
            'slug',
            'description',
        ]);

        $this->applyTableStatusFilter($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'name' => 'name',
            'slug' => 'slug',
            'products' => 'products_count',
            'is_active' => 'is_active',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $brands = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('brands.index', ['brands' => $brands, 'sort' => $sort, 'direction' => $direction]);
    }

    /**
     * Show the form for creating a brand.
     */
    public function create(): View
    {
        return view('brands.create');
    }

    /**
     * Store a newly created brand.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('brands')) {
            $workflow->submit($request->user(), 'brands', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()->route('brands.index')
                ->with('status', __('Brand change request submitted for approval.'));
        }

        Brand::create($data);

        return redirect()->route('brands.index')->with('status', __('Brand created.'));
    }

    /**
     * Show the form for editing a brand.
     */
    public function edit(Brand $brand): View
    {
        return view('brands.edit', ['brand' => $brand]);
    }

    /**
     * Update the specified brand.
     */
    public function update(Request $request, Brand $brand, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $brand);

        if ($workflow->isApprovalActive('brands')) {
            $workflow->submit($request->user(), 'brands', ApprovalRequest::ACTION_UPDATE, $brand->id, $data);

            return redirect()->route('brands.index')
                ->with('status', __('Brand change request submitted for approval.'));
        }

        $brand->update($data);

        return redirect()->route('brands.index')->with('status', __('Brand updated.'));
    }

    /**
     * Remove the brand; brands referenced by products cannot be deleted.
     */
    public function destroy(Brand $brand, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return back()->withErrors(['brand' => __('This brand is used by products and cannot be deleted.')]);
        }

        if ($workflow->isApprovalActive('brands')) {
            $workflow->submit(request()->user(), 'brands', ApprovalRequest::ACTION_DELETE, $brand->id, []);

            return redirect()->route('brands.index')
                ->with('status', __('Brand change request submitted for approval.'));
        }

        $brand->delete();

        return redirect()->route('brands.index')->with('status', __('Brand deleted.'));
    }

    /**
     * Validate and normalize the brand payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Brand $brand = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('brands', 'slug')->ignore($brand?->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
