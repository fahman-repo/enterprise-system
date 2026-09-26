<?php

use App\Models\ApprovalRequest;
use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Menu;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use App\Services\PermissionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Maker and approver wired to an active employees approval matrix.
 *
 * @return array{maker: User, approver: User}
 */
function employeesApprovalActors(): array
{
    $moduleMenu = Menu::factory()->create(['slug' => 'employees']);
    $approvalsMenu = Menu::factory()->create(['slug' => 'approvals']);

    $makerRole = Role::factory()->create();
    $makerRole->menus()->sync([
        $moduleMenu->id => ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true],
        $approvalsMenu->id => ['can_view' => true, 'can_delete' => true],
    ]);

    $approverRole = Role::factory()->create();
    $approverRole->menus()->sync([
        $approvalsMenu->id => ['can_view' => true, 'can_update' => true],
    ]);

    $maker = User::factory()->create(['role_id' => $makerRole->id]);
    $approver = User::factory()->create(['role_id' => $approverRole->id]);

    app(PermissionService::class)->flush();

    app(ApprovalWorkflowService::class)->replaceConfiguration('employees', [
        'is_active' => true,
        'maker_roles' => [$makerRole->id],
        'stages' => [
            ['stage_number' => 1, 'name' => 'Approval', 'role_ids' => [$approverRole->id]],
        ],
    ], $maker);

    return ['maker' => $maker, 'approver' => $approver];
}

/**
 * A consistent division, department, position and status set.
 *
 * @return array<string, int>
 */
