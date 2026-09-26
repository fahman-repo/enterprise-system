<?php

use App\Models\ApprovalRequest;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Hash;

function usersApprovalGrant(Role $role, Menu $menu, array $actions): void
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
 * Maker-checker setup for the users module: an eligible maker role with
 * the users menu permissions and a single-step approver role.
 */
function usersApprovalScenario(): array
{
    $usersMenu = Menu::factory()->create(['slug' => 'users']);
    $approvalsMenu = Menu::factory()->create(['slug' => 'approvals']);

    $makerRole = Role::factory()->create();
    usersApprovalGrant($makerRole, $usersMenu, ['view', 'create', 'update', 'delete']);
    usersApprovalGrant($makerRole, $approvalsMenu, ['view', 'delete']);

    $approverRole = Role::factory()->create();
    usersApprovalGrant($approverRole, $approvalsMenu, ['view', 'update']);

    $maker = User::factory()->create(['role_id' => $makerRole->id]);
    $approver = User::factory()->create(['role_id' => $approverRole->id]);

    app(PermissionService::class)->flush();

    app(ApprovalWorkflowService::class)->replaceConfiguration('users', [
        'is_active' => true,
        'maker_roles' => [$makerRole->id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$approverRole->id]],
        ],
    ], $maker);

    return compact('maker', 'approver', 'makerRole', 'approverRole');
}

function usersApprovalPendingRequest(string $action): ApprovalRequest
{
    return ApprovalRequest::query()
        ->where('module_key', 'users')
        ->where('action', $action)
        ->where('status', ApprovalRequest::STATUS_PENDING)
        ->sole();
}

test('approving a create request stores the user with the submitted hashed password', function () {
    $scenario = usersApprovalScenario();
    $assignedRole = Role::factory()->create();

    $this->actingAs($scenario['maker'])
        ->post(route('users.store'), [
            'name' => 'Maker Created Person',
            'email' => 'maker-created@example.com',
            'password' => 'secret-password',
            'role_id' => $assignedRole->id,
            'is_active' => '1',
        ])
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseMissing('users', ['email' => 'maker-created@example.com']);

    $request = usersApprovalPendingRequest(ApprovalRequest::ACTION_CREATE);

    expect($request->proposed_payload['password'])->not->toBe('secret-password');

    $this->actingAs($scenario['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);

    $created = User::query()->where('email', 'maker-created@example.com')->sole();

    expect($created->name)->toBe('Maker Created Person')
        ->and($created->role_id)->toBe($assignedRole->id)
        ->and($created->is_active)->toBeTrue()
        ->and($created->password)->not->toBe('secret-password')
        ->and(Hash::check('secret-password', $created->password))->toBeTrue();
});

test('approval detail masks the proposed password instead of rendering its hash', function () {
    $scenario = usersApprovalScenario();

    $this->actingAs($scenario['maker'])
        ->post(route('users.store'), [
            'name' => 'Masked Person',
            'email' => 'masked-person@example.com',
            'password' => 'secret-password',
            'is_active' => '1',
        ])
        ->assertRedirect(route('users.index'));

    $request = usersApprovalPendingRequest(ApprovalRequest::ACTION_CREATE);
    $hash = $request->proposed_payload['password'];

    expect($hash)->not->toBe('secret-password');

    $this->actingAs($scenario['approver'])
        ->get(route('approvals.show', $request))
        ->assertOk()
        ->assertSee('Masked Person')
        ->assertSee('••••••')
        ->assertDontSee($hash);
});

test('maker cannot submit a request to delete their own account', function () {
    $scenario = usersApprovalScenario();

    $this->actingAs($scenario['maker'])
        ->delete(route('users.destroy', $scenario['maker']))
        ->assertSessionHasErrors(['user' => 'You cannot delete your own account.']);

    $this->assertModelExists($scenario['maker']);
    $this->assertDatabaseCount('approval_requests', 0);
});

test('self-delete guard is re-checked when the request is approved', function () {
    $scenario = usersApprovalScenario();
    $target = User::factory()->create(['role_id' => Role::factory()->create()->id]);

    $this->actingAs($scenario['maker'])
        ->delete(route('users.destroy', $target))
        ->assertRedirect(route('users.index'));

    $request = usersApprovalPendingRequest(ApprovalRequest::ACTION_DELETE);

    // The self-delete guard only fires when the maker is the target, and
    // the controller blocks that combination at submission. Rewriting the
    // maker afterwards is the only reachable way to exercise the
    // apply-time guard.
    $request->update(['maker_user_id' => $target->id]);

    $this->actingAs($scenario['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertSessionHasErrors(['user' => 'You cannot delete your own account.']);

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING);
    $this->assertModelExists($target);
});

test('last-administrator guard is re-checked when the request is approved', function () {
    $scenario = usersApprovalScenario();

    $adminRole = Role::factory()->create();
    usersApprovalGrant($adminRole, Menu::factory()->create(['slug' => 'roles']), ['view', 'update']);

    $target = User::factory()->create(['role_id' => $adminRole->id]);
    $otherAdmin = User::factory()->create(['role_id' => $adminRole->id]);
    app(PermissionService::class)->flush();

    $this->actingAs($scenario['maker'])
        ->delete(route('users.destroy', $target))
        ->assertRedirect(route('users.index'));

    $request = usersApprovalPendingRequest(ApprovalRequest::ACTION_DELETE);

    // Revoke the second administrator between submission and approval so
    // the target becomes the last administrator.
    $otherAdmin->update(['role_id' => Role::factory()->create()->id]);

    $this->actingAs($scenario['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertSessionHasErrors(['approval' => 'You cannot delete the last user with administrative access.']);

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING);
    $this->assertModelExists($target);
});

test('approved update applies the submitted name, email, and role', function () {
    $scenario = usersApprovalScenario();
    $target = User::factory()->create(['name' => 'Before Update', 'email' => 'before-update@example.com']);
    $newRole = Role::factory()->create();

    $this->actingAs($scenario['maker'])
        ->put(route('users.update', $target), [
            'name' => 'After Update',
            'email' => 'before-update@example.com',
            'role_id' => $newRole->id,
            'is_active' => '1',
        ])
        ->assertRedirect(route('users.index'));

    expect($target->fresh()->name)->toBe('Before Update')
        ->and($target->fresh()->role_id)->not->toBe($newRole->id);

    $request = usersApprovalPendingRequest(ApprovalRequest::ACTION_UPDATE);

    expect($request->target_id)->toBe($target->id);

    $this->actingAs($scenario['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    expect($target->fresh()->name)->toBe('After Update')
        ->and($target->fresh()->email)->toBe('before-update@example.com')
        ->and($target->fresh()->role_id)->toBe($newRole->id);
});
