<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalMatrix;
use App\Models\ApprovalRequest;
use App\Models\Benefit;
use App\Models\EmploymentStatus;
use App\Models\Grade;
use App\Services\ApprovalWorkflowService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BenefitsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of benefits.
     */
    public function index(Request $request): View
    {
        $query = Benefit::query()->withCount(['enrollments', 'claims']);

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'code',
            'name',
            'description',
        ]);

        $this->applyTableStatusFilter($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'code' => 'code',
            'name' => 'name',
            'type' => 'type',
            'period' => 'period',
            'limit_amount' => 'limit_amount',
            'enrollments' => 'enrollments_count',
            'is_active' => 'is_active',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $benefits = $query->paginate($this->tablePerPage($request))->withQueryString();
        $changeRequests = ApprovalRequest::query()
            ->where('module_key', 'benefits')
            ->with(['maker', 'stages.roles'])
            ->latest('submitted_at')
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString();

        return view('benefits.index', [
            'benefits' => $benefits,
            'changeRequests' => $changeRequests,
            'sort' => $sort,
            'direction' => $direction,
            'approvalReady' => ApprovalMatrix::query()->where('module_key', 'benefits')->where('is_active', true)->exists(),
        ]);
    }

    /**
     * Show the form for creating a benefit.
     */
    public function create(): View
    {
        return view('benefits.create', $this->formOptions());
    }

    /**
     * Store a newly created benefit.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('benefits')) {
            $workflow->submit($request->user(), 'benefits', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()
                ->route('benefits.index', ['tab' => 'requests'])
                ->with('status', __('Benefit change request submitted for approval.'));
        }

        $gradeIds = $data['eligible_grade_ids'] ?? [];
        $statusIds = $data['eligible_employment_status_ids'] ?? [];

        unset($data['eligible_grade_ids'], $data['eligible_employment_status_ids']);

        $benefit = Benefit::create($data);
        $benefit->eligibleGrades()->sync($gradeIds);
        $benefit->eligibleEmploymentStatuses()->sync($statusIds);

        return redirect()->route('benefits.index')->with('status', __('Benefit created.'));
    }

    /**
     * Show the form for editing a benefit.
     */
    public function edit(Benefit $benefit): View
    {
        $benefit->load(['eligibleGrades:id', 'eligibleEmploymentStatuses:id']);

        return view('benefits.edit', [
            'benefit' => $benefit,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified benefit.
     */
    public function update(Request $request, Benefit $benefit, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $benefit);

        if ($workflow->isApprovalActive('benefits')) {
            $workflow->submit($request->user(), 'benefits', ApprovalRequest::ACTION_UPDATE, $benefit->id, $data);

            return redirect()
                ->route('benefits.index', ['tab' => 'requests'])
                ->with('status', __('Benefit change request submitted for approval.'));
        }

        $gradeIds = $data['eligible_grade_ids'] ?? [];
        $statusIds = $data['eligible_employment_status_ids'] ?? [];

        unset($data['eligible_grade_ids'], $data['eligible_employment_status_ids']);

        $benefit->update($data);
        $benefit->eligibleGrades()->sync($gradeIds);
        $benefit->eligibleEmploymentStatuses()->sync($statusIds);

        return redirect()->route('benefits.index')->with('status', __('Benefit updated.'));
    }

    /**
     * Remove the benefit; benefits with enrollments or claims cannot be deleted.
     */
    public function destroy(Benefit $benefit, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($benefit->enrollments()->exists() || $benefit->claims()->exists()) {
            return back()->withErrors(['benefit' => __('This benefit has enrollments or claims and cannot be deleted.')]);
        }

        if ($workflow->isApprovalActive('benefits')) {
            $workflow->submit(request()->user(), 'benefits', ApprovalRequest::ACTION_DELETE, $benefit->id, []);

            return redirect()
                ->route('benefits.index', ['tab' => 'requests'])
                ->with('status', __('Benefit deletion request submitted for approval.'));
        }

        $benefit->delete();

        return redirect()->route('benefits.index')->with('status', __('Benefit deleted.'));
    }

    /**
     * Select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'grades' => Grade::query()->orderBy('level')->orderBy('name')->get(['id', 'name']),
            'employmentStatuses' => EmploymentStatus::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * Validate and normalize the benefit payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Benefit $benefit = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('benefits', 'code')->ignore($benefit?->id)],
            'name' => ['required', 'string', 'max:255', Rule::unique('benefits', 'name')->ignore($benefit?->id)],
            'type' => ['required', Rule::in(Benefit::TYPES)],
            'description' => ['nullable', 'string'],
            'limit_amount' => ['required', 'numeric', 'min:0'],
            'period' => ['required', Rule::in(Benefit::PERIODS)],
            'min_tenure_months' => ['required', 'integer', 'min:0'],
            'requires_receipt' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'eligible_grade_ids' => ['nullable', 'array'],
            'eligible_grade_ids.*' => [Rule::exists('grades', 'id')],
            'eligible_employment_status_ids' => ['nullable', 'array'],
            'eligible_employment_status_ids.*' => [Rule::exists('employment_statuses', 'id')],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['requires_receipt'] = $request->boolean('requires_receipt');
        $data['limit_amount'] = number_format((float) $data['limit_amount'], 2, '.', '');
        $data['eligible_grade_ids'] = array_values(array_map('intval', $data['eligible_grade_ids'] ?? []));
        $data['eligible_employment_status_ids'] = array_values(array_map('intval', $data['eligible_employment_status_ids'] ?? []));

        return $data;
    }

    /**
     * Apply the status filter from the query string.
     */
    protected function applyTableFilters(Builder $query, Request $request): void
    {
        $this->applyTableStatusFilter($query, $request);
    }
}
