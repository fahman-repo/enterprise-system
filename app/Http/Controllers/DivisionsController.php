<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\Division;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DivisionsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of divisions.
     */
    public function index(Request $request): View
    {
        $query = Division::query()->withCount('departments');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'code',
            'name',
            'description',
        ]);

        $this->applyTableStatusFilter($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'code' => 'code',
            'name' => 'name',
            'departments' => 'departments_count',
            'is_active' => 'is_active',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $divisions = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('divisions.index', ['divisions' => $divisions, 'sort' => $sort, 'direction' => $direction]);
    }

    /**
     * Show the form for creating a division.
     */
    public function create(): View
    {
        return view('divisions.create');
    }

    /**
     * Store a newly created division.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('divisions')) {
            $workflow->submit($request->user(), 'divisions', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()->route('divisions.index')
                ->with('status', __('Division change request submitted for approval.'));
        }

        Division::create($data);

        return redirect()->route('divisions.index')->with('status', __('Division created.'));
    }

    /**
     * Show the form for editing a division.
     */
    public function edit(Division $division): View
    {
        return view('divisions.edit', ['division' => $division]);
    }

    /**
     * Update the specified division.
     */
    public function update(Request $request, Division $division, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $division);

        if ($workflow->isApprovalActive('divisions')) {
            $workflow->submit($request->user(), 'divisions', ApprovalRequest::ACTION_UPDATE, $division->id, $data);

            return redirect()->route('divisions.index')
                ->with('status', __('Division change request submitted for approval.'));
        }

        $division->update($data);

        return redirect()->route('divisions.index')->with('status', __('Division updated.'));
    }

    /**
     * Remove the division; divisions with departments cannot be deleted.
     */
    public function destroy(Division $division, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($division->departments()->exists()) {
            return back()->withErrors(['division' => __('This division has departments and cannot be deleted.')]);
        }

        if ($workflow->isApprovalActive('divisions')) {
            $workflow->submit(request()->user(), 'divisions', ApprovalRequest::ACTION_DELETE, $division->id, []);

            return redirect()->route('divisions.index')
                ->with('status', __('Division change request submitted for approval.'));
        }

        $division->delete();

        return redirect()->route('divisions.index')->with('status', __('Division deleted.'));
    }

    /**
     * Validate and normalize the division payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Division $division = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('divisions', 'code')->ignore($division?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
