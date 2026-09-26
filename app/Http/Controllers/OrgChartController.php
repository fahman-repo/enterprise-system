<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Employee;
use App\Models\Site;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrgChartController extends Controller
{
    /**
     * Display the organization chart page.
     */
    public function index(): View
    {
        return view('org-chart.index', [
            'sites' => Site::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Provide the chart nodes for the requested mode and filter.
     */
    public function data(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mode' => ['nullable', Rule::in(['structure', 'reporting'])],
            'site_id' => ['nullable', Rule::exists('sites', 'id')],
        ]);

        $mode = $data['mode'] ?? 'structure';
        $siteId = $data['site_id'] ?? null;

        $employees = Employee::query()
            ->where('is_active', true)
            ->when($siteId, fn ($query) => $query->where('site_id', $siteId))
            ->with(['position:id,name', 'grade:id,name'])
            ->get(['id', 'name', 'manager_id', 'division_id', 'department_id', 'org_unit_id', 'position_id', 'grade_id', 'photo_path']);

        if ($mode === 'reporting') {
            return response()->json(['nodes' => $this->reportingNodes($employees)]);
        }

        $divisions = Division::query()
            ->where('is_active', true)
            ->with([
                'departments' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
                'departments.orgUnits' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
            ])
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return response()->json(['nodes' => $this->structureNodes($employees, $divisions)]);
    }

    /**
     * Build the org-unit tree: company â†’ divisions â†’ departments â†’
     * org units, with employees as leaves at their deepest placement.
     *
     * @param  Collection<int, Employee>  $employees
     * @param  Collection<int, Division>  $divisions
     * @return list<array{id: string, parentId: ?string, type: string, name: string, code: ?string, subtitle: ?string, avatarUrl: ?string, profileUrl: ?string, inactive: bool}>
     */
    private function structureNodes(Collection $employees, Collection $divisions): array
    {
        $nodes = [$this->rootNode($employees->count())];

        foreach ($divisions as $division) {
            $nodes[] = $this->entityNode(
                id: 'div-'.$division->id,
                parentId: 'company',
                type: 'division',
                name: $division->name,
                code: $division->code,
                subtitle: __(':departments departments Â· :employees employees', [
                    'departments' => $division->departments->count(),
                    'employees' => $employees->where('division_id', $division->id)->count(),
                ]),
            );

            foreach ($division->departments as $department) {
                $nodes[] = $this->entityNode(
                    id: 'dep-'.$department->id,
                    parentId: 'div-'.$division->id,
                    type: 'department',
                    name: $department->name,
                    code: $department->code,
                    subtitle: __(':units org units Â· :employees employees', [
                        'units' => $department->orgUnits->count(),
                        'employees' => $employees->where('department_id', $department->id)->count(),
                    ]),
                );

                foreach ($department->orgUnits as $orgUnit) {
                    $nodes[] = $this->entityNode(
                        id: 'unit-'.$orgUnit->id,
                        parentId: 'dep-'.$department->id,
                        type: 'unit',
                        name: $orgUnit->name,
                        code: $orgUnit->code,
                        subtitle: __(':count employees', ['count' => $employees->where('org_unit_id', $orgUnit->id)->count()]),
                    );

                    foreach ($employees->where('org_unit_id', $orgUnit->id)->sortBy('name') as $employee) {
                        $nodes[] = $this->employeeNode($employee, 'unit-'.$orgUnit->id);
                    }
                }

                foreach ($employees->where('department_id', $department->id)->whereNull('org_unit_id')->sortBy('name') as $employee) {
                    $nodes[] = $this->employeeNode($employee, 'dep-'.$department->id);
                }
            }

            foreach ($employees->where('division_id', $division->id)->whereNull('department_id')->sortBy('name') as $employee) {
                $nodes[] = $this->employeeNode($employee, 'div-'.$division->id);
            }
        }

        foreach ($employees->whereNull('division_id')->sortBy('name') as $employee) {
            $nodes[] = $this->employeeNode($employee, 'company');
        }

        return $this->withGuaranteedRoot($nodes);
    }

    /**
     * Build the reporting tree: employees under their managers,
     * including manager ancestors regardless of the location filter.
     *
     * @param  Collection<int, Employee>  $employees
     * @return list<array{id: string, parentId: ?string, type: string, name: string, code: ?string, subtitle: ?string, avatarUrl: ?string, profileUrl: ?string, inactive: bool}>
     */
    private function reportingNodes(Collection $employees): array
    {
        $byId = $employees->keyBy('id')->all();
        $visited = array_fill_keys(array_keys($byId), true);
        $pending = $employees->pluck('manager_id')->filter()->unique()->values()->all();

        while ($pending !== []) {
            $batch = [];
            foreach ($pending as $managerId) {
                if (! isset($visited[$managerId])) {
                    $visited[$managerId] = true;
                    $batch[] = $managerId;
                }
            }

            if ($batch === []) {
                break;
            }

            $pending = [];

            $managers = Employee::query()
                ->with(['position:id,name', 'grade:id,name'])
                ->findMany($batch, ['id', 'name', 'manager_id', 'division_id', 'department_id', 'org_unit_id', 'position_id', 'grade_id', 'photo_path']);

            foreach ($managers as $manager) {
                $byId[$manager->id] = $manager;

                if ($manager->manager_id !== null) {
                    $pending[] = $manager->manager_id;
                }
            }
        }

        $nodes = [$this->rootNode(count($byId))];

        foreach (collect($byId)->sortBy('name') as $employee) {
            $nodes[] = $this->employeeNode(
                $employee,
                $employee->manager_id !== null ? 'emp-'.$employee->manager_id : 'company',
            );
        }

        return $this->withGuaranteedRoot($nodes);
    }

    /**
     * @return array{id: string, parentId: null, type: string, name: string, code: null, subtitle: string, avatarUrl: null, profileUrl: null, inactive: bool}
     */
    private function rootNode(int $total): array
    {
        return [
            'id' => 'company',
            'parentId' => null,
            'type' => 'root',
            'name' => (string) config('app.name'),
            'code' => null,
            'subtitle' => __(':count employees', ['count' => $total]),
            'avatarUrl' => null,
            'profileUrl' => null,
            'inactive' => false,
        ];
    }

    /**
     * @return array{id: string, parentId: ?string, type: string, name: string, code: ?string, subtitle: ?string, avatarUrl: null, profileUrl: null, inactive: bool}
     */
    private function entityNode(string $id, string $parentId, string $type, string $name, ?string $code, ?string $subtitle): array
    {
        return [
            'id' => $id,
            'parentId' => $parentId,
            'type' => $type,
            'name' => $name,
            'code' => $code,
            'subtitle' => $subtitle,
            'avatarUrl' => null,
            'profileUrl' => null,
            'inactive' => false,
        ];
    }

    /**
     * @return array{id: string, parentId: ?string, type: string, name: string, code: null, subtitle: ?string, avatarUrl: ?string, profileUrl: ?string, inactive: bool}
     */
    private function employeeNode(Employee $employee, string $parentId): array
    {
        $subtitle = collect([$employee->position?->name, $employee->grade?->name])
            ->filter()
            ->join(' Â· ');

        return [
            'id' => 'emp-'.$employee->id,
            'parentId' => $parentId,
            'type' => 'employee',
            'name' => $employee->name,
            'code' => null,
            'subtitle' => $subtitle === '' ? null : $subtitle,
            'avatarUrl' => $employee->photoUrl(),
            'profileUrl' => route('employees.show', $employee),
            'inactive' => false,
        ];
    }

    /**
     * Reattach any orphan node to the synthetic root so the graph
     * always has a single root for d3 stratify.
     *
     * @param  list<array{id: string, parentId: ?string, type: string, name: string, code: ?string, subtitle: ?string, avatarUrl: ?string, profileUrl: ?string, inactive: bool}>  $nodes
     * @return list<array{id: string, parentId: ?string, type: string, name: string, code: ?string, subtitle: ?string, avatarUrl: ?string, profileUrl: ?string, inactive: bool}>
     */
    private function withGuaranteedRoot(array $nodes): array
    {
        $ids = array_column($nodes, 'id');

        foreach ($nodes as $index => $node) {
            if ($node['parentId'] !== null && ! in_array($node['parentId'], $ids, true)) {
                $nodes[$index]['parentId'] = 'company';
            }
        }

        return $nodes;
    }
}
