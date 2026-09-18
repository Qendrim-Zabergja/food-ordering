<?php

namespace App\Enums;

/**
 * The roles a user can hold. Role slugs are never hardcoded in business logic -
 * authorization decisions are made on permissions, not on roles. This enum
 * exists for seeding and for assigning a role to a user.
 */
enum RoleSlug: string
{
    case ADMIN = 'admin';
    case CUSTOMER = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::CUSTOMER => 'Customer',
        };
    }

    /**
     * Roles that receive every permission automatically when
     * `php artisan permissions:sync` runs.
     *
     * @return array<int, self>
     */
    public static function fullAccess(): array
    {
        return [self::ADMIN];
    }
}
