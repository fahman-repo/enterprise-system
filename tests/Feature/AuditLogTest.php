<?php

use App\Models\Activity;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

/**
 * @return array{0: User, 1: Role, 2: Menu}
 */
function actingAuditAdmin(): array
{
    $role = Role::factory()->create();
    $menus = collect(['users', 'roles', 'menus', 'audit-logs'])
        ->map(fn (string $slug) => Menu::factory()->create(['slug' => $slug]));

    $role->menus()->sync($menus->mapWithKeys(fn (Menu $menu) => [
        $menu->id => ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true],
    ]));

    app(PermissionService::class)->flush();

    return [
        User::factory()->create(['name' => 'Audit Admin', 'role_id' => $role->id]),
        $role,
        $menus->firstWhere('slug', 'audit-logs'),
    ];
}

function auditEntry(string $logName, string $event): Activity
{
    return Activity::query()
        ->where('log_name', $logName)
        ->where('event', $event)
        ->latest('id')
        ->sole();
}

/*
|--------------------------------------------------------------------------
| Model data changes
|--------------------------------------------------------------------------
*/

test('user create, update and delete are captured in the audit trail', function () {
    [$admin] = actingAuditAdmin();
    $this->actingAs($admin);

    $role = Role::factory()->create();
    User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'role_id' => $role->id,
    ]);

    $jane = User::query()->where('email', 'jane@example.com')->sole();

    $created = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'created')
        ->where('subject_id', $jane->id)
        ->sole();

    expect($created->description)->toBe('created')
        ->and($created->subject)->toBeInstanceOf(User::class)
        ->and($created->subject->email)->toBe('jane@example.com')
        ->and($created->causer?->is($admin))->toBeTrue()
        ->and($created->changes->get('attributes'))->toHaveKey('role_id')
        ->and($created->changes->get('attributes')['role_id'])->toBe($role->id);

    $jane->update(['name' => 'Jane Renamed']);

    $updated = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'updated')
        ->where('subject_id', $jane->id)
        ->sole();

    expect($updated->changes->get('attributes'))->toBe(['name' => 'Jane Renamed'])
        ->and($updated->changes->get('old'))->toBe(['name' => 'Jane Doe'])
        ->and($updated->getExtraProperty('causer.id'))->toBe($admin->id);

    $jane->delete();

    $deleted = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'deleted')
        ->where('subject_id', $jane->id)
        ->sole();

    expect($deleted->changes->get('old'))->toHaveKey('email');
});

test('passwords never appear in the audit trail and changes are flagged', function () {
    [$admin] = actingAuditAdmin();
    $this->actingAs($admin);

    $user = User::factory()->create(['password' => 'old-password']);
    $user->update(['password' => 'new-password']);

    $entry = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'updated')
        ->where('subject_id', $user->id)
        ->sole();

    expect($entry->properties->toJson())->not->toContain('new-password')
        ->and($entry->properties->toJson())->not->toContain('old-password')
        ->and($entry->getExtraProperty('password_changed'))->toBeTrue()
        ->and($entry->changesForDisplay())->toBe([]);
});

test('role permission matrix changes are logged with before and after snapshots', function () {
    [$admin] = actingAuditAdmin();
    $this->actingAs($admin);

    $usersMenu = Menu::query()->where('slug', 'users')->sole();
    $role = Role::factory()->create(['name' => 'Editor', 'slug' => 'editor']);

    $this->put(route('roles.update', $role), [
        'name' => 'Editor',
        'slug' => 'editor',
        'permissions' => [
            $usersMenu->id => ['can_view' => true, 'can_create' => true],
        ],
    ])->assertRedirect(route('roles.index'));

    $entry = Activity::query()
        ->where('log_name', 'role')
        ->where('event', 'updated')
        ->where('subject_id', $role->id)
        ->whereNotNull('properties->permissions_changed')
        ->sole();

    expect($entry->getExtraProperty('permissions_before'))->toBe([])
        ->and($entry->getExtraProperty('permissions_changed'))->toBeTrue()
        ->and($entry->getExtraProperty("permissions_after.{$usersMenu->id}"))->toBe([
            'view' => true,
            'create' => true,
            'update' => false,
            'delete' => false,
        ]);

    $this->put(route('roles.update', $role), [
        'name' => 'Editor',
        'slug' => 'editor',
        'permissions' => [],
    ])->assertRedirect(route('roles.index'));

    $removal = Activity::query()
        ->where('log_name', 'role')
        ->where('event', 'updated')
        ->where('subject_id', $role->id)
        ->whereNotNull('properties->permissions_changed')
        ->latest('id')
        ->firstOrFail();

    expect($removal->getExtraProperty("permissions_before.{$usersMenu->id}"))->toBe([
        'view' => true,
        'create' => true,
        'update' => false,
        'delete' => false,
    ])
        ->and($removal->getExtraProperty('permissions_after'))->toBe([]);
});

