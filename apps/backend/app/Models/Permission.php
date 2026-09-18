<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * The database row backing a PermissionSlug case.
 *
 * Permissions are defined in code, not by users: the PermissionSlug enum is the
 * source of truth and `php artisan permissions:sync` reconciles this table with
 * it. That is also why this model has no SoftDeletes - a permission removed from
 * the enum is pruned outright, and a soft-deleted row would collide with the
 * unique slug if it were ever reintroduced.
 */
class Permission extends Model
{
    use HasUuid;

    protected $fillable = ['slug', 'group', 'order'];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
