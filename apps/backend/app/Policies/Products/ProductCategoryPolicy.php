<?php

namespace App\Policies\Products;

use App\Enums\PermissionSlug;
use App\Models\Products\ProductCategory;
use App\Models\User;

/**
 * Reading the catalogue is public - see ProductCategoryController, where index
 * and show are the only endpoints outside the authenticated route group. These
 * checks therefore guard the admin side only.
 */
class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissions([PermissionSlug::VIEW_PRODUCT_CATEGORIES]);
    }

    public function view(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermissions([PermissionSlug::VIEW_PRODUCT_CATEGORIES]);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissions([PermissionSlug::MANAGE_PRODUCT_CATEGORIES]);
    }

    public function update(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermissions([PermissionSlug::MANAGE_PRODUCT_CATEGORIES]);
    }

    public function delete(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermissions([PermissionSlug::MANAGE_PRODUCT_CATEGORIES]);
    }

    public function restore(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermissions([PermissionSlug::MANAGE_PRODUCT_CATEGORIES]);
    }
}