test('menu changes are captured in the audit trail', function () {
    [$admin] = actingAuditAdmin();
    $this->actingAs($admin);

    $this->post(route('menus.store'), [
        'name' => 'Reports',
        'slug' => 'reports',
        'is_active' => '1',
    ])->assertRedirect(route('menus.index'));

    $created = Activity::query()
        ->where('log_name', 'menu')
        ->where('event', 'created')
        ->where('subject_id', Menu::query()->where('slug', 'reports')->sole()->id)
        ->sole();

    expect($created->subject->slug)->toBe('reports')
        ->and($created->changes->get('attributes')['is_active'])->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Authentication events
|--------------------------------------------------------------------------
*/

test('successful login is captured in the audit trail', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $entry = auditEntry('auth', 'login');

    expect($entry->causer?->is($user))->toBeTrue()
        ->and($entry->getExtraProperty('email'))->toBe($user->email)
        ->and($entry->getExtraProperty('guard'))->toBe('web')
        ->and($entry->getExtraProperty('ip'))->not->toBeNull();
});

test('logout is captured in the audit trail', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect(route('login', absolute: false));

    $entry = auditEntry('auth', 'logout');

    expect($entry->causer?->is($user))->toBeTrue()
        ->and($entry->getExtraProperty('email'))->toBe($user->email);
});

test('failed logins are captured without exposing the password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $entry = auditEntry('auth', 'failed_login');

    expect($entry->causer_id)->toBeNull()
        ->and($entry->getExtraProperty('email'))->toBe($user->email)
        ->and($entry->properties->toJson())->not->toContain('wrong-password');
});

/*
|--------------------------------------------------------------------------
| Audit log UI
|--------------------------------------------------------------------------
*/

test('audit log index lists entries and shows change summaries', function () {
    [$admin] = actingAuditAdmin();
    $this->actingAs($admin);

    $user = User::factory()->create(['name' => 'Jane Doe']);
    $user->update(['name' => 'Jane Renamed']);

    $this->get(route('audit-logs.index'))
        ->assertOk()
        ->assertSee('Jane Renamed')
        ->assertSee('Audit Admin')
        ->assertSee('1 field changed');
});

test('audit log index search matches the actor and description', function () {
    [$admin] = actingAuditAdmin();
    $this->actingAs($admin);

    $user = User::factory()->create();
    $user->update(['name' => 'Quixotic Person']);

    $this->get(route('audit-logs.index', ['search' => 'quixotic']))
        ->assertOk()
        ->assertSee('Quixotic Person');

    $this->get(route('audit-logs.index', ['search' => 'Audit Admin']))
        ->assertOk()
        ->assertSee('Audit Admin');

    $this->get(route('audit-logs.index', ['search' => 'zzz-no-match']))
        ->assertOk()
        ->assertSee('No results match');
});

test('audit log index filters by event type', function () {
    [$admin] = actingAuditAdmin();
    $this->actingAs($admin);

    $other = User::factory()->create(['name' => 'Delete Target']);
    $other->delete();

    $this->get(route('audit-logs.index', ['event' => 'deleted']))
        ->assertOk()
        ->assertSee('Deleted');

    $response = $this->get(route('audit-logs.index', ['event' => 'login']));
    $response->assertOk();

    expect($response->getContent())->not->toContain('Delete Target');
});

test('audit log detail shows the before and after diff', function () {
    [$admin] = actingAuditAdmin();
    $this->actingAs($admin);

    $user = User::factory()->create(['name' => 'Jane Doe']);
    $user->update(['name' => 'Jane Renamed']);

    $entry = auditEntry('user', 'updated');

    $this->get(route('audit-logs.show', $entry))
        ->assertOk()
        ->assertSee('Jane Doe')
        ->assertSee('Jane Renamed')
        ->assertSee('Before')
        ->assertSee('After');
});

test('audit log detail renders role permission changes', function () {
    [$admin] = actingAuditAdmin();
    $this->actingAs($admin);

    $usersMenu = Menu::query()->where('slug', 'users')->sole();
    $role = Role::factory()->create(['name' => 'Editor', 'slug' => 'editor']);

    $role->withPermissions(['name' => 'Editor', 'slug' => 'editor'], [
        $usersMenu->id => ['can_view' => true, 'can_create' => true],
    ]);

    $entry = Activity::query()
        ->where('log_name', 'role')
        ->where('event', 'updated')
        ->where('subject_id', $role->id)
        ->whereNotNull('properties->permissions_changed')
        ->sole();

    $this->get(route('audit-logs.show', $entry))
        ->assertOk()
        ->assertSee('Permission matrix')
        ->assertSee($usersMenu->name)
        ->assertSee('view')
        ->assertSee('create');
});

test('audit logs require the audit logs view permission', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $entry = Activity::query()->create([
        'log_name' => 'user',
        'description' => 'created',
        'event' => 'created',
    ]);

    $this->get(route('audit-logs.index'))->assertForbidden();
    $this->get(route('audit-logs.show', $entry))->assertForbidden();
});
