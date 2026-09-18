<?php

use App\Enums\RoleSlug;
use App\Models\Products\Product;
use App\Models\Products\ProductCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->artisan('permissions:sync');
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleSlug::ADMIN);

    $this->customer = User::factory()->create();
    $this->customer->assignRole(RoleSlug::CUSTOMER);
});

describe('authentication', function () {
    it('rejects an unauthenticated request', function () {
        $this->getJson('/api/products')->assertUnauthorized();
        $this->getJson('/api/product-categories')->assertUnauthorized();
    });
});

describe('reading the catalogue', function () {
    it('lets a customer list products', function () {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(3)->create(['product_category_id' => $category->id]);

        Sanctum::actingAs($this->customer);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('exposes the uuid as id and never the numeric id', function () {
        $product = Product::factory()->create();

        Sanctum::actingAs($this->customer);

        $response = $this->getJson("/api/products/{$product->uuid}")->assertOk();

        expect($response->json('id'))->toBe($product->uuid)
            ->and($response->json('id'))->not->toBe($product->id)
            ->and($response->json())->not->toHaveKey('product_category_id');
    });

    it('resolves route binding on uuid, so a numeric id is a 404', function () {
        $product = Product::factory()->create();

        Sanctum::actingAs($this->customer);

        $this->getJson("/api/products/{$product->id}")->assertNotFound();
    });

    it('returns the price in both major units and cents', function () {
        $product = Product::factory()->create(['price_cents' => 1250]);

        Sanctum::actingAs($this->customer);

        $this->getJson("/api/products/{$product->uuid}")
            ->assertOk()
            ->assertJsonPath('price', 12.5)
            ->assertJsonPath('price_cents', 1250);
    });

    it('loads a relationship only when asked through with', function () {
        $product = Product::factory()->create();

        Sanctum::actingAs($this->customer);

        expect($this->getJson("/api/products/{$product->uuid}")->json('category'))->toBeNull();

        $this->getJson("/api/products/{$product->uuid}?with[]=category")
            ->assertOk()
            ->assertJsonPath('category.id', $product->category->uuid);
    });
});

describe('filtering', function () {
    beforeEach(function () {
        Sanctum::actingAs($this->customer);
    });

    it('searches by name', function () {
        Product::factory()->create(['name' => 'Margherita Pizza']);
        Product::factory()->create(['name' => 'Cheeseburger']);

        $this->getJson('/api/products?filter[search]=margherita')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Margherita Pizza');
    });

    it('filters by category uuid', function () {
        $pizza = ProductCategory::factory()->create(['name' => 'Pizza']);
        $drinks = ProductCategory::factory()->create(['name' => 'Drinks']);

        Product::factory()->count(2)->create(['product_category_id' => $pizza->id]);
        Product::factory()->create(['product_category_id' => $drinks->id]);

        $this->getJson("/api/products?filter[category]={$pizza->uuid}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters on availability', function () {
        Product::factory()->count(2)->create();
        Product::factory()->unavailable()->create();

        $this->getJson('/api/products?filter[is_available]=false')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('filters on a price range, converting to cents', function () {
        Product::factory()->create(['price_cents' => 500]);
        Product::factory()->create(['price_cents' => 1500]);
        Product::factory()->create(['price_cents' => 2500]);

        $this->getJson('/api/products?filter[price_from]=10&filter[price_to]=20')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('ignores an unknown filter rather than widening the result', function () {
        Product::factory()->count(2)->create();

        $this->getJson('/api/products?filter[nonsense]=whatever')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('sorts by a column, descending with a ! prefix', function () {
        Product::factory()->create(['name' => 'Aubergine']);
        Product::factory()->create(['name' => 'Zucchini']);

        expect($this->getJson('/api/products?sort=name')->json('data.0.name'))->toBe('Aubergine')
            ->and($this->getJson('/api/products?sort=!name')->json('data.0.name'))->toBe('Zucchini');
    });
});

describe('managing the catalogue', function () {
    it('forbids a customer from creating a product', function () {
        $category = ProductCategory::factory()->create();

        Sanctum::actingAs($this->customer);

        $this->postJson('/api/products', [
            'category' => ['id' => $category->uuid],
            'name' => 'Sneaky Pizza',
            'price' => 9.5,
        ])->assertForbidden();

        expect(Product::count())->toBe(0);
    });

    it('lets an admin create a product, converting price to cents', function () {
        $category = ProductCategory::factory()->create();

        Sanctum::actingAs($this->admin);

        $this->postJson('/api/products', [
            'category' => ['id' => $category->uuid],
            'name' => 'Margherita',
            'description' => 'Tomato, mozzarella, basil',
            'price' => 8.5,
        ])
            ->assertCreated()
            ->assertJsonPath('name', 'Margherita')
            ->assertJsonPath('price_cents', 850);

        expect(Product::first()->product_category_id)->toBe($category->id);
    });

    it('rejects a product with no name or price', function () {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/products', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'category.id']);
    });

    it('rejects a category uuid that does not exist', function () {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/products', [
            'category' => ['id' => '00000000-0000-0000-0000-000000000000'],
            'name' => 'Orphan',
            'price' => 5,
        ])->assertStatus(422)->assertJsonValidationErrors(['category.id']);
    });

    it('lets an admin patch a single field without clearing the rest', function () {
        $product = Product::factory()->create(['name' => 'Old', 'description' => 'Keep me']);

        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/products/{$product->uuid}", ['name' => 'New'])
            ->assertOk()
            ->assertJsonPath('name', 'New')
            ->assertJsonPath('description', 'Keep me');
    });

    it('soft deletes and restores a product', function () {
        $product = Product::factory()->create();

        Sanctum::actingAs($this->admin);

        $this->deleteJson("/api/products/{$product->uuid}")->assertNoContent();

        expect(Product::count())->toBe(0)
            ->and(Product::withTrashed()->count())->toBe(1);

        $this->patchJson("/api/products/{$product->uuid}/restore")->assertOk();

        expect(Product::count())->toBe(1);
    });

    it('forbids a customer from deleting a product', function () {
        $product = Product::factory()->create();

        Sanctum::actingAs($this->customer);

        $this->deleteJson("/api/products/{$product->uuid}")->assertForbidden();

        expect(Product::count())->toBe(1);
    });

    it('ignores a uuid supplied in a create payload', function () {
        $category = ProductCategory::factory()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/products', [
            'category' => ['id' => $category->uuid],
            'name' => 'Chosen uuid',
            'price' => 5,
            'uuid' => '12345678-1234-1234-1234-123456789012',
        ])->assertCreated();

        expect($response->json('id'))->not->toBe('12345678-1234-1234-1234-123456789012');
    });
});

describe('categories', function () {
    it('counts products without loading them', function () {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(3)->create(['product_category_id' => $category->id]);

        Sanctum::actingAs($this->customer);

        $this->getJson('/api/product-categories')
            ->assertOk()
            ->assertJsonPath('data.0.products_count', 3);
    });

    it('lets an admin create a category and a customer not', function () {
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/product-categories', ['name' => 'Pizza'])->assertCreated();

        Sanctum::actingAs($this->customer);
        $this->postJson('/api/product-categories', ['name' => 'Nope'])->assertForbidden();

        expect(ProductCategory::count())->toBe(1);
    });
});
