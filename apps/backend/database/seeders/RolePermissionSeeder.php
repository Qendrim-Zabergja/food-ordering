<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Applies RoleSlug::defaultPermissions() to each role.
 *
 * Runs after PermissionSeeder, because the permission rows have to exist before
 * they can be attached. Roles in RoleSlug::fullAccess() are skipped - they are
 * granted everything by permissions:sync.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleSlug::cases() as $roleSlug) {
            $defaults = $roleSlug->defaultPermissions();

            if ($defaults === []) {
                continue;
            }

            $role = Role::where('slug', $roleSlug->value)->first();

            if (! $role) {
                continue;
            }

            $ids = Permission::whereIn('slug', array_map(fn ($permission) => $permission->value, $defaults))
                ->pluck('id');

            $role->permissions()->syncWithoutDetaching($ids->all());
        }
    }
}
