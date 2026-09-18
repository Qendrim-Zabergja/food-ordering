<?php

namespace App\Policies\Products;

use App\Enums\PermissionSlug;
use App\Models\Products\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissions([PermissionSlug::VIEW_PRODUCTS]);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermissions([PermissionSlug::VIEW_PRODUCTS]);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissions([PermissionSlug::MANAGE_PRODUCTS]);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermissions([PermissionSlug::MANAGE_PRODUCTS]);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermissions([PermissionSlug::MANAGE_PRODUCTS]);
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->hasPermissions([PermissionSlug::MANAGE_PRODUCTS]);
    }
}
