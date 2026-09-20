<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\EducationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EducationLevelsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of education levels.
     */
    public function index(Request $request): View
    {
        $query = EducationLevel::query()->withCount('employees');

        $this->applyTableSearch($query, $this->tableSearch($request), ['name']);

        [$sort, $direction] = $this->applyTableSort($query, [
            'name' => 'name',
            'level' => 'level',
            'sort_order' => 'sort_order',
            'employees' => 'employees_count',
            'is_active' => 'is_active',
        ], $request->query('sort'), $request->query('direction'), 'level');

        $educationLevels = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('education-levels.index', [
            'educationLevels' => $educationLevels,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Show the form for creating an education level.
     */
    public function create(): View
    {
        return view('education-levels.create');
    }

    /**
     * Store a newly created education level.
     */
    public function store(Request $request): RedirectResponse
    {
        EducationLevel::create($this->validated($request));

        return redirect()->route('education-levels.index')->with('status', __('Education level created.'));
    }

    /**
     * Show the form for editing an education level.
     */
    public function edit(EducationLevel $educationLevel): View
    {
        return view('education-levels.edit', ['educationLevel' => $educationLevel]);
    }

    /**
     * Update the specified education level.
     */
    public function update(Request $request, EducationLevel $educationLevel): RedirectResponse
    {
        $educationLevel->update($this->validated($request, $educationLevel));

        return redirect()->route('education-levels.index')->with('status', __('Education level updated.'));
    }

    /**
     * Remove the education level; levels assigned to employees cannot be deleted.
     */
    public function destroy(EducationLevel $educationLevel): RedirectResponse
    {
        if ($educationLevel->employees()->exists()) {
            return back()->withErrors(['education_level' => __('This education level is assigned to employees and cannot be deleted.')]);
        }

        $educationLevel->delete();

        return redirect()->route('education-levels.index')->with('status', __('Education level deleted.'));
    }

    /**
     * Validate and normalize the education level payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?EducationLevel $educationLevel = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('education_levels', 'name')->ignore($educationLevel?->id)],
            'level' => ['required', 'integer', 'min:0', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] ??= 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
