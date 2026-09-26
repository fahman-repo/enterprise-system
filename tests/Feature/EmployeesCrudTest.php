<?php

use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Menu;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function actingEmployeeAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'employees']);

    $role->menus()->sync([$menu->id => [
        'can_view' => true,
        'can_create' => true,
        'can_update' => true,
        'can_delete' => true,
    ]]);

    app(PermissionService::class)->flush();

    return User::factory()->create(['role_id' => $role->id]);
}

function fakeEmployeePhoto(string $name = 'employee.png'): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    return UploadedFile::fake()->createWithContent($name, $png);
}

/**
 * @return array<string, mixed>
 */
function employeePayload(array $overrides = []): array
{
    $division = Division::factory()->create();
    $department = Department::factory()->forDivision($division)->create();
    $position = Position::factory()->forDepartment($department)->create();
    $status = EmploymentStatus::factory()->create();

    return array_merge([
        'division_id' => $division->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_status_id' => $status->id,
        'name' => 'Jane Candidate',
        'gender' => 'female',
        'join_date' => '2024-01-15',
        'is_active' => '1',
    ], $overrides);
}

beforeEach(function () {
    $this->admin = actingEmployeeAdmin();
    $this->actingAs($this->admin);
});

test('employees index lists employees with their placement', function () {
    $division = Division::factory()->create(['name' => 'Northwind Division']);
    $department = Department::factory()->forDivision($division)->create(['name' => 'Quixotic Department']);
    $position = Position::factory()->forDepartment($department)->create(['name' => 'Quixotic Analyst']);
    Employee::factory()->forDepartment($department)->create([
        'name' => 'Quixotic Person',
        'position_id' => $position->id,
    ]);

    $this->get(route('employees.index'))
        ->assertOk()
        ->assertSee('Quixotic Person')
        ->assertSee('Quixotic Department')
        ->assertSee('Quixotic Analyst');
});

test('employees index search matches name, number and email', function () {
    $byName = Employee::factory()->create(['name' => 'Quixotic Person']);
    $byNumber = Employee::factory()->create(['employee_number' => 'EMP-SPECIAL-1']);
    $byEmail = Employee::factory()->create(['email' => 'special.employee@example.com']);
    $unmatched = Employee::factory()->create(['name' => 'Nothing Here']);

    $this->get(route('employees.index', ['search' => 'Quixotic']))
        ->assertOk()
        ->assertSee($byName->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('employees.index', ['search' => 'EMP-SPECIAL-1']))
        ->assertOk()
        ->assertSee($byNumber->employee_number);

    $this->get(route('employees.index', ['search' => 'special.employee@example.com']))
        ->assertOk()
        ->assertSee($byEmail->name);
});

