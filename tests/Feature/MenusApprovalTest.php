<?php

use App\Models\ApprovalRequest;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;
use Illuminate\Support\Facades\DB;

function menusApprovalGrant(Role $role, Menu $menu, array $actions): void
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
 * Maker-checker setup for the menus module: an eligible maker role with
 * the menus menu permissions and a single-step approver role.
 */
function menusApprovalScenario(): array
{
    $menusMenu = Menu::factory()->create(['slug' => 'menus']);
    $approvalsMenu = Menu::factory()->create(['slug' => 'approvals']);

    $makerRole = Role::factory()->create();
    menusApprovalGrant($makerRole, $menusMenu, ['view', 'create', 'update', 'delete']);
    menusApprovalGrant($makerRole, $approvalsMenu, ['view', 'delete']);

    $approverRole = Role::factory()->create();
    menusApprovalGrant($approverRole, $approvalsMenu, ['view', 'update']);

    $maker = User::factory()->create(['role_id' => $makerRole->id]);
    $approver = User::factory()->create(['role_id' => $approverRole->id]);

    app(PermissionService::class)->flush();

    app(ApprovalWorkflowService::class)->replaceConfiguration('menus', [
        'is_active' => true,
        'maker_roles' => [$makerRole->id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$approverRole->id]],
        ],
    ], $maker);

    return compact('maker', 'approver', 'makerRole', 'approverRole');
}

function menusApprovalPendingRequest(string $action): ApprovalRequest
{
    return ApprovalRequest::query()
        ->where('module_key', 'menus')
        ->where('action', $action)
        ->where('status', ApprovalRequest::STATUS_PENDING)
        ->sole();
}

test('approving a create request stores the menu item', function () {
    $scenario = menusApprovalScenario();

    $this->actingAs($scenario['maker'])
        ->post(route('menus.store'), [
            'name' => 'Approved Menu Item',
            'slug' => 'approved-menu-item',
            'icon' => 'users',
            'route_name' => 'dashboard',
            'sort_order' => 3,
            'is_active' => '1',
        ])
        ->assertRedirect(route('menus.index'));

    $this->assertDatabaseMissing('menus', ['slug' => 'approved-menu-item']);

    $request = menusApprovalPendingRequest(ApprovalRequest::ACTION_CREATE);

    $this->actingAs($scenario['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);

    $menu = Menu::query()->where('slug', 'approved-menu-item')->sole();

    expect($menu->name)->toBe('Approved Menu Item')
        ->and($menu->icon)->toBe('users')
        ->and($menu->route_name)->toBe('dashboard')
        ->and($menu->sort_order)->toBe(3)
        ->and($menu->is_active)->toBeTrue();
});

test('unknown route names are rejected at submission', function () {
    $scenario = menusApprovalScenario();

    $this->actingAs($scenario['maker'])
        ->post(route('menus.store'), [
            'name' => 'Broken Menu',
            'slug' => 'broken-menu',
            'route_name' => 'does.not.exist',
        ])
        ->assertSessionHasErrors(['route_name' => 'The route name does.not.exist does not exist.']);

    $this->assertDatabaseCount('approval_requests', 0);
});

test('a parent that became a descendant between submission and approval blocks the apply', function () {
    $scenario = menusApprovalScenario();
    $parent = Menu::factory()->create(['name' => 'Stale Parent', 'slug' => 'stale-parent']);
    Menu::factory()->create(['slug' => 'stale-child', 'parent_id' => $parent->id]);
    $moving = Menu::factory()->create(['name' => 'Moving Menu', 'slug' => 'moving-menu']);

    $this->actingAs($scenario['maker'])
        ->put(route('menus.update', $parent), [
            'name' => $parent->name,
            'slug' => $parent->slug,
            'parent_id' => $moving->id,
            'sort_order' => $parent->sort_order,
            'is_active' => '1',
        ])
        ->assertRedirect(route('menus.index'));

    $request = menusApprovalPendingRequest(ApprovalRequest::ACTION_UPDATE);

    expect($request->proposed_payload['parent_id'])->toBe($moving->id);

    // The parent is a valid option at submission; it becomes a descendant
    // of the submitted target afterwards, so the apply-time rule must fail.
    $moving->update(['parent_id' => $parent->id]);

    $this->actingAs($scenario['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertSessionHasErrors(['parent_id' => 'The selected parent id is invalid.']);

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING)
        ->and($parent->fresh()->parent_id)->toBeNull();
});

test('approving a delete request reparents children and cascades role assignments', function () {
    $scenario = menusApprovalScenario();
    $parent = Menu::factory()->create(['name' => 'Doomed Parent', 'slug' => 'doomed-parent']);
    $child = Menu::factory()->create(['name' => 'Doomed Child', 'slug' => 'doomed-child', 'parent_id' => $parent->id]);
    $assignedRole = Role::factory()->create();
    $assignedRole->menus()->syncWithoutDetaching([$parent->id => ['can_view' => true]]);

    $this->actingAs($scenario['maker'])
        ->delete(route('menus.destroy', $parent))
        ->assertRedirect(route('menus.index'));

    $request = menusApprovalPendingRequest(ApprovalRequest::ACTION_DELETE);

    $this->actingAs($scenario['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);

    $this->assertModelMissing($parent);

    expect($child->fresh()->parent_id)->toBeNull()
        ->and(DB::table('role_menu')->where('menu_id', $parent->id)->exists())->toBeFalse();
});
