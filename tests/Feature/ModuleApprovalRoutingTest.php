<?php

use App\Models\ApprovalRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Department;
use App\Models\Division;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Entity;
use App\Models\MaritalStatus;
use App\Models\Menu;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\Product;
use App\Models\Religion;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Model;

/**
 * Dataset of every non-grades module: menu slug, route prefix and approval key.
 *
 * @return array<string, array{string}>
 */
function approvalRoutingModuleKeys(): array
{
    return [
        'users' => ['users'],
        'roles' => ['roles'],
        'menus' => ['menus'],
        'entities' => ['entities'],
        'products' => ['products'],
        'categories' => ['categories'],
        'brands' => ['brands'],
        'units' => ['units'],
        'employees' => ['employees'],
        'divisions' => ['divisions'],
        'departments' => ['departments'],
        'org-units' => ['org-units'],
        'positions' => ['positions'],
        'employment-statuses' => ['employment-statuses'],
        'work-locations' => ['work-locations'],
        'religions' => ['religions'],
        'education-levels' => ['education-levels'],
        'marital-statuses' => ['marital-statuses'],
    ];
}

/**
 * Build a maker (authorized on the module menu) and, when requested, an
 * active one-stage policy for the module.
 */
function approvalRoutingScenario(string $key, bool $active = true): User
{
    $moduleMenu = Menu::factory()->create(['slug' => $key]);
    $approvalsMenu = Menu::factory()->create(['slug' => 'approvals']);

    $makerRole = Role::factory()->create();
    $makerRole->menus()->sync([$moduleMenu->id => [
        'can_view' => true,
        'can_create' => true,
        'can_update' => true,
        'can_delete' => true,
    ]]);
    $makerRole->menus()->syncWithoutDetaching([$approvalsMenu->id => [
        'can_view' => true,
        'can_delete' => true,
    ]]);

    app(PermissionService::class)->flush();

    $maker = User::factory()->create(['role_id' => $makerRole->id]);

    if ($active) {
        $approverRole = Role::factory()->create();
        $approverRole->menus()->syncWithoutDetaching([$approvalsMenu->id => [
            'can_view' => true,
            'can_update' => true,
        ]]);

        app(PermissionService::class)->flush();

        app(ApprovalWorkflowService::class)->replaceConfiguration($key, [
            'is_active' => true,
            'maker_roles' => [$makerRole->id],
            'stages' => [['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$approverRole->id]]],
        ], $maker);
    }

    return $maker;
}

/**
 * Per-module payloads mirroring the module CRUD tests (file uploads dropped),
 * plus the identifying attributes used to prove which side of the workflow
 * wrote the row.
 *
 * @return array{
 *     model: class-string<Model>,
 *     store_identify: array<string, mixed>,
 *     original_identify: array<string, mixed>,
 *     updated_identify: array<string, mixed>,
 *     row: Closure(): Model,
 *     store: Closure(): array<string, mixed>,
 *     update: Closure(Model): array<string, mixed>,
 * }
 */
function approvalRoutingFixture(string $key): array
{
    return match ($key) {
        'users' => [
            'model' => User::class,
            'store_identify' => ['email' => 'routing-new-user@example.com'],
            'original_identify' => ['email' => 'routing-original-user@example.com'],
            'updated_identify' => ['email' => 'routing-updated-user@example.com'],
            'row' => fn (): User => User::factory()->create([
                'email' => 'routing-original-user@example.com',
                'role_id' => Role::factory()->create()->id,
            ]),
            'store' => fn (): array => [
                'name' => 'Routing New User',
                'email' => 'routing-new-user@example.com',
                'password' => 'password123',
                'role_id' => Role::factory()->create()->id,
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'name' => 'Routing Updated User',
                'email' => 'routing-updated-user@example.com',
                'role_id' => Role::factory()->create()->id,
                'is_active' => '1',
            ],
        ],
        'roles' => [
            'model' => Role::class,
            'store_identify' => ['slug' => 'routing-new-role'],
            'original_identify' => ['slug' => 'routing-original-role'],
            'updated_identify' => ['slug' => 'routing-updated-role'],
            'row' => fn (): Role => Role::factory()->create([
                'name' => 'Routing Original Role',
                'slug' => 'routing-original-role',
            ]),
            'store' => fn (): array => [
                'name' => 'Routing New Role',
                'slug' => 'routing-new-role',
                'is_active' => '1',
                'permissions' => [],
            ],
            'update' => fn (Model $row): array => [
                'name' => 'Routing Updated Role',
                'slug' => 'routing-updated-role',
                'is_active' => '1',
                'permissions' => [],
            ],
        ],
        'menus' => [
            'model' => Menu::class,
            'store_identify' => ['slug' => 'routing-new-menu'],
            'original_identify' => ['slug' => 'routing-original-menu'],
            'updated_identify' => ['slug' => 'routing-updated-menu'],
            'row' => fn (): Menu => Menu::factory()->create([
                'name' => 'Routing Original Menu',
                'slug' => 'routing-original-menu',
            ]),
            'store' => fn (): array => [
                'name' => 'Routing New Menu',
                'slug' => 'routing-new-menu',
                'icon' => 'users',
                'route_name' => 'dashboard',
                'sort_order' => 5,
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'name' => 'Routing Updated Menu',
                'slug' => 'routing-updated-menu',
                'icon' => 'users',
                'route_name' => 'dashboard',
                'sort_order' => 5,
                'is_active' => '1',
            ],
        ],
        'entities' => [
            'model' => Entity::class,
            'store_identify' => ['code' => 'ENT-ROUTING-NEW'],
            'original_identify' => ['code' => 'ENT-ROUTING-ORIG'],
            'updated_identify' => ['code' => 'ENT-ROUTING-UPD'],
            'row' => fn (): Entity => Entity::factory()->create([
                'code' => 'ENT-ROUTING-ORIG',
                'name' => 'Routing Original Entity',
            ]),
            'store' => fn (): array => [
                'code' => 'ENT-ROUTING-NEW',
                'name' => 'Routing New Entity',
                'type' => 'company',
                'role' => 'vendor',
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'code' => 'ENT-ROUTING-UPD',
                'name' => 'Routing Updated Entity',
                'type' => 'company',
                'role' => 'customer',
                'is_active' => '1',
            ],
        ],
        'products' => [
            'model' => Product::class,
            'store_identify' => ['sku' => 'ROUTING-NEW-SKU'],
            'original_identify' => ['sku' => 'ROUTING-ORIG-SKU'],
            'updated_identify' => ['sku' => 'ROUTING-UPD-SKU'],
            'row' => fn (): Product => Product::factory()->create(['sku' => 'ROUTING-ORIG-SKU']),
            'store' => fn (): array => [
                'sku' => 'ROUTING-NEW-SKU',
                'name' => 'Routing New Product',
                'category_id' => Category::factory()->create()->id,
                'brand_id' => Brand::factory()->create()->id,
                'unit_id' => Unit::factory()->create()->id,
                'cost_price' => '10.50',
                'selling_price' => '15.00',
                'tax_rate' => '10',
                'track_stock' => '1',
                'stock_quantity' => '25',
                'reorder_level' => '5',
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'sku' => 'ROUTING-UPD-SKU',
                'name' => 'Routing Updated Product',
                'cost_price' => '11.00',
                'selling_price' => '16.00',
                'tax_rate' => '10',
                'track_stock' => '1',
                'stock_quantity' => '30',
                'reorder_level' => '5',
                'is_active' => '1',
            ],
        ],
        'categories' => [
            'model' => Category::class,
            'store_identify' => ['slug' => 'routing-new-category'],
            'original_identify' => ['slug' => 'routing-original-category'],
            'updated_identify' => ['slug' => 'routing-updated-category'],
            'row' => fn (): Category => Category::factory()->create([
                'name' => 'Routing Original Category',
                'slug' => 'routing-original-category',
            ]),
            'store' => fn (): array => [
                'name' => 'Routing New Category',
                'slug' => 'routing-new-category',
                'description' => 'Created by the routing test.',
                'sort_order' => '3',
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'name' => 'Routing Updated Category',
                'slug' => 'routing-updated-category',
                'sort_order' => '3',
                'is_active' => '1',
            ],
        ],
        'brands' => [
            'model' => Brand::class,
            'store_identify' => ['slug' => 'routing-new-brand'],
            'original_identify' => ['slug' => 'routing-original-brand'],
            'updated_identify' => ['slug' => 'routing-updated-brand'],
            'row' => fn (): Brand => Brand::factory()->create([
                'name' => 'Routing Original Brand',
                'slug' => 'routing-original-brand',
            ]),
            'store' => fn (): array => [
                'name' => 'Routing New Brand',
                'slug' => 'routing-new-brand',
                'description' => 'Created by the routing test.',
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'name' => 'Routing Updated Brand',
                'slug' => 'routing-updated-brand',
                'is_active' => '1',
            ],
        ],
        'units' => [
            'model' => Unit::class,
            'store_identify' => ['abbreviation' => 'routing-new-u'],
            'original_identify' => ['abbreviation' => 'routing-orig-u'],
            'updated_identify' => ['abbreviation' => 'routing-upd-u'],
            'row' => fn (): Unit => Unit::factory()->create([
                'name' => 'Routing Original Unit',
                'abbreviation' => 'routing-orig-u',
            ]),
            'store' => fn (): array => [
                'name' => 'Routing New Unit',
                'abbreviation' => 'routing-new-u',
                'allows_decimal' => '1',
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'name' => 'Routing Updated Unit',
                'abbreviation' => 'routing-upd-u',
                'is_active' => '1',
            ],
        ],
        'employees' => [
            'model' => Employee::class,
            'store_identify' => ['employee_number' => 'EMP-ROUTING-NEW'],
            'original_identify' => ['employee_number' => 'EMP-ROUTING-ORIG'],
            'updated_identify' => ['employee_number' => 'EMP-ROUTING-UPD'],
            'row' => function (): Employee {
                $division = Division::factory()->create();
                $department = Department::factory()->forDivision($division)->create();

                return Employee::factory()->forDepartment($department)->create([
                    'name' => 'Routing Original Employee',
                    'employee_number' => 'EMP-ROUTING-ORIG',
                    'position_id' => Position::factory()->forDepartment($department)->create()->id,
                    'employment_status_id' => EmploymentStatus::factory()->create()->id,
                ]);
            },
            'store' => function (): array {
                $division = Division::factory()->create();
                $department = Department::factory()->forDivision($division)->create();

                return [
                    'division_id' => $division->id,
                    'department_id' => $department->id,
                    'position_id' => Position::factory()->forDepartment($department)->create()->id,
                    'employment_status_id' => EmploymentStatus::factory()->create()->id,
                    'employee_number' => 'EMP-ROUTING-NEW',
                    'name' => 'Routing New Employee',
                    'gender' => 'female',
                    'join_date' => '2024-01-15',
                    'is_active' => '1',
                ];
            },
            'update' => fn (Model $row): array => [
                'division_id' => $row->division_id,
                'department_id' => $row->department_id,
                'position_id' => $row->position_id,
                'employment_status_id' => $row->employment_status_id,
                'employee_number' => 'EMP-ROUTING-UPD',
                'name' => 'Routing Updated Employee',
                'gender' => 'female',
                'join_date' => '2024-01-15',
                'is_active' => '1',
            ],
        ],
        'divisions' => [
            'model' => Division::class,
            'store_identify' => ['code' => 'DIV-ROUTING-NEW'],
            'original_identify' => ['code' => 'DIV-ROUTING-ORIG'],
            'updated_identify' => ['code' => 'DIV-ROUTING-UPD'],
            'row' => fn (): Division => Division::factory()->create([
                'code' => 'DIV-ROUTING-ORIG',
                'name' => 'Routing Original Division',
            ]),
            'store' => fn (): array => [
                'code' => 'DIV-ROUTING-NEW',
                'name' => 'Routing New Division',
                'description' => 'Created by the routing test.',
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'code' => 'DIV-ROUTING-UPD',
                'name' => 'Routing Updated Division',
                'is_active' => '1',
            ],
        ],
        'departments' => [
            'model' => Department::class,
            'store_identify' => ['code' => 'DEP-ROUTING-NEW'],
            'original_identify' => ['code' => 'DEP-ROUTING-ORIG'],
            'updated_identify' => ['code' => 'DEP-ROUTING-UPD'],
            'row' => fn (): Department => Department::factory()->create([
                'code' => 'DEP-ROUTING-ORIG',
                'name' => 'Routing Original Department',
            ]),
            'store' => fn (): array => [
                'division_id' => Division::factory()->create()->id,
                'code' => 'DEP-ROUTING-NEW',
                'name' => 'Routing New Department',
                'description' => 'Created by the routing test.',
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'division_id' => $row->division_id,
                'code' => 'DEP-ROUTING-UPD',
                'name' => 'Routing Updated Department',
                'is_active' => '1',
            ],
        ],
        'org-units' => [
            'model' => OrgUnit::class,
            'store_identify' => ['code' => 'ORG-ROUTING-NEW'],
            'original_identify' => ['code' => 'ORG-ROUTING-ORIG'],
            'updated_identify' => ['code' => 'ORG-ROUTING-UPD'],
            'row' => fn (): OrgUnit => OrgUnit::factory()->create([
                'code' => 'ORG-ROUTING-ORIG',
                'name' => 'Routing Original Org Unit',
            ]),
            'store' => fn (): array => [
                'department_id' => Department::factory()->create()->id,
                'code' => 'ORG-ROUTING-NEW',
                'name' => 'Routing New Org Unit',
                'description' => 'Created by the routing test.',
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'department_id' => $row->department_id,
                'code' => 'ORG-ROUTING-UPD',
                'name' => 'Routing Updated Org Unit',
                'is_active' => '1',
            ],
        ],
        'positions' => [
            'model' => Position::class,
            'store_identify' => ['code' => 'POS-ROUTING-NEW'],
            'original_identify' => ['code' => 'POS-ROUTING-ORIG'],
            'updated_identify' => ['code' => 'POS-ROUTING-UPD'],
            'row' => fn (): Position => Position::factory()->create([
                'code' => 'POS-ROUTING-ORIG',
                'name' => 'Routing Original Position',
            ]),
            'store' => fn (): array => [
                'department_id' => Department::factory()->create()->id,
                'code' => 'POS-ROUTING-NEW',
                'name' => 'Routing New Position',
                'description' => 'Created by the routing test.',
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'department_id' => null,
                'code' => 'POS-ROUTING-UPD',
                'name' => 'Routing Updated Position',
                'is_active' => '1',
            ],
        ],
        'employment-statuses' => [
            'model' => EmploymentStatus::class,
            'store_identify' => ['code' => 'ROUTING-NEW'],
            'original_identify' => ['code' => 'ROUTING-ORIG'],
            'updated_identify' => ['code' => 'ROUTING-UPD'],
            'row' => fn (): EmploymentStatus => EmploymentStatus::factory()->create([
                'code' => 'ROUTING-ORIG',
                'name' => 'Routing Original Status',
            ]),
            'store' => fn (): array => [
                'code' => 'ROUTING-NEW',
                'name' => 'Routing New Status',
                'sort_order' => 3,
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'code' => 'ROUTING-UPD',
                'name' => 'Routing Updated Status',
                'is_active' => '1',
            ],
        ],
        'work-locations' => [
            'model' => WorkLocation::class,
            'store_identify' => ['code' => 'LOC-ROUTING-NEW'],
            'original_identify' => ['code' => 'LOC-ROUTING-ORIG'],
            'updated_identify' => ['code' => 'LOC-ROUTING-UPD'],
            'row' => fn (): WorkLocation => WorkLocation::factory()->create([
                'code' => 'LOC-ROUTING-ORIG',
                'name' => 'Routing Original Office',
            ]),
            'store' => fn (): array => [
                'code' => 'LOC-ROUTING-NEW',
                'name' => 'Routing New Office',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'code' => 'LOC-ROUTING-UPD',
                'name' => 'Routing Updated Office',
                'is_active' => '1',
            ],
        ],
        'religions' => [
            'model' => Religion::class,
            'store_identify' => ['name' => 'Routing New Religion'],
            'original_identify' => ['name' => 'Routing Original Religion'],
            'updated_identify' => ['name' => 'Routing Updated Religion'],
            'row' => fn (): Religion => Religion::factory()->create(['name' => 'Routing Original Religion']),
            'store' => fn (): array => [
                'name' => 'Routing New Religion',
                'sort_order' => 3,
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'name' => 'Routing Updated Religion',
                'is_active' => '1',
            ],
        ],
        'education-levels' => [
            'model' => EducationLevel::class,
            'store_identify' => ['name' => 'Routing New Education'],
            'original_identify' => ['name' => 'Routing Original Education'],
            'updated_identify' => ['name' => 'Routing Updated Education'],
            'row' => fn (): EducationLevel => EducationLevel::factory()->create([
                'name' => 'Routing Original Education',
                'level' => 3,
            ]),
            'store' => fn (): array => [
                'name' => 'Routing New Education',
                'level' => 4,
                'sort_order' => 4,
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'name' => 'Routing Updated Education',
                'level' => 5,
                'is_active' => '1',
            ],
        ],
        'marital-statuses' => [
            'model' => MaritalStatus::class,
            'store_identify' => ['name' => 'Routing New Marital'],
            'original_identify' => ['name' => 'Routing Original Marital'],
            'updated_identify' => ['name' => 'Routing Updated Marital'],
            'row' => fn (): MaritalStatus => MaritalStatus::factory()->create(['name' => 'Routing Original Marital']),
            'store' => fn (): array => [
                'name' => 'Routing New Marital',
                'sort_order' => 3,
                'is_active' => '1',
            ],
            'update' => fn (Model $row): array => [
                'name' => 'Routing Updated Marital',
                'is_active' => '1',
            ],
        ],
    };
}

test('active matrix routes every module write into approval requests', function (string $key) {
    $fixture = approvalRoutingFixture($key);
    $maker = approvalRoutingScenario($key);

    $table = (new $fixture['model'])->getTable();

    $this->actingAs($maker);

    $this->post(route($key.'.store'), ($fixture['store'])())
        ->assertRedirect(route($key.'.index'));

    $this->assertDatabaseMissing($table, $fixture['store_identify']);

    $createRequest = ApprovalRequest::query()
        ->where('module_key', $key)
        ->where('action', ApprovalRequest::ACTION_CREATE)
        ->sole();

    expect($createRequest->status)->toBe(ApprovalRequest::STATUS_PENDING)
        ->and($createRequest->target_id)->toBeNull();

    $row = ($fixture['row'])();

    $this->put(route($key.'.update', $row), ($fixture['update'])($row))
        ->assertRedirect(route($key.'.index'));

    $this->assertDatabaseHas($table, ['id' => $row->getKey()] + $fixture['original_identify']);
    $this->assertDatabaseMissing($table, ['id' => $row->getKey()] + $fixture['updated_identify']);

    $updateRequest = ApprovalRequest::query()
        ->where('module_key', $key)
        ->where('action', ApprovalRequest::ACTION_UPDATE)
        ->sole();

    expect($updateRequest->status)->toBe(ApprovalRequest::STATUS_PENDING)
        ->and($updateRequest->target_id)->toBe($row->getKey());

    $this->delete(route($key.'.destroy', $row))
        ->assertRedirect(route($key.'.index'));

    $this->assertDatabaseHas($table, ['id' => $row->getKey()]);

    $deleteRequest = ApprovalRequest::query()
        ->where('module_key', $key)
        ->where('action', ApprovalRequest::ACTION_DELETE)
        ->sole();

    expect($deleteRequest->status)->toBe(ApprovalRequest::STATUS_PENDING)
        ->and($deleteRequest->target_id)->toBe($row->getKey());
})->with(approvalRoutingModuleKeys());

test('modules without an active matrix write directly', function (string $key) {
    $fixture = approvalRoutingFixture($key);
    $maker = approvalRoutingScenario($key, active: false);

    $table = (new $fixture['model'])->getTable();

    $this->actingAs($maker);

    $this->post(route($key.'.store'), ($fixture['store'])())
        ->assertRedirect(route($key.'.index'));

    $this->assertDatabaseHas($table, $fixture['store_identify']);

    expect(ApprovalRequest::query()->count())->toBe(0);
})->with(approvalRoutingModuleKeys());