function employeesApprovalPlacement(): array
{
    $division = Division::factory()->create();
    $department = Department::factory()->forDivision($division)->create();
    $position = Position::factory()->forDepartment($department)->create();
    $status = EmploymentStatus::factory()->create();

    return [
        'division_id' => $division->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_status_id' => $status->id,
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function employeesApprovalCreatePayload(array $overrides = []): array
{
    return array_merge(employeesApprovalPlacement(), [
        'name' => 'Approval Candidate',
        'gender' => 'female',
        'join_date' => '2024-01-15',
        'is_active' => '1',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function employeesApprovalUpdatePayload(Employee $employee, array $overrides = []): array
{
    return array_merge([
        'division_id' => $employee->division_id,
        'department_id' => $employee->department_id,
        'position_id' => $employee->position_id,
        'employment_status_id' => $employee->employment_status_id,
        'name' => $employee->name,
        'gender' => $employee->gender,
        'join_date' => $employee->join_date->toDateString(),
        'is_active' => $employee->is_active ? '1' : '0',
    ], $overrides);
}

function employeesApprovalPhoto(string $name = 'approval-candidate.png'): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    return UploadedFile::fake()->createWithContent($name, $png);
}

function employeesApprovalPendingRequest(string $action, ?Employee $employee = null): ApprovalRequest
{
    $query = ApprovalRequest::query()
        ->where('module_key', 'employees')
        ->where('action', $action)
        ->where('status', ApprovalRequest::STATUS_PENDING);

    if ($employee !== null) {
        $query->where('target_id', $employee->id);
    }

    return $query->sole();
}

test('a blank employee number is generated when an employee create is approved', function () {
    $actors = employeesApprovalActors();
    $this->actingAs($actors['maker']);

    $payload = employeesApprovalCreatePayload();
    unset($payload['employee_number']);

    $this->post(route('employees.store'), $payload)
        ->assertRedirect(route('employees.index'));

    $this->assertDatabaseMissing('employees', ['name' => 'Approval Candidate']);

    $request = employeesApprovalPendingRequest(ApprovalRequest::ACTION_CREATE);

    expect($request->proposed_payload)->not->toHaveKey('employee_number');

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    $employee = Employee::query()->where('name', 'Approval Candidate')->sole();

    expect($employee->employee_number)->toStartWith(config('hr.employee_number_prefix').'-')
        ->and($request->fresh()->status)->toBe(ApprovalRequest::STATUS_APPROVED);
});

test('an employee cannot be submitted as their own manager', function () {
    $actors = employeesApprovalActors();
    $this->actingAs($actors['maker']);

    $employee = Employee::factory()->create(employeesApprovalPlacement());

    $this->put(route('employees.update', $employee), employeesApprovalUpdatePayload($employee, [
        'manager_id' => $employee->id,
    ]))->assertSessionHasErrors('manager_id');

    $this->assertDatabaseCount('approval_requests', 0);
    expect($employee->fresh()->manager_id)->toBeNull();
});

test('a manager cycle created after submission blocks the approval', function () {
    $actors = employeesApprovalActors();
    $this->actingAs($actors['maker']);

    $placement = employeesApprovalPlacement();
    $target = Employee::factory()->create($placement);
    $manager = Employee::factory()->create($placement);
    $report = Employee::factory()->create($placement + ['manager_id' => $manager->id]);

    $this->put(route('employees.update', $target), employeesApprovalUpdatePayload($target, [
        'manager_id' => $manager->id,
    ]))->assertRedirect(route('employees.index'));

    $request = employeesApprovalPendingRequest(ApprovalRequest::ACTION_UPDATE, $target);

    $manager->update(['manager_id' => $target->id]);

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertSessionHasErrors('manager_id');

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_PENDING)
        ->and($target->fresh()->manager_id)->toBeNull()
        ->and($report->fresh()->manager_id)->toBe($manager->id);
});

test('an employee create with a photo submits a pending request and stores the photo', function () {
    Storage::fake('public');
    $actors = employeesApprovalActors();
    $this->actingAs($actors['maker']);

    $this->post(route('employees.store'), employeesApprovalCreatePayload([
        'name' => 'Photographed Candidate',
        'photo' => employeesApprovalPhoto(),
    ]))->assertRedirect(route('employees.index'));

    $this->assertDatabaseMissing('employees', ['name' => 'Photographed Candidate']);

    $request = employeesApprovalPendingRequest(ApprovalRequest::ACTION_CREATE);

    expect($request->proposed_payload['photo_path'])->toBeString()->not->toBe('');
    Storage::disk('public')->assertExists($request->proposed_payload['photo_path']);
});

test('approving an employee update with a photo replaces the old file', function () {
    Storage::fake('public');
    $actors = employeesApprovalActors();
    $this->actingAs($actors['maker']);

    $oldPath = employeesApprovalPhoto('old-photo.png')->store('employees', 'public');
    $employee = Employee::factory()->create(employeesApprovalPlacement() + [
        'name' => 'Original Employee',
        'photo_path' => $oldPath,
    ]);

    $this->put(route('employees.update', $employee), employeesApprovalUpdatePayload($employee, [
        'name' => 'Replaced Photo Employee',
        'photo' => employeesApprovalPhoto('new-photo.png'),
    ]))->assertRedirect(route('employees.index'));

    expect($employee->fresh()->name)->toBe('Original Employee')
        ->and($employee->fresh()->photo_path)->toBe($oldPath);
    Storage::disk('public')->assertExists($oldPath);

    $request = employeesApprovalPendingRequest(ApprovalRequest::ACTION_UPDATE, $employee);
    $newPath = $request->proposed_payload['photo_path'];

    expect($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertExists($newPath);

    $this->actingAs($actors['approver'])
        ->post(route('approvals.approve', $request), ['comment' => 'ok'])
        ->assertRedirect(route('approvals.show', $request));

    expect($employee->fresh()->name)->toBe('Replaced Photo Employee')
        ->and($employee->fresh()->photo_path)->toBe($newPath);
    Storage::disk('public')->assertExists($newPath);
    Storage::disk('public')->assertMissing($oldPath);
});

test('rejecting an employee update deletes the newly stored photo', function () {
    Storage::fake('public');
    $actors = employeesApprovalActors();
    $this->actingAs($actors['maker']);

    $oldPath = employeesApprovalPhoto('kept-photo.png')->store('employees', 'public');
    $employee = Employee::factory()->create(employeesApprovalPlacement() + [
        'name' => 'Rejected Employee',
        'photo_path' => $oldPath,
    ]);

    $this->put(route('employees.update', $employee), employeesApprovalUpdatePayload($employee, [
        'name' => 'Never Applied Employee',
        'photo' => employeesApprovalPhoto('orphan-photo.png'),
    ]))->assertRedirect(route('employees.index'));

    $request = employeesApprovalPendingRequest(ApprovalRequest::ACTION_UPDATE, $employee);
    $orphanPath = $request->proposed_payload['photo_path'];

    expect($orphanPath)->not->toBe($oldPath);
    Storage::disk('public')->assertExists($orphanPath);

    $this->actingAs($actors['approver'])
        ->post(route('approvals.reject', $request), ['comment' => 'not this time'])
        ->assertRedirect(route('approvals.show', $request));

    expect($request->fresh()->status)->toBe(ApprovalRequest::STATUS_REJECTED)
        ->and($employee->fresh()->name)->toBe('Rejected Employee')
        ->and($employee->fresh()->photo_path)->toBe($oldPath);
    Storage::disk('public')->assertMissing($orphanPath);
    Storage::disk('public')->assertExists($oldPath);
});

test('an employee update without an upload carries the current photo path', function () {
    Storage::fake('public');
    $actors = employeesApprovalActors();
    $this->actingAs($actors['maker']);

    $photoPath = employeesApprovalPhoto('unchanged-photo.png')->store('employees', 'public');
    $employee = Employee::factory()->create(employeesApprovalPlacement() + [
        'name' => 'Unchanged Photo Employee',
        'photo_path' => $photoPath,
    ]);

    $this->put(route('employees.update', $employee), employeesApprovalUpdatePayload($employee, [
        'name' => 'Renamed Employee',
    ]))->assertRedirect(route('employees.index'));

    $request = employeesApprovalPendingRequest(ApprovalRequest::ACTION_UPDATE, $employee);

    expect($request->proposed_payload['photo_path'])->toBe($photoPath)
        ->and($employee->fresh()->name)->toBe('Unchanged Photo Employee');
    Storage::disk('public')->assertExists($photoPath);
});
