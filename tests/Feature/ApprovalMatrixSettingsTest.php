<?php

use App\Models\Activity;
use App\Models\ApprovalMatrix;
use App\Models\ApprovalMatrixStage;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\Approvals\ApprovalModuleRegistry;
use App\Services\PermissionService;
use Database\Seeders\DatabaseSeeder;

function actingMatrixAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'approval-matrices']);
    $role->menus()->sync([$menu->id => [
        'can_view' => true,
        'can_create' => true,
        'can_update' => true,
        'can_delete' => true,
    ]]);
    app(PermissionService::class)->flush();

    return User::factory()->create(['role_id' => $role->id]);
}

function gradeMatrixPayload(Role $makerRole, Role $approverRole): array
{
    return [
        'module_key' => 'grades',
        'is_active' => '1',
        'maker_roles' => [$makerRole->id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Manager Approval', 'role_ids' => [$approverRole->id]],
        ],
    ];
}

beforeEach(function () {
    $this->admin = actingMatrixAdmin();
    $this->makerRole = Role::factory()->create();
    $this->approverRole = Role::factory()->create();
    $this->actingAs($this->admin);
});

test('approval matrix routes require menu permissions', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('approval-matrices.index'))->assertForbidden();
    $this->get(route('approval-matrices.edit', 'grades'))->assertForbidden();
    $this->put(route('approval-matrices.update', 'grades'), [
        'module_key' => 'grades',
        'maker_roles' => [$this->makerRole->id],
        'stages' => [['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$this->approverRole->id]]],
    ])->assertForbidden();
});

test('approval matrix index lists supported grade configuration status', function () {
    $this->get(route('approval-matrices.index'))
        ->assertOk()
        ->assertSee('Grades')
        ->assertSee('Not configured')
        ->assertSee('Configure');
});

test('approval matrix index lists every supported module in sidebar order', function () {
    $registry = app(ApprovalModuleRegistry::class);

    expect($registry->keys())->toBe([
        'users',
        'roles',
        'menus',
        'entities',
        'products',
        'categories',
        'brands',
        'units',
        'employees',
        'divisions',
        'departments',
        'org-units',
        'positions',
        'grades',
        'employment-statuses',
        'work-locations',
        'religions',
        'education-levels',
        'marital-statuses',
    ]);

    $response = $this->get(route('approval-matrices.index'))->assertOk();

    foreach ($registry->all() as $module) {
        $response->assertSee($module->label());
    }
});

test('every supported module stores its own matrix configuration', function (string $moduleKey) {
    $this->put(route('approval-matrices.update', $moduleKey), [
        'module_key' => $moduleKey,
        'is_active' => '1',
        'maker_roles' => [$this->makerRole->id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$this->approverRole->id]],
        ],
    ])->assertRedirect(route('approval-matrices.index'));

    $this->assertDatabaseHas('approval_matrices', [
        'module_key' => $moduleKey,
        'is_active' => true,
    ]);

    $this->get(route('approval-matrices.edit', $moduleKey))
        ->assertOk()
        ->assertSee('Approval');
})->with([
    'users',
    'roles',
    'menus',
    'entities',
    'products',
    'categories',
    'brands',
    'units',
    'employees',
    'divisions',
    'departments',
    'org-units',
    'positions',
    'grades',
    'employment-statuses',
    'work-locations',
    'religions',
    'education-levels',
    'marital-statuses',
]);

test('approval matrix editor renders the stage builder for authorized admins', function () {
    $this->get(route('approval-matrices.edit', 'grades'))
        ->assertOk()
        ->assertSee('Grades')
        ->assertSee($this->makerRole->name)
        ->assertSee($this->approverRole->name)
        ->assertSee('stages[');
});

test('approval matrix editor rejects unsupported modules', function () {
    $this->get(route('approval-matrices.edit', 'audit-logs'))->assertNotFound();

    $this->put(route('approval-matrices.update', 'grades'), [
        'module_key' => 'audit-logs',
        'is_active' => '1',
        'maker_roles' => [$this->makerRole->id],
        'stages' => [['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$this->approverRole->id]]],
    ])->assertSessionHasErrors('module_key');
});

test('approval matrix requires maker roles and valid stages', function () {
    $this->put(route('approval-matrices.update', 'grades'), [
        'module_key' => 'grades',
        'is_active' => '1',
        'maker_roles' => [],
        'stages' => [
            ['stage_number' => 3, 'name' => '', 'role_ids' => []],
            ['stage_number' => 3, 'name' => 'Second', 'role_ids' => [$this->approverRole->id]],
        ],
    ])
        ->assertSessionHasErrors(['maker_roles', 'stages', 'stages.0.name', 'stages.0.role_ids', 'stages.1.stage_number']);

    $this->assertDatabaseCount('approval_matrices', 0);
});

test('approval matrix rejects maker and approver role overlap', function () {
    $payload = gradeMatrixPayload($this->makerRole, $this->approverRole);
    $payload['stages'][0]['role_ids'][] = $this->makerRole->id;

    $this->put(route('approval-matrices.update', 'grades'), $payload)
        ->assertSessionHasErrors('stages');

    $this->assertDatabaseCount('approval_matrices', 0);
});

test('saving an approval matrix atomically replaces its assignments', function () {
    $this->put(route('approval-matrices.update', 'grades'), gradeMatrixPayload($this->makerRole, $this->approverRole))
        ->assertRedirect(route('approval-matrices.index'));

    $replacementApprover = Role::factory()->create();
    $this->put(route('approval-matrices.update', 'grades'), [
        'module_key' => 'grades',
        'is_active' => '1',
        'maker_roles' => [$this->makerRole->id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'First Approval', 'role_ids' => [$this->approverRole->id]],
            ['stage_number' => 2, 'name' => 'Final Approval', 'role_ids' => [$replacementApprover->id]],
        ],
    ])->assertRedirect(route('approval-matrices.index'));

    $this->assertDatabaseHas('approval_matrices', [
        'module_key' => 'grades',
        'is_active' => true,
        'configuration_version' => 2,
    ]);
    expect(ApprovalMatrixStage::query()->count())->toBe(2);

    $entry = Activity::query()->where('log_name', 'approval-matrix')->where('event', 'configuration_replaced')->latest('id')->first();
    expect($entry)->not->toBeNull();
});

test('database seeder adds approval menus and admin grants idempotently', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $admin = Role::query()->where('slug', 'admin')->sole();
    $menus = Menu::query()->whereIn('slug', ['approval-matrices', 'approvals'])->get();

    expect($menus)->toHaveCount(2)
        ->and(ApprovalMatrix::query()->count())->toBe(0);

    foreach ($menus as $menu) {
        $permission = $admin->menus()->where('menus.id', $menu->id)->sole()->pivot;

        expect((bool) $permission->can_view)->toBeTrue()
            ->and((bool) $permission->can_create)->toBeTrue()
            ->and((bool) $permission->can_update)->toBeTrue()
            ->and((bool) $permission->can_delete)->toBeTrue();
    }
});
