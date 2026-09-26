<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalMatrix;
use App\Models\ApprovalRequest;
use App\Models\Benefit;
use App\Models\BenefitEnrollment;
use App\Models\Employee;
use App\Services\ApprovalWorkflowService;
use App\Services\BenefitEligibilityService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BenefitEnrollmentsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of benefit enrollments.
     */
    public function index(Request $request): View
    {
        $query = BenefitEnrollment::query()
            ->select('benefit_enrollments.*')
            ->leftJoin('benefits', 'benefits.id', '=', 'benefit_enrollments.benefit_id')
            ->leftJoin('employees', 'employees.id', '=', 'benefit_enrollments.employee_id')
            ->with(['benefit:id,code,name', 'employee:id,name,employee_number']);

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'benefits.code',
            'benefits.name',
            'employees.name',
            'employees.employee_number',
        ]);

        $this->applyTableFilters($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'benefit' => 'benefits.name',
            'employee' => 'employees.name',
            'status' => 'benefit_enrollments.status',
            'effective_from' => 'benefit_enrollments.effective_from',
        ], $request->query('sort'), $request->query('direction'), 'effective_from');

        $enrollments = $query->paginate($this->tablePerPage($request))->withQueryString();
        $changeRequests = ApprovalRequest::query()
            ->where('module_key', 'benefit-enrollments')
            ->with(['maker', 'stages.roles'])
            ->latest('submitted_at')
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString();

        return view('benefit-enrollments.index', [
            'enrollments' => $enrollments,
            'changeRequests' => $changeRequests,
            'sort' => $sort,
            'direction' => $direction,
            'benefits' => Benefit::query()->orderBy('name')->get(['id', 'code', 'name']),
            'approvalReady' => ApprovalMatrix::query()->where('module_key', 'benefit-enrollments')->where('is_active', true)->exists(),
        ]);
    }

    /**
     * Display the enrollment details with its claims.
     */
    public function show(BenefitEnrollment $benefitEnrollment, BenefitEligibilityService $service): View
    {
        $benefitEnrollment->load(['benefit', 'employee', 'claims' => fn ($query) => $query->latest('claim_date')]);

        return view('benefit-enrollments.show', [
            'enrollment' => $benefitEnrollment,
            'consumed' => $service->consumedAmount($benefitEnrollment),
            'remaining' => $service->remainingAmount($benefitEnrollment),
        ]);
    }

    /**
     * Show the form for creating an enrollment.
     */
    public function create(): View
    {
        return view('benefit-enrollments.create', $this->formOptions());
    }

    /**
     * Store a newly created enrollment.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow, BenefitEligibilityService $service): RedirectResponse
    {
        $data = $this->validated($request);

        $benefit = Benefit::query()->findOrFail($data['benefit_id']);
        $employee = Employee::query()->withTrashed()->findOrFail($data['employee_id']);

        $service->assertEnrollable($employee, $benefit);
        $service->assertSingleActiveEnrollment($benefit, $employee);

        if ($workflow->isApprovalActive('benefit-enrollments')) {
            $workflow->submit($request->user(), 'benefit-enrollments', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()
                ->route('benefit-enrollments.index', ['tab' => 'requests'])
                ->with('status', __('Enrollment change request submitted for approval.'));
        }

        BenefitEnrollment::create($data);

        return redirect()->route('benefit-enrollments.index')->with('status', __('Enrollment created.'));
    }

    /**
     * Show the form for editing an enrollment.
     */
    public function edit(BenefitEnrollment $benefitEnrollment): View
    {
        return view('benefit-enrollments.edit', [
            'enrollment' => $benefitEnrollment,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified enrollment.
     */
    public function update(Request $request, BenefitEnrollment $benefitEnrollment, ApprovalWorkflowService $workflow, BenefitEligibilityService $service): RedirectResponse
    {
        $data = $this->validated($request, $benefitEnrollment);

        $benefit = Benefit::query()->findOrFail($data['benefit_id']);
        $employee = Employee::query()->withTrashed()->findOrFail($data['employee_id']);

        $service->assertEnrollable($employee, $benefit);
        $service->assertSingleActiveEnrollment($benefit, $employee, $benefitEnrollment->id);

        if ($workflow->isApprovalActive('benefit-enrollments')) {
            $workflow->submit($request->user(), 'benefit-enrollments', ApprovalRequest::ACTION_UPDATE, $benefitEnrollment->id, $data);

            return redirect()
                ->route('benefit-enrollments.index', ['tab' => 'requests'])
                ->with('status', __('Enrollment change request submitted for approval.'));
        }

        $benefitEnrollment->update($data);

        return redirect()->route('benefit-enrollments.index')->with('status', __('Enrollment updated.'));
    }

    /**
     * Remove the enrollment; enrollments with claims cannot be deleted.
     */
    public function destroy(BenefitEnrollment $benefitEnrollment, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($benefitEnrollment->claims()->exists()) {
            return back()->withErrors(['enrollment' => __('This enrollment has claims and cannot be deleted.')]);
        }

        if ($workflow->isApprovalActive('benefit-enrollments')) {
            $workflow->submit(request()->user(), 'benefit-enrollments', ApprovalRequest::ACTION_DELETE, $benefitEnrollment->id, []);

            return redirect()
                ->route('benefit-enrollments.index', ['tab' => 'requests'])
                ->with('status', __('Enrollment deletion request submitted for approval.'));
        }

        $benefitEnrollment->delete();

        return redirect()->route('benefit-enrollments.index')->with('status', __('Enrollment deleted.'));
    }

    /**
     * Select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'benefits' => Benefit::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'employee_number']),
        ];
    }

    /**
     * Apply the benefit and status filters from the query string.
     */
    protected function applyTableFilters(Builder $query, Request $request): void
    {
        if ($benefitId = $request->query('benefit_id')) {
            $query->where('benefit_enrollments.benefit_id', $benefitId);
        }

        if ($employeeId = $request->query('employee_id')) {
            $query->where('benefit_enrollments.employee_id', $employeeId);
        }

        if (in_array($request->query('status'), BenefitEnrollment::STATUSES, true)) {
            $query->where('benefit_enrollments.status', $request->query('status'));
        }
    }

    /**
     * Validate and normalize the enrollment payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?BenefitEnrollment $enrollment = null): array
    {
        return $request->validate([
            'benefit_id' => ['required', Rule::exists('benefits', 'id')],
            'employee_id' => ['required', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'status' => ['required', Rule::in(BenefitEnrollment::STATUSES)],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
