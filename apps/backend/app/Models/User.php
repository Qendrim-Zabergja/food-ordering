<?php

namespace App\Models;

use App\Enums\PermissionSlug;
use App\Enums\RoleSlug;
use App\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuid, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * True when at least one of the user's roles carries every permission given.
     *
     * All of the permissions must come from the same role. A user who holds
     * "view" through one role and "manage" through another has not been granted
     * the combination, and granting it implicitly would let two narrow roles add
     * up to a broad one.
     *
     * @param  array<int, PermissionSlug|string>|PermissionSlug|string  $permissions
     */
    public function hasPermissions(array|PermissionSlug|string $permissions): bool
    {
        $required = collect(is_array($permissions) ? $permissions : [$permissions])
            ->map(fn (PermissionSlug|string $permission) => $permission instanceof PermissionSlug
                ? $permission->value
                : $permission)
            ->all();

        if (empty($required)) {
            return false;
        }

        return $this->roles
            ->loadMissing('permissions')
            ->contains(function (Role $role) use ($required): bool {
                $granted = $role->permissions->pluck('slug')->all();

                return empty(array_diff($required, $granted));
            });
    }

    /**
     * @param  array<int, RoleSlug|string>|RoleSlug|string  $roles
     */
    public function hasRoles(array|RoleSlug|string $roles): bool
    {
        $wanted = collect(is_array($roles) ? $roles : [$roles])
            ->map(fn (RoleSlug|string $role) => $role instanceof RoleSlug ? $role->value : $role)
            ->all();

        return $this->roles->pluck('slug')->intersect($wanted)->isNotEmpty();
    }

    public function assignRole(RoleSlug|Role $role): void
    {
        $role = $role instanceof Role
            ? $role
            : Role::where('slug', $role->value)->firstOrFail();

        $this->roles()->syncWithoutDetaching([$role->id]);
        $this->unsetRelation('roles');
    }
}
