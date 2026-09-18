<?php

namespace Database\Seeders;

use App\Enums\PermissionSlug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class PermissionSeeder extends Seeder
{
    /**
     * The definitive list of permissions, derived from the PermissionSlug enum.
     *
     * The enum is the single source of truth: adding a case here is all it takes
     * for a permission to exist, and the group and order come from the enum's own
     * methods. There is no second list to keep in step, so the enum and the
     * database cannot drift apart.
     *
     * @return array<int, array{slug: string, group: string, order: int}>
     */
    public static function getPermissions(): array
    {
        return array_map(
            fn (PermissionSlug $permission): array => [
                'slug' => $permission->value,
                'group' => $permission->group(),
                'order' => $permission->order(),
            ],
            PermissionSlug::cases(),
        );
    }

    /**
     * Seeding permissions is exactly what permissions:sync does, so it does it.
     */
    public function run(): void
    {
        Artisan::call('permissions:sync');

        $this->command?->getOutput()->write(Artisan::output());
    }
}
