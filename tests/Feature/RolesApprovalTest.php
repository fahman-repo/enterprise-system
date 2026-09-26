<?php

use App\Models\ApprovalRequest;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;
use Illuminate\Support\Facades\DB;

function rolesApprovalGrant(Role $role, Menu $menu, array $actions): void
{
    $flags = [
        'can_view' => false,
        'can_create' => false,
        'can_update' => false,
        'can_delete' => false,
    ];

    foreach ($actions as $action) {
        $flags['can_'.$action] = true;
    }

    $role->menus()->syncWithoutDetaching([$menu->id => $flags]);
}

/**
 * Maker-checker setup for the roles module: an eligible maker role with
 * the roles menu permissions and a single-step approver role.
 */
function rolesApprovalScenario(): array
{
    $rolesMenu = Menu::factory()->create(['slug' => 'roles']);
    $approvalsMenu = Menu::factory()->create(['slug' => 'approvals']);

    $makerRole = Role::factory()->create();
    rolesApprovalGrant($makerRole, $rolesMenu, ['view', 'create', 'update', 'delete']);
    rolesApprovalGrant($makerRole, $approvalsMenu, ['view', 'delete']);

    $approverRole = Role::factory()->create();
    rolesApprovalGrant($approverRole, $approvalsMenu, ['view', 'update']);

    $maker = User::factory()->create(['role_id' => $makerRole->id]);
    $approver = User::factory()->create(['role_id' => $approverRole->id]);

    app(PermissionService::class)->flush();

    app(ApprovalWorkflowService::class)->replaceConfiguration('roles', [
        'is_active' => true,
        'maker_roles' => [$makerRole->id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$approverRole->id]],
        ],
    ], $maker);

    return compact('maker', 'approver', 'makerRole', 'approverRole');
}

function rolesApprovalPendingRequest(string $action): ApprovalRequest
{
    return ApprovalRequest::query()
        ->where('module_key', 'roles')
        ->where('action', $action)
        ->where('status', ApprovalRequest::STATUS_PENDING)
        ->sole();
}

test('approving a create request applies the submitted permission matrix', function () {
    $scenario = rolesApprovalScenario();
    $menu = Menu::factory()->create(['slug' => 'reports-widget']);

    $this->actingAs($scenario['maker'])
        ->post(route('roles.store'), [
            'name' => 'Approved Role',
            'slug' => 'approved-role',
            'is_active' => '1',
            'permissions' => [
                $menu->id => ['can_view' => '1', 'can_create' => '1', 'can_update' => '0', 'can_delete' => '0'],
            ],
        ])
        ->assertRedirect(route('roles.index'));

    $this->assertDatabaseMissing('roles', ['slug' => 'approved-role']);
    expect(DB::table('role_menu')->where('menu_id', $menu->id)->exists())->toBeFalse();

    $request = rolesApprovalPendingRequest(ApprovalRequest::ACTION_CREATE);

    $this->actingAs($scenario['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);

    $role = Role::query()->where('slug', 'approved-role')->sole();
    $pivot = DB::table('role_menu')->where('role_id', $role->id)->where('menu_id', $menu->id)->sole();

    expect((bool) $pivot->can_view)->toBeTrue()
        ->and((bool) $pivot->can_create)->toBeTrue()
        ->and((bool) $pivot->can_update)->toBeFalse()
        ->and((bool) $pivot->can_delete)->toBeFalse()
        ->and($role->permissionsSnapshot())->toBe([
            $menu->id => ['view' => true, 'create' => true, 'update' => false, 'delete' => false],
        ]);
});

test('approving an update request replaces the role permission matrix', function () {
    $scenario = rolesApprovalScenario();
    $oldMenu = Menu::factory()->create(['slug' => 'roles-approval-old']);
    $newMenu = Menu::factory()->create(['slug' => 'roles-approval-new']);
    $role = Role::factory()->create(['name' => 'Role Before', 'slug' => 'role-before']);
    $role->menus()->syncWithoutDetaching([$oldMenu->id => ['can_view' => true]]);
    app(PermissionService::class)->flush();

    $this->actingAs($scenario['maker'])
        ->put(route('roles.update', $role), [
            'name' => 'Role After',
            'slug' => 'role-before',
            'is_active' => '1',
            'permissions' => [
                $newMenu->id => ['can_view' => '1', 'can_delete' => '1'],
            ],
        ])
        ->assertRedirect(route('roles.index'));

    expect($role->fresh()->name)->toBe('Role Before')
        ->and($role->permissionsSnapshot())->toBe([
            $oldMenu->id => ['view' => true, 'create' => false, 'update' => false, 'delete' => false],
        ]);

    $request = rolesApprovalPendingRequest(ApprovalRequest::ACTION_UPDATE);

    expect($request->target_id)->toBe($role->id);

    $this->actingAs($scenario['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    expect($role->fresh()->name)->toBe('Role After')
        ->and($role->permissionsSnapshot())->toBe([
            $newMenu->id => ['view' => true, 'create' => false, 'update' => false, 'delete' => true],
        ]);
});

test('delete blocked by a later user assignment remains pending', function () {
    $scenario = rolesApprovalScenario();
    $role = Role::factory()->create();

    $this->actingAs($scenario['maker'])
        ->delete(route('roles.destroy', $role))
        ->assertRedirect(route('roles.index'));

    $request = rolesApprovalPendingRequest(ApprovalRequest::ACTION_DELETE);

    User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($scenario['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertSessionHasErrors(['approval' => 'This role is still assigned to users. Reassign them first.']);

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING);
    $this->assertModelExists($role);
});

test('a role assigned to users cannot request deletion', function () {
    $scenario = rolesApprovalScenario();
    $role = Role::factory()->create();
    User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($scenario['maker'])
        ->delete(route('roles.destroy', $role))
        ->assertSessionHasErrors(['role' => 'This role is still assigned to users. Reassign them first.']);

    $this->assertModelExists($role);
    $this->assertDatabaseCount('approval_requests', 0);
});
