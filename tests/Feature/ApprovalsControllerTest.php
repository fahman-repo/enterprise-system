<?php

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;

function queueMenuGrant(Role $role, Menu $menu, array $actions): void
{
    $role->menus()->syncWithoutDetaching([$menu->id => [
        'can_view' => in_array('view', $actions, true),
        'can_create' => in_array('create', $actions, true),
        'can_update' => in_array('update', $actions, true),
        'can_delete' => in_array('delete', $actions, true),
    ]]);
}

function approvalQueueScenario(string $makerName = 'Queue Maker', string $gradeName = 'Queue Grade'): array
{
    $gradesMenu = Menu::firstOrCreate(['slug' => 'grades'], [
        'name' => 'Grades',
        'icon' => 'layers',
        'route_name' => 'grades.index',
        'sort_order' => 1,
        'is_active' => true,
    ]);
    $approvalsMenu = Menu::firstOrCreate(['slug' => 'approvals'], [
        'name' => 'Approvals',
        'icon' => 'alert-circle',
        'route_name' => 'approvals.index',
        'sort_order' => 2,
        'is_active' => true,
    ]);
    $makerRole = Role::factory()->create();
    $approverRole = Role::factory()->create();
    queueMenuGrant($makerRole, $gradesMenu, ['view', 'create', 'update', 'delete']);
    queueMenuGrant($makerRole, $approvalsMenu, ['view', 'delete']);
    queueMenuGrant($approverRole, $approvalsMenu, ['view', 'update']);
    $maker = User::factory()->create(['name' => $makerName, 'role_id' => $makerRole->id]);
    $approver = User::factory()->create(['role_id' => $approverRole->id]);
    app(PermissionService::class)->flush();

    $service = app(ApprovalWorkflowService::class);
    $service->replaceConfiguration('grades', [
        'is_active' => true,
        'maker_roles' => [$makerRole->id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Manager Approval', 'role_ids' => [$approverRole->id]],
        ],
    ], $maker);
    $request = $service->submit($maker, 'grades', 'create', null, [
        'name' => $gradeName,
        'level' => 3,
        'description' => 'Awaiting review',
        'is_active' => true,
    ]);

    return compact('maker', 'approver', 'request');
}

test('approval routes require menu permissions', function () {
    $scenario = approvalQueueScenario();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->get(route('approvals.index'))->assertForbidden();
    $this->actingAs($outsider)->get(route('approvals.show', $scenario['request']))->assertForbidden();
    $this->actingAs($outsider)->post(route('approvals.approve', $scenario['request']))->assertForbidden();
    $this->actingAs($outsider)->post(route('approvals.reject', $scenario['request']), ['comment' => 'No'])->assertForbidden();
    $this->actingAs($outsider)->post(route('approvals.cancel', $scenario['request']))->assertForbidden();
});

test('approval queue supports search, filters, and awaiting scopes', function () {
    $scenario = approvalQueueScenario('Searchable Maker');
    $request = $scenario['request'];

    $this->actingAs($scenario['maker'])->get(route('approvals.index', [
        'search' => 'Searchable Maker',
        'module' => 'grades',
        'action' => 'create',
        'status' => 'Pending',
        'scope' => 'mine',
    ]))
        ->assertOk()
        ->assertSee('#'.$request->id);

    $this->actingAs($scenario['maker'])->get(route('approvals.index', ['search' => 'missing-term']))
        ->assertOk()
        ->assertDontSee('#'.$request->id);
    $this->actingAs($scenario['maker'])->get(route('approvals.index', ['action' => 'delete']))
        ->assertOk()
        ->assertDontSee('#'.$request->id);
    $this->actingAs($scenario['maker'])->get(route('approvals.index', ['scope' => 'awaiting']))
        ->assertOk()
        ->assertDontSee('#'.$request->id);
    $this->actingAs($scenario['approver'])->get(route('approvals.index', ['scope' => 'awaiting']))
        ->assertOk()
        ->assertSee('#'.$request->id);
});

test('approval detail shows proposed values, diffs, stages, and participant actions', function () {
    $scenario = approvalQueueScenario();
    $request = $scenario['request'];

    $this->actingAs($scenario['approver'])->get(route('approvals.show', $request))
        ->assertOk()
        ->assertSee('Proposed changes')
        ->assertSee('Queue Grade')
        ->assertSee('Before')
        ->assertSee('Proposed')
        ->assertSee('Manager Approval')
        ->assertSee('Approve stage');

    $this->actingAs($scenario['maker'])->get(route('approvals.show', $request))
        ->assertOk()
        ->assertSee('Cancel request')
        ->assertDontSee('Approve stage');
});

test('global and grades request views include grade change requests', function () {
    $scenario = approvalQueueScenario();
    $request = $scenario['request'];

    $this->actingAs($scenario['maker'])->get(route('approvals.index', ['module' => 'grades']))
        ->assertOk()
        ->assertSee('#'.$request->id)
        ->assertSee('Queue Maker');

    $this->actingAs($scenario['maker'])->get(route('grades.index', ['tab' => 'requests']))
        ->assertOk()
        ->assertSee('Change Requests')
        ->assertSee('#'.$request->id);
});

test('approval comments and maker cancellation appear in request history', function () {
    $scenario = approvalQueueScenario();
    $request = $scenario['request'];

    $this->actingAs($scenario['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'Looks correct'])
        ->assertRedirect(route('approvals.show', $request));

    $this->actingAs($scenario['approver'])->get(route('approvals.show', $request))
        ->assertOk()
        ->assertSee('Timeline')
        ->assertSee('Approval request submitted.')
        ->assertSee('Looks correct')
        ->assertSee('Approved');

    $cancelled = approvalQueueScenario('Cancel Maker', 'Cancelled Queue Grade');
    $this->actingAs($cancelled['maker'])
        ->post(route('approvals.cancel', $cancelled['request']), ['comment' => 'Submitted by mistake'])
        ->assertRedirect(route('approvals.show', $cancelled['request']));

    $this->actingAs($cancelled['maker'])->get(route('approvals.show', $cancelled['request']))
        ->assertOk()
        ->assertSee('Submitted by mistake')
        ->assertSee('Cancelled');
});