test('employees index filters by department and status', function () {
    $department = Department::factory()->create();
    $inDepartment = Employee::factory()->forDepartment($department)->create(['name' => 'In Department']);
    $inactive = Employee::factory()->inactive()->create(['name' => 'Inactive Employee']);
    $elsewhere = Employee::factory()->create(['name' => 'Elsewhere Employee']);

    $this->get(route('employees.index', ['department_id' => $department->id]))
        ->assertOk()
        ->assertSee($inDepartment->name)
        ->assertDontSee($elsewhere->name);

    $this->get(route('employees.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee($inactive->name)
        ->assertDontSee($inDepartment->name);
});

test('employees index ignores status when a placement filter is applied', function () {
    $division = Division::factory()->create();
    $department = Department::factory()->forDivision($division)->create();
    $placed = Employee::factory()->forDepartment($department)->create(['name' => 'Active In Division']);
    $inactive = Employee::factory()->inactive()->create(['name' => 'Inactive Elsewhere']);

    $this->get(route('employees.index', ['division_id' => $division->id, 'status' => 'inactive']))
        ->assertOk()
        ->assertSee($placed->name)
        ->assertDontSee($inactive->name);
});

test('employees index applies only the first placement filter', function () {
    $division = Division::factory()->create();
    $department = Department::factory()->forDivision($division)->create();
    $inDivision = Employee::factory()->forDepartment($department)->create(['name' => 'In First Division']);

    $otherDepartment = Department::factory()->create();
    $inOtherDepartment = Employee::factory()->forDepartment($otherDepartment)->create(['name' => 'In Other Department']);

    $this->get(route('employees.index', [
        'division_id' => $division->id,
        'department_id' => $otherDepartment->id,
    ]))
        ->assertOk()
        ->assertSee($inDivision->name)
        ->assertDontSee($inOtherDepartment->name);
});

test('employees index shows headcount summary cards', function () {
    $division = Division::factory()->create();
    $department = Department::factory()->forDivision($division)->create();

    Employee::factory()->forDepartment($department)->count(2)->create(['is_active' => true]);
    Employee::factory()->inactive()->create();

    $this->get(route('employees.index'))
        ->assertOk()
        ->assertViewHas('summary', fn (array $summary) => $summary['total'] === 3
            && $summary['active'] === 2
            && $summary['inactive'] === 1
            && $summary['divisions'] === 1
            && $summary['departments'] === 1);
});

test('employees index summary ignores the search and filters', function () {
    $division = Division::factory()->create();
    $department = Department::factory()->forDivision($division)->create();
    Employee::factory()->forDepartment($department)->create(['name' => 'Quixotic First']);
    Employee::factory()->create(['name' => 'Elsewhere Person']);

    $this->get(route('employees.index', ['search' => 'Quixotic First']))
        ->assertOk()
        ->assertViewHas('summary', fn (array $summary) => $summary['total'] === 2);

    $this->get(route('employees.index', ['division_id' => $division->id]))
        ->assertOk()
        ->assertViewHas('summary', fn (array $summary) => $summary['total'] === 2);
});

test('employees index excludes soft deleted employees from the summary', function () {
    Employee::factory()->create()->delete();

    $this->get(route('employees.index'))
        ->assertOk()
        ->assertViewHas('summary', fn (array $summary) => $summary['total'] === 0);
});

test('employees index links the status cards to the status filter', function () {
    $this->get(route('employees.index'))
        ->assertOk()
        // Use the default escaping: Blade renders & as &amp;, and assertSee escapes the expected
        // value the same way. Passing false would search for a raw & that is not in the markup.
        ->assertSee(route('employees.index', ['status' => 'active']))
        ->assertSee(route('employees.index', ['status' => 'inactive']))
        ->assertSee(__('View active employees'))
        ->assertSee(__('View inactive employees'));
});

test('employees index status link filters the table', function () {
    Employee::factory()->inactive()->create(['name' => 'Quixotic Inactive']);
    Employee::factory()->create(['name' => 'Quixotic Active']);

    $this->get(route('employees.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee('Quixotic Inactive')
        ->assertDontSee('Quixotic Active');
});

test('employees index status card link wins over an active placement filter', function () {
    $division = Division::factory()->create();
    $department = Department::factory()->forDivision($division)->create();
    Employee::factory()->forDepartment($department)->create(['name' => 'Quixotic In Division']);
    Employee::factory()->inactive()->create(['name' => 'Quixotic Inactive Elsewhere']);

    // The card drops the placement filter, so status is applied instead of being ignored.
    $this->get(route('employees.index', ['division_id' => $division->id]))
        ->assertOk()
        ->assertSee(route('employees.index', ['status' => 'inactive']))
        ->assertDontSee(route('employees.index', ['division_id' => $division->id, 'status' => 'inactive']));

    // Sorting and per-page survive the link; the placement filter does not.
    $this->get(route('employees.index', [
        'sort' => 'name',
        'direction' => 'desc',
        'per_page' => 50,
        'division_id' => $division->id,
    ]))
        ->assertOk()
        ->assertSee(route('employees.index', [
            'sort' => 'name',
            'direction' => 'desc',
            'per_page' => 50,
            'status' => 'inactive',
        ]));
});

test('admin can create an employee and the number is generated', function () {
    $this->post(route('employees.store'), employeePayload())
        ->assertRedirect(route('employees.index'));

    $employee = Employee::query()->sole();

    expect($employee->employee_number)->toBe('EMP-00001')
        ->and($employee->name)->toBe('Jane Candidate')
        ->and($employee->gender)->toBe('female')
        ->and($employee->join_date->toDateString())->toBe('2024-01-15')
        ->and($employee->is_active)->toBeTrue();
});

test('admin can create an employee with an explicit number', function () {
    $this->post(route('employees.store'), employeePayload(['employee_number' => 'EMP-CUSTOM-1']))
        ->assertRedirect(route('employees.index'));

    expect(Employee::query()->sole()->employee_number)->toBe('EMP-CUSTOM-1');
});

test('employee number and email must be unique', function () {
    $existing = Employee::factory()->create([
        'employee_number' => 'EMP-TAKEN',
        'email' => 'taken.employee@example.com',
    ]);

    $this->post(route('employees.store'), employeePayload([
        'employee_number' => $existing->employee_number,
        'email' => $existing->email,
    ]))->assertSessionHasErrors(['employee_number', 'email']);
});

test('employee can be linked to a user account only once', function () {
    $user = User::factory()->create();
    Employee::factory()->create(['user_id' => $user->id]);

    $this->post(route('employees.store'), employeePayload(['user_id' => $user->id]))
        ->assertSessionHasErrors('user_id');
});

test('org unit must belong to the selected department', function () {
    $payload = employeePayload();
    $otherDepartment = Department::factory()->create();
    $foreignUnit = OrgUnit::factory()->forDepartment($otherDepartment)->create();

    $this->post(route('employees.store'), array_merge($payload, [
        'org_unit_id' => $foreignUnit->id,
    ]))->assertSessionHasErrors('org_unit_id');
});

test('department must belong to the selected division', function () {
    $payload = employeePayload();
    $otherDivision = Division::factory()->create();
    $foreignDepartment = Department::factory()->forDivision($otherDivision)->create();

    $this->post(route('employees.store'), array_merge($payload, [
        'department_id' => $foreignDepartment->id,
    ]))->assertSessionHasErrors('department_id');
});

test('employee required fields are validated', function () {
    $this->post(route('employees.store'), [])
        ->assertSessionHasErrors(['name', 'gender', 'division_id', 'department_id', 'position_id', 'employment_status_id', 'join_date']);
});

test('admin can update an employee', function () {
    $employee = Employee::factory()->create();
    $division = Division::factory()->create();
    $department = Department::factory()->forDivision($division)->create();

    $this->put(route('employees.update', $employee), employeePayload([
        'division_id' => $division->id,
        'department_id' => $department->id,
        'name' => 'Renamed Employee',
        'is_active' => '0',
    ]))->assertRedirect(route('employees.index'));

    expect($employee->fresh()->name)->toBe('Renamed Employee')
        ->and($employee->fresh()->department_id)->toBe($department->id)
        ->and($employee->fresh()->is_active)->toBeFalse();
});

test('admin can show an employee profile', function () {
    $employee = Employee::factory()->create([
        'name' => 'Profile Person',
        'bank_name' => 'Bank Quixotic',
    ]);

    $this->get(route('employees.show', $employee))
        ->assertOk()
        ->assertSee('Profile Person')
        ->assertSee('Bank Quixotic');
});

test('admin can create an employee with a photo', function () {
    Storage::fake('public');

    $this->post(route('employees.store'), employeePayload(['photo' => fakeEmployeePhoto()]))
        ->assertRedirect(route('employees.index'));

    $employee = Employee::query()->sole();

    expect($employee->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($employee->photo_path);
});

test('updating an employee replaces the old photo', function () {
    Storage::fake('public');

    $employee = Employee::factory()->create([
        'photo_path' => fakeEmployeePhoto('old.png')->store('employees', 'public'),
    ]);

    $oldPath = $employee->photo_path;

    $this->put(route('employees.update', $employee), employeePayload([
        'photo' => fakeEmployeePhoto('new.png'),
    ]))->assertRedirect(route('employees.index'));

    $newPath = $employee->fresh()->photo_path;

    expect($newPath)->not->toBeNull()
        ->and($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertExists($newPath);
    Storage::disk('public')->assertMissing($oldPath);
});

test('admin can remove an employee photo', function () {
    Storage::fake('public');

    $employee = Employee::factory()->create([
        'photo_path' => fakeEmployeePhoto('remove.png')->store('employees', 'public'),
    ]);

    $oldPath = $employee->photo_path;

    $this->put(route('employees.update', $employee), employeePayload(['remove_photo' => '1']))
        ->assertRedirect(route('employees.index'));

    expect($employee->fresh()->photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($oldPath);
});

test('admin can soft delete an employee', function () {
    $employee = Employee::factory()->create(['name' => 'Doomed Employee']);

    $this->delete(route('employees.destroy', $employee))
        ->assertRedirect(route('employees.index'));

    $this->assertSoftDeleted($employee);

    $this->get(route('employees.index'))->assertDontSee('Doomed Employee');
});

test('admin can assign a direct manager on update', function () {
    $manager = Employee::factory()->create();
    $employee = Employee::factory()->create();

    $this->put(route('employees.update', $employee), employeePayload(['manager_id' => $manager->id]))
        ->assertRedirect(route('employees.index'));

    expect($employee->fresh()->manager_id)->toBe($manager->id);
});

test('an employee cannot be their own manager', function () {
    $employee = Employee::factory()->create();

    $this->put(route('employees.update', $employee), employeePayload(['manager_id' => $employee->id]))
        ->assertSessionHasErrors('manager_id');

    expect($employee->fresh()->manager_id)->toBeNull();
});

test('a manager assignment cannot create a circular reporting line', function () {
    $manager = Employee::factory()->create();
    $employee = Employee::factory()->reportsTo($manager)->create();

    $this->put(route('employees.update', $manager), employeePayload(['manager_id' => $employee->id]))
        ->assertSessionHasErrors('manager_id');

    expect($manager->fresh()->manager_id)->toBeNull()
        ->and($employee->fresh()->manager_id)->toBe($manager->id);
});
