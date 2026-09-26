<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\Department;
use App\Models\Position;
use App\Services\ApprovalWorkflowService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PositionsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of positions.
     */
    public function index(Request $request): View
    {
        $query = Position::query()
            ->select('positions.*')
            ->leftJoin('departments', 'departments.id', '=', 'positions.department_id')
            ->with('department')
            ->withCount('employees');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'positions.code',
            'positions.name',
            'positions.description',
            'departments.name',
        ]);

        $this->applyTableFilters($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'code' => 'positions.code',
            'name' => 'positions.name',
            'department' => 'departments.name',
            'employees' => 'employees_count',
            'is_active' => 'positions.is_active',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $positions = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('positions.index', [
            'positions' => $positions,
            'sort' => $sort,
            'direction' => $direction,
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a position.
     */
    public function create(): View
    {
        return view('positions.create', $this->formOptions());
    }

    /**
     * Store a newly created position.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('positions')) {
            $workflow->submit($request->user(), 'positions', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()->route('positions.index')
                ->with('status', __('Position change request submitted for approval.'));
        }

        Position::create($data);

        return redirect()->route('positions.index')->with('status', __('Position created.'));
    }

    /**
     * Show the form for editing a position.
     */
    public function edit(Position $position): View
    {
        return view('positions.edit', [
            'position' => $position,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified position.
     */
    public function update(Request $request, Position $position, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $position);

        if ($workflow->isApprovalActive('positions')) {
            $workflow->submit($request->user(), 'positions', ApprovalRequest::ACTION_UPDATE, $position->id, $data);

            return redirect()->route('positions.index')
                ->with('status', __('Position change request submitted for approval.'));
        }

        $position->update($data);

        return redirect()->route('positions.index')->with('status', __('Position updated.'));
    }

    /**
     * Remove the position; positions held by employees cannot be deleted.
     */
    public function destroy(Position $position, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($position->employees()->exists()) {
            return back()->withErrors(['position' => __('This position is assigned to employees and cannot be deleted.')]);
        }

        if ($workflow->isApprovalActive('positions')) {
            $workflow->submit(request()->user(), 'positions', ApprovalRequest::ACTION_DELETE, $position->id, []);

            return redirect()->route('positions.index')
                ->with('status', __('Position change request submitted for approval.'));
        }

        $position->delete();

        return redirect()->route('positions.index')->with('status', __('Position deleted.'));
    }

    /**
     * Select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'departments' => Department::query()->with('division')->orderBy('name')->get(),
        ];
    }

    /**
     * Apply the department and status filters from the query string.
     *
     * The list UI allows a single active filter; only the first matching
     * parameter is applied so stale URLs cannot combine filters.
     */
    protected function applyTableFilters(Builder $query, Request $request): void
    {
        if ($departmentId = $request->query('department_id')) {
            $query->where('positions.department_id', $departmentId);

            return;
        }

        $this->applyTableStatusFilter($query, $request, 'positions.is_active');
    }

    /**
     * Validate and normalize the position payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Position $position = null): array
    {
        $data = $request->validate([
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'code' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('positions', 'code')->ignore($position?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
