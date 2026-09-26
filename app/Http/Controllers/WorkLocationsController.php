<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\WorkLocation;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkLocationsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of work locations.
     */
    public function index(Request $request): View
    {
        $query = WorkLocation::query()->withCount('employees');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'code',
            'name',
            'address',
            'city',
            'province',
        ]);

        $this->applyTableStatusFilter($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'code' => 'code',
            'name' => 'name',
            'city' => 'city',
            'province' => 'province',
            'employees' => 'employees_count',
            'is_active' => 'is_active',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $locations = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('work-locations.index', ['locations' => $locations, 'sort' => $sort, 'direction' => $direction]);
    }

    /**
     * Show the form for creating a work location.
     */
    public function create(): View
    {
        return view('work-locations.create');
    }

    /**
     * Store a newly created work location.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('work-locations')) {
            $workflow->submit($request->user(), 'work-locations', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()
                ->route('work-locations.index')
                ->with('status', __('Work Location change request submitted for approval.'));
        }

        WorkLocation::create($data);

        return redirect()->route('work-locations.index')->with('status', __('Work location created.'));
    }

    /**
     * Show the form for editing a work location.
     */
    public function edit(WorkLocation $workLocation): View
    {
        return view('work-locations.edit', ['location' => $workLocation]);
    }

    /**
     * Update the specified work location.
     */
    public function update(Request $request, WorkLocation $workLocation, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $workLocation);

        if ($workflow->isApprovalActive('work-locations')) {
            $workflow->submit($request->user(), 'work-locations', ApprovalRequest::ACTION_UPDATE, $workLocation->id, $data);

            return redirect()
                ->route('work-locations.index')
                ->with('status', __('Work Location change request submitted for approval.'));
        }

        $workLocation->update($data);

        return redirect()->route('work-locations.index')->with('status', __('Work location updated.'));
    }

    /**
     * Remove the work location; locations assigned to employees cannot be deleted.
     */
    public function destroy(WorkLocation $workLocation, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($workLocation->employees()->exists()) {
            return back()->withErrors(['work_location' => __('This work location is assigned to employees and cannot be deleted.')]);
        }

        if ($workflow->isApprovalActive('work-locations')) {
            $workflow->submit(request()->user(), 'work-locations', ApprovalRequest::ACTION_DELETE, $workLocation->id, []);

            return redirect()
                ->route('work-locations.index')
                ->with('status', __('Work Location change request submitted for approval.'));
        }

        $workLocation->delete();

        return redirect()->route('work-locations.index')->with('status', __('Work location deleted.'));
    }

    /**
     * Validate and normalize the work location payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?WorkLocation $workLocation = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('work_locations', 'code')->ignore($workLocation?->id)],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
