<?php

namespace App\Enums;

/**
 * Every permission that exists in the system.
 *
 * Permission strings are never written as raw literals anywhere else - policies,
 * seeders, tests and controllers all reference a case on this enum. If a
 * permission is not here, it does not exist.
 *
 * Two tiers are used in this project:
 *
 *   VIEW_*    full read access to the resource and its detail endpoint
 *   MANAGE_*  create, update and delete
 *
 * MANAGE never implies VIEW. A user needs VIEW to reach a resource at all and
 * MANAGE in addition to change it, so the two are assigned together for write
 * access. (This project has no BROWSE tier - see CLAUDE.md, deviation 2.)
 */
enum PermissionSlug: string
{
    // Products
    case VIEW_PRODUCTS = 'view-products';
    case MANAGE_PRODUCTS = 'manage-products';

    // Product categories
    case VIEW_PRODUCT_CATEGORIES = 'view-product-categories';
    case MANAGE_PRODUCT_CATEGORIES = 'manage-product-categories';

    // Orders
    case VIEW_ORDERS = 'view-orders';
    case MANAGE_ORDERS = 'manage-orders';

    /**
     * The group this permission is listed under on the roles screen.
     */
    public function group(): string
    {
        return match ($this) {
            self::VIEW_PRODUCTS,
            self::MANAGE_PRODUCTS,
            self::VIEW_PRODUCT_CATEGORIES,
            self::MANAGE_PRODUCT_CATEGORIES => 'products',

            self::VIEW_ORDERS,
            self::MANAGE_ORDERS => 'orders',
        };
    }

    /**
     * Display order within the group: VIEW first, then MANAGE.
     */
    public function order(): int
    {
        return str_starts_with($this->value, 'view-') ? 1 : 2;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
