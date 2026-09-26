<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalMatrix;
use App\Models\ApprovalRequest;
use App\Models\Benefit;
use App\Models\BenefitClaim;
use App\Models\BenefitEnrollment;
use App\Services\ApprovalWorkflowService;
use App\Services\BenefitEligibilityService;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BenefitClaimsController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of benefit claims.
     */
    public function index(Request $request): View
    {
        $query = BenefitClaim::query()
            ->select('benefit_claims.*')
            ->leftJoin('benefits', 'benefits.id', '=', 'benefit_claims.benefit_id')
            ->leftJoin('employees', 'employees.id', '=', 'benefit_claims.employee_id')
            ->with(['benefit:id,code,name', 'employee:id,name,employee_number', 'enrollment:id']);

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'benefits.code',
            'benefits.name',
            'employees.name',
            'employees.employee_number',
            'benefit_claims.description',
        ]);

        $this->applyTableFilters($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'claim_date' => 'benefit_claims.claim_date',
            'amount' => 'benefit_claims.amount',
            'status' => 'benefit_claims.status',
            'benefit' => 'benefits.name',
            'employee' => 'employees.name',
        ], $request->query('sort'), $request->query('direction'), 'claim_date');

        $claims = $query->paginate($this->tablePerPage($request))->withQueryString();
        $changeRequests = ApprovalRequest::query()
            ->where('module_key', 'benefit-claims')
            ->with(['maker', 'stages.roles'])
            ->latest('submitted_at')
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString();

        return view('benefit-claims.index', [
            'claims' => $claims,
            'changeRequests' => $changeRequests,
            'sort' => $sort,
            'direction' => $direction,
            'benefits' => Benefit::query()->orderBy('name')->get(['id', 'code', 'name']),
            'approvalReady' => ApprovalMatrix::query()->where('module_key', 'benefit-claims')->where('is_active', true)->exists(),
        ]);
    }

    /**
     * Display the claim details with its balance and approval history.
     */
    public function show(BenefitClaim $benefitClaim, BenefitEligibilityService $service, ApprovalWorkflowService $workflow): View
    {
        $benefitClaim->load(['benefit', 'employee', 'enrollment.benefit', 'enrollment.employee']);

        $changeRequests = ApprovalRequest::query()
            ->where('module_key', 'benefit-claims')
            ->where('target_id', $benefitClaim->id)
            ->with(['maker', 'stages.roles'])
            ->latest('submitted_at')
            ->paginate(10, ['*'], 'requests_page');

        return view('benefit-claims.show', [
            'claim' => $benefitClaim,
            'consumed' => $service->consumedAmount($benefitClaim->enrollment, Carbon::parse($benefitClaim->claim_date)),
            'remaining' => $service->remainingAmount($benefitClaim->enrollment, Carbon::parse($benefitClaim->claim_date)),
            'changeRequests' => $changeRequests,
            'approvalActive' => $workflow->isApprovalActive('benefit-claims'),
        ]);
    }

    /**
     * Show the form for creating a claim.
     */
    public function create(BenefitEligibilityService $service): View
    {
        return view('benefit-claims.create', $this->formOptions($service));
    }

    /**
     * Store a newly created claim.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow, BenefitEligibilityService $service): RedirectResponse
    {
        $storedPath = null;

        if ($request->hasFile('receipt')) {
            $storedPath = $request->file('receipt')->store($this->receiptDirectory(), 'public');
        }

        try {
            $data = $this->validated($request);

            if ($storedPath !== null) {
                $data['receipt_path'] = $storedPath;
            }
        } catch (ValidationException $exception) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }

            throw $exception;
        }

        $enrollment = BenefitEnrollment::query()->with(['benefit', 'employee'])->findOrFail($data['benefit_enrollment_id']);

        if ((int) $data['benefit_id'] !== (int) $enrollment->benefit_id || (int) $data['employee_id'] !== (int) $enrollment->employee_id) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }

            return back()->withInput()->withErrors(['enrollment' => __('The benefit and employee must match the enrollment.')]);
        }

        try {
            $service->assertClaimable($enrollment, (string) $data['amount'], Carbon::parse($data['claim_date']));
        } catch (ValidationException $exception) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }

            throw $exception;
        }

        $data['status'] = BenefitClaim::STATUS_PENDING;

        if ($workflow->isApprovalActive('benefit-claims')) {
            try {
                $workflow->submit($request->user(), 'benefit-claims', ApprovalRequest::ACTION_CREATE, null, $data);
            } catch (ValidationException $exception) {
                if ($storedPath !== null) {
                    Storage::disk('public')->delete($storedPath);
                }

                throw $exception;
            }

            return redirect()
                ->route('benefit-claims.index', ['tab' => 'requests'])
                ->with('status', __('Claim change request submitted for approval.'));
        }

        BenefitClaim::create($data);

        return redirect()->route('benefit-claims.index')->with('status', __('Claim created.'));
    }

    /**
     * Show the form for editing a pending claim.
     */
    public function edit(BenefitClaim $benefitClaim, BenefitEligibilityService $service): View
    {
        if (! in_array($benefitClaim->status, BenefitClaim::RECOVERABLE, true)) {
            abort(403, __('Only pending claims can be edited.'));
        }

        return view('benefit-claims.edit', [
            'claim' => $benefitClaim,
            ...$this->formOptions($service),
        ]);
    }

    /**
     * Update the specified pending claim.
     */
    public function update(Request $request, BenefitClaim $benefitClaim, ApprovalWorkflowService $workflow, BenefitEligibilityService $service): RedirectResponse
    {
        if (! in_array($benefitClaim->status, BenefitClaim::RECOVERABLE, true)) {
            abort(403, __('Only pending claims can be edited.'));
        }

        $storedPath = null;

        if ($request->hasFile('receipt')) {
            $storedPath = $request->file('receipt')->store($this->receiptDirectory(), 'public');
        }

        try {
            $data = $this->validated($request, $benefitClaim);

            if ($storedPath !== null) {
                $data['receipt_path'] = $storedPath;
            } elseif ($request->boolean('remove_receipt')) {
                $data['receipt_path'] = null;
            } else {
                $data['receipt_path'] = $benefitClaim->receipt_path;
            }
        } catch (ValidationException $exception) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }

            throw $exception;
        }

        $enrollment = BenefitEnrollment::query()->with(['benefit', 'employee'])->findOrFail($data['benefit_enrollment_id']);

        if ((int) $data['benefit_id'] !== (int) $enrollment->benefit_id || (int) $data['employee_id'] !== (int) $enrollment->employee_id) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }

            return back()->withInput()->withErrors(['enrollment' => __('The benefit and employee must match the enrollment.')]);
        }

        try {
            $service->assertClaimable($enrollment, (string) $data['amount'], Carbon::parse($data['claim_date']), $benefitClaim->id);
        } catch (ValidationException $exception) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }

            throw $exception;
        }

        $data['status'] = BenefitClaim::STATUS_PENDING;

        if ($workflow->isApprovalActive('benefit-claims')) {
            try {
                $workflow->submit($request->user(), 'benefit-claims', ApprovalRequest::ACTION_UPDATE, $benefitClaim->id, $data);
            } catch (ValidationException $exception) {
                if ($storedPath !== null) {
                    Storage::disk('public')->delete($storedPath);
                }

                throw $exception;
            }

            return redirect()
                ->route('benefit-claims.index', ['tab' => 'requests'])
                ->with('status', __('Claim change request submitted for approval.'));
        }

        $old = $benefitClaim->receipt_path;
        $benefitClaim->update($data);

        if ($storedPath !== null && $old) {
            Storage::disk('public')->delete($old);
        }

        if ($request->boolean('remove_receipt') && $old && $old !== $benefitClaim->receipt_path) {
            Storage::disk('public')->delete($old);
        }

        return redirect()->route('benefit-claims.index')->with('status', __('Claim updated.'));
    }

    /**
     * Remove a pending claim.
     */
    public function destroy(BenefitClaim $benefitClaim, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if (! in_array($benefitClaim->status, BenefitClaim::RECOVERABLE, true)) {
            return back()->withErrors(['claim' => __('Only pending claims can be deleted.')]);
        }

        if ($workflow->isApprovalActive('benefit-claims')) {
            $workflow->submit(request()->user(), 'benefit-claims', ApprovalRequest::ACTION_DELETE, $benefitClaim->id, []);

            return redirect()
                ->route('benefit-claims.index', ['tab' => 'requests'])
                ->with('status', __('Claim deletion request submitted for approval.'));
        }

        $receipt = $benefitClaim->receipt_path;
        $benefitClaim->delete();

        if ($receipt) {
            Storage::disk('public')->delete($receipt);
        }

        return redirect()->route('benefit-claims.index')->with('status', __('Claim deleted.'));
    }

    /**
     * Approve a pending claim when the approval matrix is inactive.
     */
    public function approve(Request $request, BenefitClaim $benefitClaim, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($workflow->isApprovalActive('benefit-claims')) {
            abort(403, __('Use the Approvals page to decide this claim.'));
        }

        if ($benefitClaim->status !== BenefitClaim::STATUS_PENDING) {
            return back()->withErrors(['claim' => __('Only pending claims can be approved.')]);
        }

        $data = $request->validate(['comment' => ['nullable', 'string']]);

        app(BenefitEligibilityService::class)->assertClaimable(
            $benefitClaim->enrollment()->with(['benefit', 'employee'])->firstOrFail(),
            (string) $benefitClaim->amount,
            Carbon::parse($benefitClaim->claim_date),
            $benefitClaim->id
        );

        $benefitClaim->update([
            'status' => BenefitClaim::STATUS_APPROVED,
            'resolution_comment' => $data['comment'] ?? null,
            'decided_at' => now(),
        ]);

        return redirect()->route('benefit-claims.show', $benefitClaim)->with('status', __('Claim approved.'));
    }

    /**
     * Reject a pending claim when the approval matrix is inactive.
     */
    public function reject(Request $request, BenefitClaim $benefitClaim, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($workflow->isApprovalActive('benefit-claims')) {
            abort(403, __('Use the Approvals page to decide this claim.'));
        }

        if ($benefitClaim->status !== BenefitClaim::STATUS_PENDING) {
            return back()->withErrors(['claim' => __('Only pending claims can be rejected.')]);
        }

        $data = $request->validate(['comment' => ['required', 'string']]);

        $benefitClaim->update([
            'status' => BenefitClaim::STATUS_REJECTED,
            'resolution_comment' => $data['comment'],
            'decided_at' => now(),
        ]);

        return redirect()->route('benefit-claims.show', $benefitClaim)->with('status', __('Claim rejected.'));
    }

    /**
     * Mark an approved claim as paid when the approval matrix is inactive.
     */
    public function markPaid(BenefitClaim $benefitClaim, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($workflow->isApprovalActive('benefit-claims')) {
            abort(403, __('Use the Approvals page to decide this claim.'));
        }

        if ($benefitClaim->status !== BenefitClaim::STATUS_APPROVED) {
            return back()->withErrors(['claim' => __('Only approved claims can be marked as paid.')]);
        }

        $benefitClaim->update([
            'status' => BenefitClaim::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return redirect()->route('benefit-claims.show', $benefitClaim)->with('status', __('Claim marked as paid.'));
    }

    /**
     * Cancel a pending claim; privileged cancel of decided claims is not offered in v1.
     */
    public function cancel(Request $request, BenefitClaim $benefitClaim, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($workflow->isApprovalActive('benefit-claims')) {
            abort(403, __('Use the Approvals page to decide this claim.'));
        }

        if (! in_array($benefitClaim->status, BenefitClaim::RECOVERABLE, true)) {
            return back()->withErrors(['claim' => __('Only pending claims can be cancelled.')]);
        }

        $data = $request->validate(['comment' => ['nullable', 'string']]);

        $benefitClaim->update([
            'status' => BenefitClaim::STATUS_CANCELLED,
            'resolution_comment' => $data['comment'] ?? null,
            'decided_at' => now(),
        ]);

        return redirect()->route('benefit-claims.show', $benefitClaim)->with('status', __('Claim cancelled.'));
    }

    /**
     * Select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(?BenefitEligibilityService $service = null): array
    {
        $service ??= app(BenefitEligibilityService::class);

        $enrollments = BenefitEnrollment::query()
            ->with(['benefit:id,code,name,limit_amount', 'employee:id,name,employee_number'])
            ->where('status', BenefitEnrollment::STATUS_ACTIVE)
            ->latest('effective_from')
            ->get()
            ->filter(fn (BenefitEnrollment $enrollment): bool => $enrollment->isUsable())
            ->values()
            ->map(fn (BenefitEnrollment $enrollment): array => [
                'id' => $enrollment->id,
                'label' => $enrollment->benefit?->name.' — '.$enrollment->employee?->name,
                'benefit_id' => $enrollment->benefit_id,
                'benefit_name' => $enrollment->benefit?->name,
                'employee_id' => $enrollment->employee_id,
                'employee_name' => $enrollment->employee?->name,
                'remaining' => $service->remainingAmount($enrollment->loadMissing(['benefit', 'employee'])),
            ]);

        return [
            'enrollments' => $enrollments,
            'benefits' => Benefit::query()->orderBy('name')->get(['id', 'code', 'name']),
        ];
    }

    /**
     * Apply the status, benefit, and claim-date filters from the query string.
     */
    protected function applyTableFilters(Builder $query, Request $request): void
    {
        if (in_array($request->query('status'), BenefitClaim::STATUSES, true)) {
            $query->where('benefit_claims.status', $request->query('status'));
        }

        if ($benefitId = $request->query('benefit_id')) {
            $query->where('benefit_claims.benefit_id', $benefitId);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('benefit_claims.claim_date', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('benefit_claims.claim_date', '<=', $to);
        }
    }

    /**
     * Validate and normalize the claim payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?BenefitClaim $claim = null): array
    {
        $data = $request->validate([
            'benefit_id' => ['required', Rule::exists('benefits', 'id')],
            'benefit_enrollment_id' => ['required', Rule::exists('benefit_enrollments', 'id')],
            'employee_id' => ['required', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'claim_date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:4096'],
            'remove_receipt' => ['nullable', 'boolean'],
        ]);

        $data['amount'] = number_format((float) $data['amount'], 2, '.', '');

        unset($data['receipt'], $data['remove_receipt']);

        return $data;
    }

    /**
     * Storage directory for claim receipts, partitioned by month.
     */
    protected function receiptDirectory(): string
    {
        return 'benefit-claims/'.now()->format('Y').'/'.now()->format('m');
    }
}
