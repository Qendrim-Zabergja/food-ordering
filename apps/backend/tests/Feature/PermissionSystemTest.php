<?php

use App\Enums\PermissionSlug;
use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('HasUuid', function () {
    it('assigns a uuid when a model is created', function () {
        $user = User::factory()->create();

        expect($user->uuid)->not->toBeEmpty()
            ->and($user->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-/i');
    });

    it('does not overwrite a uuid that was set deliberately', function () {
        // uuid is not fillable on purpose - a client must never be able to choose
        // one - so a seeder or import assigns it explicitly before saving.
        $role = new Role(['slug' => 'fixed', 'name' => 'Fixed']);
        $role->uuid = '11111111-2222-3333-4444-555555555555';
        $role->save();

        expect($role->fresh()->uuid)->toBe('11111111-2222-3333-4444-555555555555');
    });

    it('ignores a uuid passed through mass assignment', function () {
        $role = Role::create([
            'uuid' => '99999999-9999-9999-9999-999999999999',
            'slug' => 'guarded',
            'name' => 'Guarded',
        ]);

        expect($role->uuid)->not->toBe('99999999-9999-9999-9999-999999999999');
    });

    it('resolves route model binding on uuid, never the numeric id', function () {
        expect((new Role)->getRouteKeyName())->toBe('uuid');
    });
});

describe('permissions:sync', function () {
    it('creates a row for every case in the PermissionSlug enum', function () {
        $this->artisan('permissions:sync')->assertSuccessful();

        expect(Permission::pluck('slug')->sort()->values()->all())
            ->toBe(collect(PermissionSlug::values())->sort()->values()->all());
    });

    it('is safe to run twice', function () {
        $this->artisan('permissions:sync')->assertSuccessful();
        $this->artisan('permissions:sync')->assertSuccessful();

        expect(Permission::count())->toBe(count(PermissionSlug::cases()));
    });

    it('prunes a permission that no longer exists in the enum and detaches it', function () {
        $this->artisan('permissions:sync');

        $orphan = Permission::create(['slug' => 'legacy-permission', 'group' => 'legacy', 'order' => 1]);
        $role = Role::where('slug', RoleSlug::ADMIN->value)->first();
        $role->permissions()->attach($orphan->id);

        $this->artisan('permissions:sync')->assertSuccessful();

        expect(Permission::where('slug', 'legacy-permission')->exists())->toBeFalse()
            ->and($role->fresh()->permissions->pluck('slug'))->not->toContain('legacy-permission');
    });

    it('writes nothing on a dry run', function () {
        $this->artisan('permissions:sync --dry-run')->assertSuccessful();

        expect(Permission::count())->toBe(0);
    });

    it('grants every permission to the admin role', function () {
        $this->artisan('permissions:sync');

        $admin = Role::where('slug', RoleSlug::ADMIN->value)->first();

        expect($admin->permissions)->toHaveCount(count(PermissionSlug::cases()));
    });

    it('grants nothing to the customer role', function () {
        $this->artisan('permissions:sync');

        $customer = Role::where('slug', RoleSlug::CUSTOMER->value)->first();

        expect($customer->permissions)->toBeEmpty();
    });
});

describe('User authorization', function () {
    beforeEach(function () {
        $this->artisan('permissions:sync');
    });

    it('grants an admin every permission', function () {
        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::ADMIN);

        expect($admin->hasPermissions([PermissionSlug::MANAGE_PRODUCTS]))->toBeTrue()
            ->and($admin->hasPermissions([PermissionSlug::VIEW_ORDERS, PermissionSlug::MANAGE_ORDERS]))->toBeTrue();
    });

    it('denies a customer any managed permission', function () {
        $customer = User::factory()->create();
        $customer->assignRole(RoleSlug::CUSTOMER);

        expect($customer->hasPermissions([PermissionSlug::MANAGE_PRODUCTS]))->toBeFalse()
            ->and($customer->hasPermissions([PermissionSlug::VIEW_PRODUCTS]))->toBeFalse();
    });

    it('accepts a raw slug as well as an enum case', function () {
        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::ADMIN);

        expect($admin->hasPermissions(['view-products']))->toBeTrue()
            ->and($admin->hasPermissions(PermissionSlug::VIEW_PRODUCTS))->toBeTrue();
    });

    it('does not let two narrow roles add up to a broad one', function () {
        $viewer = Role::create(['slug' => 'viewer', 'name' => 'Viewer']);
        $editor = Role::create(['slug' => 'editor', 'name' => 'Editor']);

        $viewer->permissions()->attach(Permission::where('slug', PermissionSlug::VIEW_PRODUCTS->value)->first());
        $editor->permissions()->attach(Permission::where('slug', PermissionSlug::MANAGE_PRODUCTS->value)->first());

        $user = User::factory()->create();
        $user->roles()->attach([$viewer->id, $editor->id]);

        expect($user->hasPermissions([PermissionSlug::VIEW_PRODUCTS]))->toBeTrue()
            ->and($user->hasPermissions([PermissionSlug::MANAGE_PRODUCTS]))->toBeTrue()
            ->and($user->hasPermissions([PermissionSlug::VIEW_PRODUCTS, PermissionSlug::MANAGE_PRODUCTS]))->toBeFalse();
    });

    it('denies a user with no roles', function () {
        expect(User::factory()->create()->hasPermissions([PermissionSlug::VIEW_PRODUCTS]))->toBeFalse();
    });

    it('reports role membership', function () {
        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::ADMIN);

        expect($admin->hasRoles(RoleSlug::ADMIN))->toBeTrue()
            ->and($admin->hasRoles(RoleSlug::CUSTOMER))->toBeFalse();
    });

    it('does not duplicate a role that is assigned twice', function () {
        $user = User::factory()->create();
        $user->assignRole(RoleSlug::CUSTOMER);
        $user->assignRole(RoleSlug::CUSTOMER);

        expect($user->fresh()->roles)->toHaveCount(1);
    });
});
