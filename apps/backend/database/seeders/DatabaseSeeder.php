<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Note: no WithoutModelEvents here, deliberately. The HasUuid trait assigns
     * every model's uuid on the `creating` event, so muting model events during
     * seeding would insert rows with no uuid and fail on the not-null column.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@food-ordering.test'],
            ['name' => 'Administrator', 'password' => Hash::make('password')],
        );

        $admin->assignRole(RoleSlug::ADMIN);

        $customer = User::firstOrCreate(
            ['email' => 'customer@food-ordering.test'],
            ['name' => 'Demo Customer', 'password' => Hash::make('password')],
        );

        $customer->assignRole(RoleSlug::CUSTOMER);
    }
}
