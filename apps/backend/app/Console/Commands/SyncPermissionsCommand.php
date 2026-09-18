<?php

namespace App\Console\Commands;

use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Illuminate\Console\Command;

/**
 * Reconciles the permissions table with the PermissionSlug enum.
 *
 * Run this after every deploy that adds, renames or removes a permission. It is
 * the replacement for re-running seeders by hand, and it is safe to run twice.
 */
class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync {--dry-run : Report what would change without writing anything}';

    protected $description = 'Sync the permissions table with the PermissionSlug enum';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $defined = collect(PermissionSeeder::getPermissions())->keyBy('slug');
        $existing = Permission::all()->keyBy('slug');

        $created = [];
        $updated = [];
        $pruned = [];

        foreach ($defined as $slug => $attributes) {
            $permission = $existing->get($slug);

            if (! $permission) {
                $created[] = $slug;

                if (! $dryRun) {
                    Permission::create($attributes);
                }

                continue;
            }

            $changes = array_diff_assoc(
                ['group' => $attributes['group'], 'order' => $attributes['order']],
                ['group' => $permission->group, 'order' => $permission->order],
            );

            if ($changes !== []) {
                $updated[] = $slug;

                if (! $dryRun) {
                    $permission->update($changes);
                }
            }
        }

        foreach ($existing as $slug => $permission) {
            if ($defined->has($slug)) {
                continue;
            }

            $pruned[] = $slug;

            if (! $dryRun) {
                // Detaching first keeps roles from holding a permission that no
                // longer exists in code.
                $permission->roles()->detach();
                $permission->delete();
            }
        }

        $granted = $this->grantToFullAccessRoles($dryRun);

        $this->report($created, $updated, $pruned, $granted, $dryRun);

        return self::SUCCESS;
    }

    /**
     * Roles in RoleSlug::fullAccess() always hold every permission, so a new
     * permission never has to be assigned to an administrator by hand.
     *
     * @return array<string, int> role slug => number of permissions added
     */
    protected function grantToFullAccessRoles(bool $dryRun): array
    {
        $slugs = array_map(fn (RoleSlug $role): string => $role->value, RoleSlug::fullAccess());

        $permissionIds = $dryRun
            ? collect(PermissionSeeder::getPermissions())->pluck('slug')
            : Permission::pluck('id');

        $granted = [];

        foreach (Role::whereIn('slug', $slugs)->get() as $role) {
            if ($dryRun) {
                $held = $role->permissions->pluck('slug');
                $granted[$role->slug] = $permissionIds->diff($held)->count();

                continue;
            }

            $missing = $permissionIds->diff($role->permissions->pluck('id'));
            $granted[$role->slug] = $missing->count();

            if ($missing->isNotEmpty()) {
                $role->permissions()->syncWithoutDetaching($missing->all());
            }
        }

        return $granted;
    }

    /**
     * @param  array<int, string>  $created
     * @param  array<int, string>  $updated
     * @param  array<int, string>  $pruned
     * @param  array<string, int>  $granted
     */
    protected function report(array $created, array $updated, array $pruned, array $granted, bool $dryRun): void
    {
        if ($dryRun) {
            $this->components->warn('Dry run - nothing was written.');
        }

        foreach (['Created' => $created, 'Updated' => $updated, 'Pruned' => $pruned] as $label => $slugs) {
            foreach ($slugs as $slug) {
                $this->components->twoColumnDetail($slug, $label);
            }
        }

        foreach ($granted as $role => $count) {
            if ($count > 0) {
                $this->components->twoColumnDetail($role, "+{$count} permissions");
            }
        }

        if ($created === [] && $updated === [] && $pruned === [] && array_sum($granted) === 0) {
            $this->components->info('Permissions are already in sync.');

            return;
        }

        $this->components->info(sprintf(
            '%d created, %d updated, %d pruned.',
            count($created),
            count($updated),
            count($pruned),
        ));
    }
}
