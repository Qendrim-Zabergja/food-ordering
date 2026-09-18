<?php

namespace App\Models;

use App\Enums\PermissionSlug;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A named bundle of permissions. Users hold roles; roles hold permissions.
 *
 * Authorization decisions are always made on permissions, never on a role slug.
 * RoleSlug exists for seeding and assignment only.
 */
class Role extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = ['slug', 'name'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function hasPermission(PermissionSlug|string $permission): bool
    {
        $slug = $permission instanceof PermissionSlug ? $permission->value : $permission;

        return $this->permissions->contains('slug', $slug);
    }
}
