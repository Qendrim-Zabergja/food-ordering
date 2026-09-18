<?php

use App\Enums\RoleSlug;
use App\Models\Products\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->artisan('permissions:sync');
    $this->seed(RolePermissionSeeder::class);
});

describe('registration', function () {
    it('creates a customer and returns a usable token', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Arta Krasniqi',
            'email' => 'arta@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ])->assertCreated();

        expect($response->json('token'))->not->toBeEmpty()
            ->and($response->json('user.id'))->toBe(User::first()->uuid)
            ->and($response->json('user.roles'))->toBe(['customer'])
            ->and($response->json('user'))->not->toHaveKey('password');
    });

    it('gives the new customer catalogue read permissions', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Arta',
            'email' => 'arta@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ]);

        expect($response->json('user.permissions'))
            ->toContain('view-products')
            ->toContain('view-product-categories')
            ->not->toContain('manage-products');
    });

    it('never lets the payload choose a role', function () {
        $this->postJson('/api/auth/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'role' => 'admin',
            'roles' => ['admin'],
        ])->assertCreated();

        expect(User::first()->hasRoles(RoleSlug::ADMIN))->toBeFalse()
            ->and(User::first()->hasRoles(RoleSlug::CUSTOMER))->toBeTrue();
    });

    it('hashes the password', function () {
        $this->postJson('/api/auth/register', [
            'name' => 'Arta',
            'email' => 'arta@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ]);

        $stored = User::first()->password;

        expect($stored)->not->toBe('correct-horse-battery')
            ->and(Hash::check('correct-horse-battery', $stored))->toBeTrue();
    });

    it('rejects a duplicate email', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Arta',
            'email' => 'taken@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    });

    it('rejects a password that is not confirmed', function () {
        $this->postJson('/api/auth/register', [
            'name' => 'Arta',
            'email' => 'arta@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'something-else',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    });

    it('rejects missing fields', function () {
        $this->postJson('/api/auth/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });
});

describe('login', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'email' => 'arta@example.com',
            'password' => Hash::make('correct-horse-battery'),
        ]);
        $this->user->assignRole(RoleSlug::CUSTOMER);
    });

    it('returns a token for valid credentials', function () {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'arta@example.com',
            'password' => 'correct-horse-battery',
        ])->assertOk();

        expect($response->json('token'))->not->toBeEmpty()
            ->and($response->json('user.id'))->toBe($this->user->uuid);
    });

    it('rejects a wrong password', function () {
        $this->postJson('/api/auth/login', [
            'email' => 'arta@example.com',
            'password' => 'wrong',
        ])->assertStatus(422)->assertJsonValidationErrors('email');

        expect(PersonalAccessToken::count())->toBe(0);
    });

    it('gives the same message for an unknown email as for a wrong password', function () {
        $unknown = $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ])->assertStatus(422);

        $wrongPassword = $this->postJson('/api/auth/login', [
            'email' => 'arta@example.com',
            'password' => 'wrong',
        ])->assertStatus(422);

        // Identical responses, so the endpoint cannot be used to find out which
        // addresses have accounts.
        expect($unknown->json('errors'))->toBe($wrongPassword->json('errors'));
    });

    it('names the token after the device when one is given', function () {
        $this->postJson('/api/auth/login', [
            'email' => 'arta@example.com',
            'password' => 'correct-horse-battery',
            'device_name' => 'Arta iPhone',
        ])->assertOk();

        expect(PersonalAccessToken::first()->name)->toBe('Arta iPhone');
    });
});

describe('the token actually works', function () {
    beforeEach(function () {
        $this->token = $this->postJson('/api/auth/register', [
            'name' => 'Arta',
            'email' => 'arta@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ])->json('token');
    });

    it('authenticates a real request end to end', function () {
        Product::factory()->count(2)->create();

        // No actingAs here - this is the bearer token the API just issued,
        // sent the way the React client will send it.
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('returns the current user from auth/me', function () {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('email', 'arta@example.com');
    });

    it('stops working after logout', function () {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        expect(PersonalAccessToken::count())->toBe(0);

        // The guard memoizes the resolved user for the lifetime of the
        // container, and the container is not rebuilt between requests within
        // one test. A real second request would be a fresh process.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    });

    it('leaves other devices signed in after one logs out', function () {
        $second = $this->postJson('/api/auth/login', [
            'email' => 'arta@example.com',
            'password' => 'correct-horse-battery',
            'device_name' => 'tablet',
        ])->json('token');

        $this->withHeader('Authorization', "Bearer {$this->token}")->postJson('/api/auth/logout');

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$second}")
            ->getJson('/api/auth/me')
            ->assertOk();
    });

    it('returns 401 JSON to a client that did not ask for JSON', function () {
        // A browser or curl sends Accept: */*, not application/json. Without an
        // explicit guest handler the framework would try to redirect to a login
        // route that does not exist in an API-only app, and answer 500.
        $this->get('/api/products', ['Accept' => '*/*'])
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    });

    it('rejects a made-up token', function () {
        $this->withHeader('Authorization', 'Bearer 1|totallymadeuptokenvalue')
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    });
});

describe('the seeded admin', function () {
    it('can log in and holds every permission', function () {
        $this->seed(DatabaseSeeder::class);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@food-ordering.test',
            'password' => 'password',
        ])->assertOk();

        expect($response->json('user.roles'))->toBe(['admin'])
            ->and($response->json('user.permissions'))->toContain('manage-products');
    });
});
