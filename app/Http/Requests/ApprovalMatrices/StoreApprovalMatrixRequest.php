<?php

namespace App\Http\Requests\ApprovalMatrices;

use App\Models\ApprovalMatrix;
use App\Services\Approvals\ApprovalModuleRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreApprovalMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module_key' => ['required', 'string', Rule::in(app(ApprovalModuleRegistry::class)->keys())],
            'is_active' => ['nullable', 'boolean'],
            'mode' => ['required', 'string', Rule::in(ApprovalMatrix::MODES)],
            'maker_roles' => ['required', 'array', 'min:1', 'distinct'],
            'maker_roles.*' => ['integer', Rule::exists('roles', 'id')->where('is_active', true)],
            'stages' => ['required', 'array', 'min:1', 'max:10'],
            'stages.*.stage_number' => ['required', 'integer', 'distinct', 'min:1', 'max:10'],
            'stages.*.name' => ['required', 'string', 'max:100'],
            'stages.*.role_ids' => ['required', 'array', 'min:1', 'distinct'],
            'stages.*.role_ids.*' => ['integer', Rule::exists('roles', 'id')->where('is_active', true)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $stages = collect($this->input('stages', []));
                $stageNumbers = $stages->pluck('stage_number')->map(fn ($value): int => (int) $value)->sort()->values()->all();

                if ($stageNumbers !== range(1, $stages->count())) {
                    $validator->errors()->add('stages', __('Stage numbers must be sequential from 1 without gaps.'));
                }

                $makerRoles = collect($this->input('maker_roles', []))->map(fn ($value): int => (int) $value);
                $approverRoles = $stages
                    ->flatMap(fn ($stage): array => is_array($stage) ? ($stage['role_ids'] ?? []) : [])
                    ->map(fn ($value): int => (int) $value);

                if ($makerRoles->intersect($approverRoles)->isNotEmpty()) {
                    $validator->errors()->add('stages', __('Maker roles cannot also be approver roles.'));
                }
            },
        ];
    }
}
