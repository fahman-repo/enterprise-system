<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Http\Requests\Approvals\ApproveApprovalRequestRequest;
use App\Http\Requests\Approvals\CancelApprovalRequestRequest;
use App\Http\Requests\Approvals\RejectApprovalRequestRequest;
use App\Models\Activity;
use App\Models\ApprovalRequest;
use App\Services\Approvals\ApprovalModuleRegistry;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalsController extends Controller
{
    use InteractsWithDataTable;

    public function index(Request $request, ApprovalModuleRegistry $registry, ApprovalWorkflowService $workflow): View
    {
        $query = ApprovalRequest::query()->with(['maker', 'stages.roles']);
        $search = $this->tableSearch($request);

        if ($search !== null) {
            $query->where(function ($query) use ($search): void {
                $query->whereLike('module_key', '%'.$search.'%')
                    ->orWhereLike('action', '%'.$search.'%')
                    ->orWhereHas('maker', fn ($makerQuery) => $makerQuery
                        ->whereLike('name', '%'.$search.'%')
                        ->orWhereLike('email', '%'.$search.'%'));
            });
        }

        if (in_array($request->query('status'), ['Pending', 'Approved', 'Rejected', 'Cancelled'], true)) {
            $query->where('status', $request->query('status'));
        }

        if (in_array($request->query('module'), $registry->keys(), true)) {
            $query->where('module_key', $request->query('module'));
        }

        if (in_array($request->query('action'), ['create', 'update', 'delete'], true)) {
            $query->where('action', $request->query('action'));
        }

        if ($request->query('scope') === 'mine') {
            $query->where('maker_user_id', $request->user()->id);
        }

        if ($request->query('scope') === 'awaiting') {
            $query->where('status', ApprovalRequest::STATUS_PENDING)
                ->where('maker_user_id', '!=', $request->user()->id)
                ->whereHas('stages', function ($stageQuery) use ($request): void {
                    $stageQuery
                        ->whereColumn('approval_request_stages.stage_number', 'approval_requests.current_stage')
                        ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('role_id', $request->user()->role_id));
                });
        }

        $requests = $query->latest('submitted_at')->paginate($this->tablePerPage($request))->withQueryString();

        return view('approvals.index', [
            'requests' => $requests,
            'modules' => $registry->all(),
            'workflow' => $workflow,
        ]);
    }

    public function show(
        ApprovalRequest $approvalRequest,
        ApprovalModuleRegistry $registry,
        ApprovalWorkflowService $workflow,
        Request $request,
    ): View {
        $approvalRequest->load(['maker', 'stages.roles']);
        $module = $registry->get($approvalRequest->module_key);
        $target = $module->target($approvalRequest->target_id);
        $before = $approvalRequest->before_payload;
        $proposed = $approvalRequest->proposed_payload;

        return view('approvals.show', [
            'request' => $approvalRequest,
            'module' => $module,
            'target' => $target,
            'preview' => $module->preview($approvalRequest->action, $target, $proposed),
            'before' => $before,
            'proposed' => $proposed,
            'changes' => $this->changes($before, $proposed),
            'canDecide' => $workflow->canDecide($approvalRequest, $request->user()),
            'canCancel' => $workflow->canCancel($approvalRequest, $request->user()),
            'timeline' => Activity::query()
                ->where('subject_type', $approvalRequest->getMorphClass())
                ->where('subject_id', $approvalRequest->getKey())
                ->with('causer')
                ->latest()
                ->get(),
        ]);
    }

    public function approve(
        ApproveApprovalRequestRequest $request,
        ApprovalRequest $approvalRequest,
        ApprovalWorkflowService $workflow,
    ): RedirectResponse {
        $workflow->approve($approvalRequest, $request->user(), $request->validated('comment'));

        return redirect()
            ->route('approvals.show', $approvalRequest)
            ->with('status', __('Request approved.'));
    }

    public function reject(
        RejectApprovalRequestRequest $request,
        ApprovalRequest $approvalRequest,
        ApprovalWorkflowService $workflow,
    ): RedirectResponse {
        $workflow->reject($approvalRequest, $request->user(), $request->validated('comment'));

        return redirect()
            ->route('approvals.show', $approvalRequest)
            ->with('status', __('Request rejected.'));
    }

    public function cancel(
        CancelApprovalRequestRequest $request,
        ApprovalRequest $approvalRequest,
        ApprovalWorkflowService $workflow,
    ): RedirectResponse {
        $workflow->cancel($approvalRequest, $request->user(), $request->validated('comment'));

        return redirect()
            ->route('approvals.show', $approvalRequest)
            ->with('status', __('Request cancelled.'));
    }

    protected function changes(?array $before, array $proposed): array
    {
        $changes = [];

        foreach (array_unique([...array_keys($before ?? []), ...array_keys($proposed)]) as $key) {
            if ($key === 'updated_at') {
                continue;
            }

            $old = $before[$key] ?? null;
            $new = $proposed[$key] ?? null;

            if ($key === 'password') {
                $old = $old === null ? null : '••••••';
                $new = $new === null ? null : '••••••';
            }

            if ($old !== $new || $before === null) {
                $changes[$key] = ['old' => $old, 'new' => $new];
            }
        }

        return $changes;
    }
}
