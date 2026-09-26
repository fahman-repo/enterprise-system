<?php

namespace Database\Seeders;

use App\Models\ApprovalMatrix;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ApprovalMatrixSeeder extends Seeder
{
    /**
     * Configure the maker-checker matrices for every workflow module.
     * Uses the same service the UI uses, so versions, snapshots and the
     * configuration_replaced audit entries are produced the real way.
     * A module whose matrix already has stages is left untouched, which
     * keeps re-runs idempotent and preserves operator edits.
     */
    public function run(): void
    {
        $roles = Role::query()->get(['id', 'slug'])->keyBy('slug');
        $actor = User::query()
            ->whereHas('role', fn ($query) => $query->where('slug', 'admin'))
            ->orderBy('id')
            ->first();

        if ($actor === null) {
            return;
        }

        $workflow = app(ApprovalWorkflowService::class);

        foreach (self::definitions() as $moduleKey => $definition) {
            $matrix = ApprovalMatrix::query()->where('module_key', $moduleKey)->first();

            if ($matrix !== null && $matrix->stages()->exists()) {
                continue;
            }

            $workflow->replaceConfiguration($moduleKey, [
                'is_active' => true,
                'maker_roles' => $this->roleIds($roles, $definition['makers']),
                'stages' => collect($definition['stages'])
                    ->map(fn (array $stage, int $index): array => [
                        'stage_number' => $index + 1,
                        'name' => $stage['name'],
                        'role_ids' => $this->roleIds($roles, $stage['roles']),
                    ])
                    ->values()
                    ->all(),
            ], $actor);
        }
    }

    /**
     * Module matrices keyed by module key. Every chain is decidable: the
     * second (or only) stage is held by a role the makers never hold
     * themselves, so no seeded request can dead-end.
     *
     * @return array<string, array{makers: list<string>, stages: list<array{name: string, roles: list<string>}>}>
     */
    public static function definitions(): array
    {
        return [
            'users' => [
                'makers' => ['it-support'],
                'stages' => [
                    ['name' => 'HR Review', 'roles' => ['hr-manager']],
                    ['name' => 'Executive Approval', 'roles' => ['admin']],
                ],
            ],
            'employees' => [
                'makers' => ['hr-staff'],
                'stages' => [
                    ['name' => 'HR Manager Review', 'roles' => ['hr-manager']],
                    ['name' => 'Finance Approval', 'roles' => ['finance-approver']],
                ],
            ],
            'divisions' => [
                'makers' => ['hr-staff'],
                'stages' => [
                    ['name' => 'Executive Approval', 'roles' => ['admin']],
                ],
            ],
            'departments' => [
                'makers' => ['hr-staff'],
                'stages' => [
                    ['name' => 'HR Manager Review', 'roles' => ['hr-manager']],
                ],
            ],
            'org-units' => [
                'makers' => ['hr-staff'],
                'stages' => [
                    ['name' => 'HR Manager Review', 'roles' => ['hr-manager']],
                ],
            ],
            'positions' => [
                'makers' => ['hr-staff'],
                'stages' => [
                    ['name' => 'HR Manager Review', 'roles' => ['hr-manager']],
                ],
            ],
            'grades' => [
                'makers' => ['hr-staff'],
                'stages' => [
                    ['name' => 'HR Manager Review', 'roles' => ['hr-manager']],
                ],
            ],
            'development-programs' => [
                'makers' => ['hr-staff', 'it-support'],
                'stages' => [
                    ['name' => 'HR Manager Review', 'roles' => ['hr-manager']],
                ],
            ],
            'benefits' => [
                'makers' => ['hr-staff'],
                'stages' => [
                    ['name' => 'HR Manager Review', 'roles' => ['hr-manager']],
                    ['name' => 'Finance Approval', 'roles' => ['finance-approver']],
                ],
            ],
            'benefit-enrollments' => [
                'makers' => ['hr-staff', 'commercial-staff'],
                'stages' => [
                    ['name' => 'Line Manager Review', 'roles' => ['branch-manager']],
                    ['name' => 'Finance Approval', 'roles' => ['finance-approver']],
                ],
            ],
            'benefit-claims' => [
                'makers' => ['hr-staff', 'commercial-staff', 'employee-self'],
                'stages' => [
                    ['name' => 'Line Manager Review', 'roles' => ['branch-manager']],
                    ['name' => 'Finance Approval', 'roles' => ['finance-approver']],
                ],
            ],
            'entities' => [
                'makers' => ['commercial-staff', 'hr-staff'],
                'stages' => [
                    ['name' => 'Finance Review', 'roles' => ['finance-approver']],
                    ['name' => 'Executive Approval', 'roles' => ['admin']],
                ],
            ],
            'products' => [
                'makers' => ['ops-manager'],
                'stages' => [
                    ['name' => 'Executive Approval', 'roles' => ['admin']],
                ],
            ],
            'categories' => [
                'makers' => ['ops-manager'],
                'stages' => [
                    ['name' => 'Executive Approval', 'roles' => ['admin']],
                ],
            ],
            'brands' => [
                'makers' => ['ops-manager'],
                'stages' => [
                    ['name' => 'Executive Approval', 'roles' => ['admin']],
                ],
            ],
            'units' => [
                'makers' => ['ops-manager'],
                'stages' => [
                    ['name' => 'Executive Approval', 'roles' => ['admin']],
                ],
            ],
            'sites' => [
                'makers' => ['ops-manager'],
                'stages' => [
                    ['name' => 'Executive Approval', 'roles' => ['admin']],
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, Role>  $roles
     * @param  list<string>  $slugs
     * @return list<int>
     */
    protected function roleIds(Collection $roles, array $slugs): array
    {
        return collect($slugs)
            ->map(fn (string $slug): ?int => $roles->get($slug)?->id)
            ->filter()
            ->values()
            ->all();
    }
}
