<?php

use App\Models\Entity;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

function actingEntityMasterAdmin(): User
{
    $role = Role::factory()->create();
    $menu = Menu::factory()->create(['slug' => 'entities']);

    $role->menus()->sync([
        $menu->id => ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true],
    ]);

    app(PermissionService::class)->flush();

    return User::factory()->create(['role_id' => $role->id]);
}

beforeEach(function () {
    $this->admin = actingEntityMasterAdmin();
    $this->actingAs($this->admin);
});

test('entities index lists entities', function () {
    $entity = Entity::factory()->create(['name' => 'Acme Supply']);

    $this->get(route('entities.index'))
        ->assertOk()
        ->assertSee('Acme Supply')
        ->assertSee($entity->code);
});

test('entities index search matches code, name, email, phone, npwp and identity number', function () {
    $byCode = Entity::factory()->create(['code' => 'ENT-99999']);
    $byName = Entity::factory()->create(['name' => 'Quixotic Entity']);
    $byEmail = Entity::factory()->create(['email' => 'zephyr.contact@example.test']);
    $byPhone = Entity::factory()->create(['phone' => '08999888777']);
    $byNpwp = Entity::factory()->create(['npwp' => '12.345.678.9-012.345']);
    $byIdentity = Entity::factory()->personal()->create(['identity_number' => '3201445566778899']);
    $unmatched = Entity::factory()->create(['name' => 'Nothing Here']);

    $this->get(route('entities.index', ['search' => 'ENT-99999']))
        ->assertOk()
        ->assertSee($byCode->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('entities.index', ['search' => 'quixotic']))
        ->assertOk()
        ->assertSee($byName->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('entities.index', ['search' => 'zephyr.contact']))
        ->assertOk()
        ->assertSee($byEmail->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('entities.index', ['search' => '08999888777']))
        ->assertOk()
        ->assertSee($byPhone->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('entities.index', ['search' => '345.678']))
        ->assertOk()
        ->assertSee($byNpwp->name)
        ->assertDontSee($unmatched->name);

    $this->get(route('entities.index', ['search' => '445566778899']))
        ->assertOk()
        ->assertSee($byIdentity->name)
        ->assertDontSee($unmatched->name);
});

