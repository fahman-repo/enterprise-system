<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\EmploymentStatus;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmploymentStatusesController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of employment statuses.
     */
    public function index(Request $request): View
    {
        $query = EmploymentStatus::query()->withCount('employees');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'code',
            'name',
            'description',
        ]);

        $this->applyTableStatusFilter($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'code' => 'code',
            'name' => 'name',
            'employees' => 'employees_count',
            'sort_order' => 'sort_order',
            'is_active' => 'is_active',
        ], $request->query('sort'), $request->query('direction'), 'sort_order');

        $statuses = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('employment-statuses.index', [
            'statuses' => $statuses,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Show the form for creating an employment status.
     */
    public function create(): View
    {
        return view('employment-statuses.create');
    }

    /**
     * Store a newly created employment status.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('employment-statuses')) {
            $workflow->submit($request->user(), 'employment-statuses', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()
                ->route('employment-statuses.index')
                ->with('status', __('Employment Status change request submitted for approval.'));
        }

        EmploymentStatus::create($data);

        return redirect()->route('employment-statuses.index')->with('status', __('Employment status created.'));
    }

    /**
     * Show the form for editing an employment status.
     */
    public function edit(EmploymentStatus $employmentStatus): View
    {
        return view('employment-statuses.edit', ['status' => $employmentStatus]);
    }

    /**
     * Update the specified employment status.
     */
    public function update(Request $request, EmploymentStatus $employmentStatus, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $employmentStatus);

        if ($workflow->isApprovalActive('employment-statuses')) {
            $workflow->submit($request->user(), 'employment-statuses', ApprovalRequest::ACTION_UPDATE, $employmentStatus->id, $data);

            return redirect()
                ->route('employment-statuses.index')
                ->with('status', __('Employment Status change request submitted for approval.'));
        }

        $employmentStatus->update($data);

        return redirect()->route('employment-statuses.index')->with('status', __('Employment status updated.'));
    }

    /**
     * Remove the employment status; statuses assigned to employees cannot be deleted.
     */
    public function destroy(EmploymentStatus $employmentStatus, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($employmentStatus->employees()->exists()) {
            return back()->withErrors(['employment_status' => __('This employment status is assigned to employees and cannot be deleted.')]);
        }

        if ($workflow->isApprovalActive('employment-statuses')) {
            $workflow->submit(request()->user(), 'employment-statuses', ApprovalRequest::ACTION_DELETE, $employmentStatus->id, []);

            return redirect()
                ->route('employment-statuses.index')
                ->with('status', __('Employment Status change request submitted for approval.'));
        }

        $employmentStatus->delete();

        return redirect()->route('employment-statuses.index')->with('status', __('Employment status deleted.'));
    }

    /**
     * Validate and normalize the employment status payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?EmploymentStatus $employmentStatus = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('employment_statuses', 'code')->ignore($employmentStatus?->id)],
            'name' => ['required', 'string', 'max:255', Rule::unique('employment_statuses', 'name')->ignore($employmentStatus?->id)],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] ??= 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
