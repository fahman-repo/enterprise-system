<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\Unit;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of units of measure.
     */
    public function index(Request $request): View
    {
        $query = Unit::query()->withCount('products');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'name',
            'abbreviation',
        ]);

        $this->applyTableStatusFilter($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'name' => 'name',
            'abbreviation' => 'abbreviation',
            'products' => 'products_count',
            'is_active' => 'is_active',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $units = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('units.index', ['units' => $units, 'sort' => $sort, 'direction' => $direction]);
    }

    /**
     * Show the form for creating a unit.
     */
    public function create(): View
    {
        return view('units.create');
    }

    /**
     * Store a newly created unit.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('units')) {
            $workflow->submit($request->user(), 'units', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()->route('units.index')
                ->with('status', __('Unit change request submitted for approval.'));
        }

        Unit::create($data);

        return redirect()->route('units.index')->with('status', __('Unit created.'));
    }

    /**
     * Show the form for editing a unit.
     */
    public function edit(Unit $unit): View
    {
        return view('units.edit', ['unit' => $unit]);
    }

    /**
     * Update the specified unit.
     */
    public function update(Request $request, Unit $unit, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $unit);

        if ($workflow->isApprovalActive('units')) {
            $workflow->submit($request->user(), 'units', ApprovalRequest::ACTION_UPDATE, $unit->id, $data);

            return redirect()->route('units.index')
                ->with('status', __('Unit change request submitted for approval.'));
        }

        $unit->update($data);

        return redirect()->route('units.index')->with('status', __('Unit updated.'));
    }

    /**
     * Remove the unit; units referenced by products cannot be deleted.
     */
    public function destroy(Unit $unit, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($unit->products()->exists()) {
            return back()->withErrors(['unit' => __('This unit is used by products and cannot be deleted.')]);
        }

        if ($workflow->isApprovalActive('units')) {
            $workflow->submit(request()->user(), 'units', ApprovalRequest::ACTION_DELETE, $unit->id, []);

            return redirect()->route('units.index')
                ->with('status', __('Unit change request submitted for approval.'));
        }

        $unit->delete();

        return redirect()->route('units.index')->with('status', __('Unit deleted.'));
    }

    /**
     * Validate and normalize the unit payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Unit $unit = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:255', Rule::unique('units', 'abbreviation')->ignore($unit?->id)],
            'allows_decimal' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['allows_decimal'] = $request->boolean('allows_decimal');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
