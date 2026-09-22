<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\Religion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReligionsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of religions.
     */
    public function index(Request $request): View
    {
        $query = Religion::query()->withCount('employees');

        $this->applyTableSearch($query, $this->tableSearch($request), ['name']);

        $this->applyTableStatusFilter($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'name' => 'name',
            'employees' => 'employees_count',
            'sort_order' => 'sort_order',
            'is_active' => 'is_active',
        ], $request->query('sort'), $request->query('direction'), 'sort_order');

        $religions = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('religions.index', ['religions' => $religions, 'sort' => $sort, 'direction' => $direction]);
    }

    /**
     * Show the form for creating a religion.
     */
    public function create(): View
    {
        return view('religions.create');
    }

    /**
     * Store a newly created religion.
     */
    public function store(Request $request): RedirectResponse
    {
        Religion::create($this->validated($request));

        return redirect()->route('religions.index')->with('status', __('Religion created.'));
    }

    /**
     * Show the form for editing a religion.
     */
    public function edit(Religion $religion): View
    {
        return view('religions.edit', ['religion' => $religion]);
    }

    /**
     * Update the specified religion.
     */
    public function update(Request $request, Religion $religion): RedirectResponse
    {
        $religion->update($this->validated($request, $religion));

        return redirect()->route('religions.index')->with('status', __('Religion updated.'));
    }

    /**
     * Remove the religion; religions assigned to employees cannot be deleted.
     */
    public function destroy(Religion $religion): RedirectResponse
    {
        if ($religion->employees()->exists()) {
            return back()->withErrors(['religion' => __('This religion is assigned to employees and cannot be deleted.')]);
        }

        $religion->delete();

        return redirect()->route('religions.index')->with('status', __('Religion deleted.'));
    }

    /**
     * Validate and normalize the religion payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Religion $religion = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('religions', 'name')->ignore($religion?->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] ??= 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