test('entities index filters by role, type and status', function () {
    $vendor = Entity::factory()->vendor()->create(['name' => 'Vendor Co']);
    $customer = Entity::factory()->customer()->create(['name' => 'Customer Co']);
    $both = Entity::factory()->vendorAndCustomer()->create(['name' => 'Both Co']);
    $personal = Entity::factory()->personal()->customer()->create(['name' => 'Solo Person']);
    $firm = Entity::factory()->company()->create(['name' => 'Firm Co']);
    $inactive = Entity::factory()->inactive()->create(['name' => 'Gone Co']);

    $this->get(route('entities.index', ['role' => 'vendor']))
        ->assertOk()
        ->assertSee($vendor->name)
        ->assertDontSee($customer->name)
        ->assertDontSee($both->name);

    $this->get(route('entities.index', ['type' => 'personal']))
        ->assertOk()
        ->assertSee($personal->name)
        ->assertDontSee($firm->name);

    $this->get(route('entities.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee($inactive->name)
        ->assertDontSee($vendor->name)
        ->assertDontSee($customer->name);

    $this->get(route('entities.index', ['role' => 'vendor', 'type' => 'personal']))
        ->assertOk()
        ->assertSee($vendor->name)
        ->assertDontSee($personal->name);
});

test('entities index sorts by a column', function () {
    Entity::factory()->create(['name' => 'Alpha Entity', 'role' => 'vendor']);
    Entity::factory()->create(['name' => 'Zulu Entity', 'role' => 'customer']);

    $this->get(route('entities.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder(['Zulu Entity', 'Alpha Entity']);

    $this->get(route('entities.index', ['sort' => 'role', 'direction' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder(['Alpha Entity', 'Zulu Entity']);
});

test('entities index falls back to default order for unknown sort', function () {
    Entity::factory()->create(['name' => 'Zulu Entity']);
    Entity::factory()->create(['name' => 'Alpha Entity']);

    $this->get(route('entities.index', ['sort' => 'evil', 'direction' => 'drop table']))
        ->assertOk()
        ->assertSeeInOrder(['Alpha Entity', 'Zulu Entity']);
});

test('entities index shows summary cards', function () {
    Entity::factory()->vendor()->create();
    Entity::factory()->vendor()->inactive()->create();
    Entity::factory()->customer()->create();
    Entity::factory()->vendorAndCustomer()->create();
    Entity::factory()->create()->delete();

    $this->get(route('entities.index'))
        ->assertOk()
        ->assertViewHas('summary', fn (array $summary) => $summary['total'] === 4
            && $summary['active'] === 3
            && $summary['inactive'] === 1
            && $summary['vendors'] === 2
            && $summary['customers'] === 1);
});

test('entities index summary ignores the search and filters', function () {
    Entity::factory()->vendor()->create(['name' => 'Quixotic Vendor']);
    Entity::factory()->customer()->create(['name' => 'Quixotic Customer']);

    $this->get(route('entities.index', ['search' => 'Quixotic Vendor']))
        ->assertOk()
        ->assertViewHas('summary', fn (array $summary) => $summary['total'] === 2);

    $this->get(route('entities.index', ['role' => 'vendor']))
        ->assertOk()
        ->assertViewHas('summary', fn (array $summary) => $summary['total'] === 2
            && $summary['customers'] === 1);
});

test('entities index links the summary cards to their filters', function () {
    $this->get(route('entities.index'))
        ->assertOk()
        ->assertSee(route('entities.index', ['status' => 'active']))
        ->assertSee(route('entities.index', ['status' => 'inactive']))
        ->assertSee(route('entities.index', ['role' => 'vendor']))
        ->assertSee(route('entities.index', ['role' => 'customer']))
        ->assertSee(__('View active entities'))
        ->assertSee(__('View inactive entities'))
        ->assertSee(__('View vendors'))
        ->assertSee(__('View customers'));
});

test('admin can create an entity with a generated code', function () {
    $this->post(route('entities.store'), [
        'name' => 'PT Contoh',
        'type' => 'company',
        'role' => 'vendor',
    ])->assertRedirect(route('entities.index'));

    $entity = Entity::query()->sole();

    expect($entity->code)->toMatch('/^ENT-\d{5}$/')
        ->and($entity->name)->toBe('PT Contoh');
});

test('admin can create an entity with a manual code', function () {
    $this->post(route('entities.store'), [
        'code' => 'V-100',
        'name' => 'Manual Code Co',
        'type' => 'company',
        'role' => 'vendor',
    ])->assertRedirect(route('entities.index'));

    $this->assertDatabaseHas('entities', ['code' => 'V-100']);
});

test('entity code and identity number must be unique', function () {
    $existing = Entity::factory()->personal()->create();

    $this->post(route('entities.store'), [
        'code' => $existing->code,
        'name' => 'Duplicate Code',
        'type' => 'company',
        'role' => 'vendor',
    ])->assertSessionHasErrors(['code']);

    $this->post(route('entities.store'), [
        'name' => 'Duplicate Identity',
        'type' => 'personal',
        'role' => 'customer',
        'identity_number' => $existing->identity_number,
    ])->assertSessionHasErrors(['identity_number']);
});

test('entity name, type and role are required and constrained', function () {
    $this->post(route('entities.store'), [])
        ->assertSessionHasErrors(['name', 'type', 'role']);

    $this->post(route('entities.store'), [
        'name' => 'X',
        'type' => 'NGO',
        'role' => 'supplier',
    ])->assertSessionHasErrors(['type', 'role']);
});

test('admin can update an entity and a blank code keeps the existing code', function () {
    $entity = Entity::factory()->create(['code' => 'ENT-00001']);

    $this->put(route('entities.update', $entity), [
        'code' => '',
        'name' => 'Renamed',
        'type' => 'company',
        'role' => 'vendor',
    ])->assertRedirect(route('entities.index'));

    $entity->refresh();

    expect($entity->name)->toBe('Renamed')
        ->and($entity->code)->toBe('ENT-00001');
});

test('admin can soft delete an entity', function () {
    $entity = Entity::factory()->create(['name' => 'Doomed Entity']);

    $this->delete(route('entities.destroy', $entity))
        ->assertRedirect(route('entities.index'));

    $this->assertSoftDeleted('entities', ['id' => $entity->id]);

    $this->get(route('entities.index'))->assertDontSee('Doomed Entity');
});

test('entities show renders entity details', function () {
    $entity = Entity::factory()->create([
        'npwp' => '12.345.678.9-012.345',
        'email' => 'detail@example.test',
    ]);

    $this->get(route('entities.show', $entity))
        ->assertOk()
        ->assertSee($entity->name)
        ->assertSee($entity->code)
        ->assertSee($entity->npwp)
        ->assertSee($entity->email);
});
