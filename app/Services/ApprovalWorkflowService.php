<?php

namespace App\Services;

use App\Models\ApprovalMatrix;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStage;
use App\Models\ApprovalRequestStageRole;
use App\Models\User;
use App\Services\Approvals\ApprovalModuleRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalWorkflowService
{
    public function __construct(public ApprovalModuleRegistry $modules) {}

    public function submit(
        User $maker,
        string $moduleKey,
        string $action,
        ?int $targetId,
        array $payload,
    ): ApprovalRequest {
        return DB::transaction(function () use ($maker, $moduleKey, $action, $targetId, $payload): ApprovalRequest {
            $matrix = ApprovalMatrix::query()
                ->where('module_key', $moduleKey)
                ->lockForUpdate()
                ->first();

            if (! $matrix?->is_active) {
                throw ValidationException::withMessages([
                    'approval' => __('Approval is not configured for this module.'),
                ]);
            }

            $module = $this->modules->get($moduleKey);
            $this->assertMakerEligible($maker, $matrix);
            $target = $module->target($targetId, true);

            if ($action === ApprovalRequest::ACTION_CREATE && $targetId !== null) {
                throw ValidationException::withMessages(['approval' => __('A create request cannot target an existing record.')]);
            }

            if ($action !== ApprovalRequest::ACTION_CREATE && $target === null) {
                throw ValidationException::withMessages(['approval' => __('The target record no longer exists.')]);
            }

            $module->validateSubmission($action, $target, $payload);

            if ($targetId !== null && ApprovalRequest::query()
                ->where('module_key', $moduleKey)
                ->where('action', $action)
                ->where('target_id', $targetId)
                ->where('status', ApprovalRequest::STATUS_PENDING)
                ->lockForUpdate()
                ->exists()) {
                throw ValidationException::withMessages([
                    'approval' => __('A pending request already exists for this change.'),
                ]);
            }

            $request = ApprovalRequest::create([
                'module_key' => $moduleKey,
                'action' => $action,
                'target_id' => $targetId,
                'proposed_payload' => $payload,
                'before_payload' => $module->beforePayload($target),
                'maker_user_id' => $maker->id,
                'maker_snapshot' => $this->userSnapshot($maker),
                'status' => ApprovalRequest::STATUS_PENDING,
                'current_stage' => 1,
                'matrix_configuration_version' => $matrix->configuration_version,
                'approval_mode' => $matrix->mode,
                'submitted_at' => now(),
            ]);

            foreach ($matrix->stages()->with('roles')->get() as $matrixStage) {
                $requestStage = $request->stages()->create([
                    'stage_number' => $matrixStage->stage_number,
                    'name' => $matrixStage->name,
                    'status' => ApprovalRequestStage::STATUS_PENDING,
                ]);

                foreach ($matrixStage->roles as $role) {
                    ApprovalRequestStageRole::create([
                        'approval_request_stage_id' => $requestStage->id,
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'role_slug' => $role->slug,
                    ]);
                }
            }

            activity('approval')
                ->event('submitted')
                ->performedOn($request)
                ->causedBy($maker)
                ->withProperties([
                    'module' => $moduleKey,
                    'action' => $action,
                    'target_id' => $targetId,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Approval request submitted.');

            return $request;
        });
    }

    /**
     * Whether writes for the module must go through approval instead of
     * being applied directly. Opt-in per module via the approval matrix.
     */
    public function isApprovalActive(string $moduleKey): bool
    {
        return ApprovalMatrix::query()
            ->where('module_key', $moduleKey)
            ->where('is_active', true)
            ->exists();
    }

    public function replaceConfiguration(string $moduleKey, array $data, User $actor): ApprovalMatrix
    {
        return DB::transaction(function () use ($moduleKey, $data, $actor): ApprovalMatrix {
            $this->modules->get($moduleKey);
            $matrix = ApprovalMatrix::query()->where('module_key', $moduleKey)->lockForUpdate()->first();

            if (! $matrix) {
                $matrix = new ApprovalMatrix(['module_key' => $moduleKey]);
            }

            $before = $this->matrixSnapshot($matrix);
            $matrix->is_active = (bool) ($data['is_active'] ?? false);
            $matrix->mode = $data['mode'] ?? $matrix->mode ?? ApprovalMatrix::MODE_SEQUENTIAL;
            $matrix->configuration_version = $matrix->exists ? $matrix->configuration_version + 1 : 1;
            $matrix->save();

            $matrix->makerRoles()->sync($data['maker_roles']);
            $matrix->stages()->delete();

            foreach ($data['stages'] as $stageData) {
                $stage = $matrix->stages()->create([
                    'stage_number' => $stageData['stage_number'],
                    'name' => $stageData['name'],
                ]);

                $stage->roles()->sync($stageData['role_ids']);
            }

            activity('approval-matrix')
                ->event('configuration_replaced')
                ->performedOn($matrix)
                ->causedBy($actor)
                ->withProperties([
                    'before' => $before,
                    'after' => $this->matrixSnapshot($matrix),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Approval matrix configuration replaced.');

            return $matrix;
        });
    }

    public function approve(ApprovalRequest $request, User $actor, ?string $comment = null): ApprovalRequest
    {
        return $this->decide($request, $actor, true, $comment);
    }

    public function reject(ApprovalRequest $request, User $actor, string $comment): ApprovalRequest
    {
        return $this->decide($request, $actor, false, $comment);
    }

    public function cancel(ApprovalRequest $request, User $actor, ?string $comment = null): ApprovalRequest
    {
        return DB::transaction(function () use ($request, $actor, $comment): ApprovalRequest {
            $lockedRequest = ApprovalRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $lockedRequest->isPending()) {
                throw ValidationException::withMessages(['approval' => __('Only pending requests can be cancelled.')]);
            }

            if ($lockedRequest->maker_user_id !== $actor->id) {
                throw ValidationException::withMessages(['approval' => __('Only the maker can cancel this request.')]);
            }

            $lockedRequest->update([
                'status' => ApprovalRequest::STATUS_CANCELLED,
                'resolution_comment' => $comment,
                'cancelled_at' => now(),
                'completed_at' => now(),
            ]);

            $this->modules->get($lockedRequest->module_key)->discard($lockedRequest);

            activity('approval')
                ->event('cancelled')
                ->performedOn($lockedRequest)
                ->causedBy($actor)
                ->withProperties([
                    'comment' => $comment,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Approval request cancelled.');

            return $lockedRequest;
        });
    }

    public function canDecide(ApprovalRequest $request, User $actor): bool
    {
        if ($request->isParallel()) {
            return $this->canDecideParallel($request, $actor);
        }

        $stage = $request->stages->firstWhere('stage_number', $request->current_stage);

        if (! $request->isPending() || ! $stage || $stage->decided_by_user_id !== null) {
            return false;
        }

        if ($request->maker_user_id === $actor->id || ! $actor->is_active || ! $actor->role?->is_active) {
            return false;
        }

        if ($request->stages->contains(fn ($stage): bool => $stage->decided_by_user_id === $actor->id)) {
            return false;
        }

        return $stage->roles->contains(fn ($role): bool => $role->role_id === $actor->role_id);
    }

    protected function canDecideParallel(ApprovalRequest $request, User $actor): bool
    {
        if (! $request->isPending()) {
            return false;
        }

        if ($request->maker_user_id === $actor->id || ! $actor->is_active || ! $actor->role?->is_active) {
            return false;
        }

        if ($request->stages->contains(fn ($stage): bool => $stage->decided_by_user_id === $actor->id)) {
            return false;
        }

        return $request->stages->contains(fn ($stage): bool => $stage->status === ApprovalRequestStage::STATUS_PENDING
            && $stage->roles->contains(fn ($role): bool => $role->role_id === $actor->role_id));
    }

    public function canCancel(ApprovalRequest $request, User $actor): bool
    {
        return $request->isPending() && $request->maker_user_id === $actor->id;
    }

    protected function decide(ApprovalRequest $request, User $actor, bool $approved, ?string $comment): ApprovalRequest
    {
        return DB::transaction(function () use ($request, $actor, $approved, $comment): ApprovalRequest {
            $lockedRequest = ApprovalRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $lockedRequest->isPending()) {
                throw ValidationException::withMessages(['approval' => __('This request has already been completed.')]);
            }

            if ($lockedRequest->isParallel()) {
                return $this->decideParallel($lockedRequest, $actor, $approved, $comment);
            }

            return $this->decideSequential($lockedRequest, $actor, $approved, $comment);
        });
    }

    protected function decideSequential(ApprovalRequest $lockedRequest, User $actor, bool $approved, ?string $comment): ApprovalRequest
    {
        $stage = ApprovalRequestStage::query()
            ->where('approval_request_id', $lockedRequest->id)
            ->where('stage_number', $lockedRequest->current_stage)
            ->lockForUpdate()
            ->firstOrFail();

        if (! $this->canDecide($lockedRequest->load(['stages.roles']), $actor)) {
            throw ValidationException::withMessages(['decision' => __('You are not eligible to decide this request.')]);
        }

        if (! $approved && blank($comment)) {
            throw ValidationException::withMessages(['comment' => __('A rejection comment is required.')]);
        }

        $module = $this->modules->get($lockedRequest->module_key);
        $target = $module->target($lockedRequest->target_id, true);
        $isFinal = $stage->stage_number === $lockedRequest->stages()->count();

        if ($approved && $isFinal) {
            $module->assertCanApply($lockedRequest, $target);
            $target = $module->apply($lockedRequest, $target);

            if ($lockedRequest->target_id === null && $target) {
                $lockedRequest->target_id = $target->getKey();
            }
        }

        $stage->update([
            'status' => $approved ? ApprovalRequestStage::STATUS_APPROVED : ApprovalRequestStage::STATUS_REJECTED,
            'decided_by_user_id' => $actor->id,
            'decided_by_snapshot' => $this->userSnapshot($actor),
            'comment' => $comment,
            'decided_at' => now(),
        ]);

        if (! $approved) {
            $lockedRequest->update([
                'status' => ApprovalRequest::STATUS_REJECTED,
                'resolution_comment' => $comment,
                'rejected_at' => now(),
                'completed_at' => now(),
            ]);

            $module->discard($lockedRequest);
        } elseif ($isFinal) {
            $lockedRequest->update([
                'status' => ApprovalRequest::STATUS_APPROVED,
                'approved_at' => now(),
                'completed_at' => now(),
            ]);
        } else {
            $lockedRequest->update(['current_stage' => $stage->stage_number + 1]);
        }

        activity('approval')
            ->event($approved ? ($isFinal ? 'approved' : 'stage_approved') : 'rejected')
            ->performedOn($lockedRequest)
            ->causedBy($actor)
            ->withProperties([
                'stage_number' => $stage->stage_number,
                'comment' => $comment,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Approval request decision recorded.');

        return $lockedRequest;
    }

    protected function decideParallel(ApprovalRequest $lockedRequest, User $actor, bool $approved, ?string $comment): ApprovalRequest
    {
        $lockedStages = ApprovalRequestStage::query()
            ->where('approval_request_id', $lockedRequest->id)
            ->lockForUpdate()
            ->orderBy('stage_number')
            ->with('roles')
            ->get();
        $lockedRequest->setRelation('stages', $lockedStages);

        if (! $this->canDecide($lockedRequest, $actor)) {
            throw ValidationException::withMessages(['decision' => __('You are not eligible to decide this request.')]);
        }

        if (! $approved && blank($comment)) {
            throw ValidationException::withMessages(['comment' => __('A rejection comment is required.')]);
        }

        $decidingStage = $lockedStages
            ->where('status', ApprovalRequestStage::STATUS_PENDING)
            ->filter(fn (ApprovalRequestStage $stage): bool => $stage->roles->contains(fn ($role): bool => $role->role_id === $actor->role_id))
            ->sortBy('stage_number')
            ->first();

        if (! $decidingStage) {
            throw ValidationException::withMessages(['decision' => __('You are not eligible to decide this request.')]);
        }

        $module = $this->modules->get($lockedRequest->module_key);
        $target = $module->target($lockedRequest->target_id, true);

        if ($approved) {
            $module->assertCanApply($lockedRequest, $target);
            $target = $module->apply($lockedRequest, $target);

            if ($lockedRequest->target_id === null && $target) {
                $lockedRequest->target_id = $target->getKey();
            }
        }

        $decidingStage->update([
            'status' => $approved ? ApprovalRequestStage::STATUS_APPROVED : ApprovalRequestStage::STATUS_REJECTED,
            'decided_by_user_id' => $actor->id,
            'decided_by_snapshot' => $this->userSnapshot($actor),
            'comment' => $comment,
            'decided_at' => now(),
        ]);

        if (! $approved) {
            $lockedRequest->update([
                'status' => ApprovalRequest::STATUS_REJECTED,
                'resolution_comment' => $comment,
                'rejected_at' => now(),
                'completed_at' => now(),
            ]);

            $module->discard($lockedRequest);
        } else {
            $lockedRequest->update([
                'status' => ApprovalRequest::STATUS_APPROVED,
                'current_stage' => $decidingStage->stage_number,
                'approved_at' => now(),
                'completed_at' => now(),
            ]);

            ApprovalRequestStage::query()
                ->where('approval_request_id', $lockedRequest->id)
                ->whereKeyNot($decidingStage->id)
                ->where('status', ApprovalRequestStage::STATUS_PENDING)
                ->update(['status' => ApprovalRequestStage::STATUS_SKIPPED]);
        }

        activity('approval')
            ->event($approved ? 'approved' : 'rejected')
            ->performedOn($lockedRequest)
            ->causedBy($actor)
            ->withProperties([
                'stage_number' => $decidingStage->stage_number,
                'mode' => ApprovalRequest::MODE_PARALLEL,
                'comment' => $comment,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Approval request decision recorded.');

        return $lockedRequest;
    }

    protected function assertMakerEligible(User $maker, ApprovalMatrix $matrix): void
    {
        if (! $maker->is_active || ! $maker->role?->is_active) {
            throw ValidationException::withMessages(['approval' => __('Only active makers can submit requests.')]);
        }

        if (! $matrix->makerRoles->contains(fn ($role): bool => $role->id === $maker->role_id)) {
            throw ValidationException::withMessages(['approval' => __('Your role is not an eligible maker role.')]);
        }
    }

    protected function userSnapshot(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'slug' => $user->role->slug,
            ] : null,
        ];
    }

    protected function matrixSnapshot(ApprovalMatrix $matrix): array
    {
        $matrix->load(['makerRoles', 'stages.roles']);

        return [
            'module_key' => $matrix->module_key,
            'is_active' => $matrix->is_active,
            'configuration_version' => $matrix->configuration_version,
            'mode' => $matrix->mode,
            'maker_roles' => $matrix->makerRoles->pluck('id')->all(),
            'stages' => $matrix->stages->map(fn ($stage): array => [
                'stage_number' => $stage->stage_number,
                'name' => $stage->name,
                'role_ids' => $stage->roles->pluck('id')->all(),
            ])->all(),
        ];
    }
}
