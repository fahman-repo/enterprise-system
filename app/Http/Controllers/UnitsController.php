<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\Unit;
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
    public function store(Request $request): RedirectResponse
    {
        Unit::create($this->validated($request));

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
    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $unit->update($this->validated($request, $unit));

        return redirect()->route('units.index')->with('status', __('Unit updated.'));
    }

    /**
     * Remove the unit; units referenced by products cannot be deleted.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->products()->exists()) {
            return back()->withErrors(['unit' => __('This unit is used by products and cannot be deleted.')]);
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
