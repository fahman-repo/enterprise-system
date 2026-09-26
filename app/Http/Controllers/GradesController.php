<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalMatrix;
use App\Models\ApprovalRequest;
use App\Models\Grade;
use App\Services\ApprovalWorkflowService;
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
        $changeRequests = ApprovalRequest::query()
            ->where('module_key', 'grades')
            ->with(['maker', 'stages.roles'])
            ->latest('submitted_at')
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString();

        return view('grades.index', [
            'grades' => $grades,
            'changeRequests' => $changeRequests,
            'sort' => $sort,
            'direction' => $direction,
            'approvalReady' => ApprovalMatrix::query()->where('module_key', 'grades')->where('is_active', true)->exists(),
        ]);
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
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('grades')) {
            $workflow->submit($request->user(), 'grades', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()
                ->route('grades.index', ['tab' => 'requests'])
                ->with('status', __('Grade change request submitted for approval.'));
        }

        Grade::create($data);

        return redirect()->route('grades.index')->with('status', __('Grade saved.'));
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
    public function update(Request $request, Grade $grade, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $grade);

        if ($workflow->isApprovalActive('grades')) {
            $workflow->submit($request->user(), 'grades', ApprovalRequest::ACTION_UPDATE, $grade->id, $data);

            return redirect()
                ->route('grades.index', ['tab' => 'requests'])
                ->with('status', __('Grade change request submitted for approval.'));
        }

        $grade->update($data);

        return redirect()->route('grades.index')->with('status', __('Grade saved.'));
    }

    /**
     * Remove the grade; grades assigned to employees cannot be deleted.
     */
    public function destroy(Grade $grade, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($grade->employees()->exists()) {
            return back()->withErrors(['grade' => __('This grade is assigned to employees and cannot be deleted.')]);
        }

        if ($workflow->isApprovalActive('grades')) {
            $workflow->submit(request()->user(), 'grades', ApprovalRequest::ACTION_DELETE, $grade->id, []);

            return redirect()
                ->route('grades.index', ['tab' => 'requests'])
                ->with('status', __('Grade deletion request submitted for approval.'));
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
