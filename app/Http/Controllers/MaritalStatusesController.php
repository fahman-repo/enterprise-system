<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\MaritalStatus;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaritalStatusesController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of marital statuses.
     */
    public function index(Request $request): View
    {
        $query = MaritalStatus::query()->withCount('employees');

        $this->applyTableSearch($query, $this->tableSearch($request), ['name']);

        $this->applyTableStatusFilter($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'name' => 'name',
            'employees' => 'employees_count',
            'sort_order' => 'sort_order',
            'is_active' => 'is_active',
        ], $request->query('sort'), $request->query('direction'), 'sort_order');

        $maritalStatuses = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('marital-statuses.index', [
            'maritalStatuses' => $maritalStatuses,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Show the form for creating a marital status.
     */
    public function create(): View
    {
        return view('marital-statuses.create');
    }

    /**
     * Store a newly created marital status.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('marital-statuses')) {
            $workflow->submit($request->user(), 'marital-statuses', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()
                ->route('marital-statuses.index')
                ->with('status', __('Marital Status change request submitted for approval.'));
        }

        MaritalStatus::create($data);

        return redirect()->route('marital-statuses.index')->with('status', __('Marital status created.'));
    }

    /**
     * Show the form for editing a marital status.
     */
    public function edit(MaritalStatus $maritalStatus): View
    {
        return view('marital-statuses.edit', ['maritalStatus' => $maritalStatus]);
    }

    /**
     * Update the specified marital status.
     */
    public function update(Request $request, MaritalStatus $maritalStatus, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $maritalStatus);

        if ($workflow->isApprovalActive('marital-statuses')) {
            $workflow->submit($request->user(), 'marital-statuses', ApprovalRequest::ACTION_UPDATE, $maritalStatus->id, $data);

            return redirect()
                ->route('marital-statuses.index')
                ->with('status', __('Marital Status change request submitted for approval.'));
        }

        $maritalStatus->update($data);

        return redirect()->route('marital-statuses.index')->with('status', __('Marital status updated.'));
    }

    /**
     * Remove the marital status; statuses assigned to employees cannot be deleted.
     */
    public function destroy(MaritalStatus $maritalStatus, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($maritalStatus->employees()->exists()) {
            return back()->withErrors(['marital_status' => __('This marital status is assigned to employees and cannot be deleted.')]);
        }

        if ($workflow->isApprovalActive('marital-statuses')) {
            $workflow->submit(request()->user(), 'marital-statuses', ApprovalRequest::ACTION_DELETE, $maritalStatus->id, []);

            return redirect()
                ->route('marital-statuses.index')
                ->with('status', __('Marital Status change request submitted for approval.'));
        }

        $maritalStatus->delete();

        return redirect()->route('marital-statuses.index')->with('status', __('Marital status deleted.'));
    }

    /**
     * Validate and normalize the marital status payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?MaritalStatus $maritalStatus = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('marital_statuses', 'name')->ignore($maritalStatus?->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] ??= 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
