<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Roles must exist before permissions are synced - permissions:sync grants
     * every permission to the roles listed in RoleSlug::fullAccess().
     */
    public function run(): void
    {
        foreach (RoleSlug::cases() as $role) {
            Role::updateOrCreate(
                ['slug' => $role->value],
                ['name' => $role->label()],
            );
        }
    }
}
