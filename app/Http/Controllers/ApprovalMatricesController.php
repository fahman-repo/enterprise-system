<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApprovalMatrices\StoreApprovalMatrixRequest;
use App\Models\ApprovalMatrix;
use App\Models\Role;
use App\Services\Approvals\ApprovalModuleRegistry;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApprovalMatricesController extends Controller
{
    public function index(ApprovalModuleRegistry $registry): View
    {
        $matrices = ApprovalMatrix::query()
            ->with(['makerRoles', 'stages.roles'])
            ->get()
            ->keyBy('module_key');

        return view('approval-matrices.index', [
            'modules' => $registry->all(),
            'matrices' => $matrices,
        ]);
    }

    public function edit(string $moduleKey, ApprovalModuleRegistry $registry): View
    {
        abort_unless(in_array($moduleKey, $registry->keys(), true), 404);
        $registry->get($moduleKey);
        $matrix = ApprovalMatrix::query()
            ->with(['makerRoles', 'stages.roles'])
            ->where('module_key', $moduleKey)
            ->first();
        $stageRows = old('stages');

        if (! is_array($stageRows) || $stageRows === []) {
            $stageRows = $matrix?->stages->map(fn ($stage): array => [
                'stage_number' => $stage->stage_number,
                'name' => $stage->name,
                'role_ids' => $stage->roles->pluck('id')->all(),
            ])->all() ?? [['stage_number' => 1, 'name' => '', 'role_ids' => []]];
        }

        return view('approval-matrices.edit', [
            'moduleKey' => $moduleKey,
            'module' => $registry->get($moduleKey),
            'matrix' => $matrix,
            'roles' => Role::query()->where('is_active', true)->orderBy('name')->get(),
            'stageRows' => collect($stageRows)->map(fn (array $stage): array => [
                'stage_number' => (int) ($stage['stage_number'] ?? 1),
                'name' => (string) ($stage['name'] ?? ''),
                'role_ids' => collect($stage['role_ids'] ?? [])->map(fn ($roleId): int => (int) $roleId)->all(),
            ])->values()->all(),
            'makerRoles' => old('maker_roles', $matrix?->makerRoles->pluck('id')->all() ?? []),
        ]);
    }

    public function update(
        StoreApprovalMatrixRequest $request,
        string $moduleKey,
        ApprovalWorkflowService $workflow,
        ApprovalModuleRegistry $registry,
    ): RedirectResponse {
        abort_unless(in_array($moduleKey, $registry->keys(), true), 404);
        $workflow->replaceConfiguration($moduleKey, $request->validated(), $request->user());

        return redirect()
            ->route('approval-matrices.index')
            ->with('status', __('Approval matrix saved.'));
    }
}
