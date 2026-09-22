<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\Grade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GradesController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of grades.
     */
    public function index(Request $request): View
    {
        $query = Grade::query()->withCount('employees');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'name',
            'description',
        ]);

        $this->applyTableStatusFilter($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'name' => 'name',
            'level' => 'level',
            'employees' => 'employees_count',
            'is_active' => 'is_active',
        ], $request->query('sort'), $request->query('direction'), 'level');

        $grades = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('grades.index', ['grades' => $grades, 'sort' => $sort, 'direction' => $direction]);
    }

    /**
     * Show the form for creating a grade.
     */
    public function create(): View
    {
        return view('grades.create');
    }

    /**
     * Store a newly created grade.
     */
    public function store(Request $request): RedirectResponse
    {
        Grade::create($this->validated($request));

        return redirect()->route('grades.index')->with('status', __('Grade created.'));
    }

    /**
     * Show the form for editing a grade.
     */
    public function edit(Grade $grade): View
    {
        return view('grades.edit', ['grade' => $grade]);
    }

    /**
     * Update the specified grade.
     */
    public function update(Request $request, Grade $grade): RedirectResponse
    {
        $grade->update($this->validated($request, $grade));

        return redirect()->route('grades.index')->with('status', __('Grade updated.'));
    }

    /**
     * Remove the grade; grades assigned to employees cannot be deleted.
     */
    public function destroy(Grade $grade): RedirectResponse
    {
        if ($grade->employees()->exists()) {
            return back()->withErrors(['grade' => __('This grade is assigned to employees and cannot be deleted.')]);
        }

        $grade->delete();

        return redirect()->route('grades.index')->with('status', __('Grade deleted.'));
    }

    /**
     * Validate and normalize the grade payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Grade $grade = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('grades', 'name')->ignore($grade?->id)],
            'level' => ['required', 'integer', 'min:0', 'max:1000'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
