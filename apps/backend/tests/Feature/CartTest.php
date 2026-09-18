<?php

use App\Enums\RoleSlug;
use App\Models\Carts\Cart;
use App\Models\Carts\CartItem;
use App\Models\Products\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->artisan('permissions:sync');
    $this->seed(RolePermissionSeeder::class);

    $this->customer = User::factory()->create();
    $this->customer->assignRole(RoleSlug::CUSTOMER);

    $this->product = Product::factory()->create(['price_cents' => 850]);
});

describe('access', function () {
    it('rejects an unauthenticated request', function () {
        $this->getJson('/api/cart')->assertUnauthorized();
        $this->postJson('/api/cart/items', [])->assertUnauthorized();
    });

    it('creates the cart on first use', function () {
        Sanctum::actingAs($this->customer);

        expect(Cart::count())->toBe(0);

        $this->getJson('/api/cart')->assertOk()->assertJsonPath('items_count', 0);

        expect(Cart::count())->toBe(1);
    });

    it('gives each user their own cart', function () {
        $other = User::factory()->create();
        $other->assignRole(RoleSlug::CUSTOMER);

        Sanctum::actingAs($this->customer);
        $mine = $this->getJson('/api/cart')->json('id');

        Sanctum::actingAs($other);
        $theirs = $this->getJson('/api/cart')->json('id');

        expect($mine)->not->toBe($theirs);
    });
});

describe('adding items', function () {
    beforeEach(function () {
        Sanctum::actingAs($this->customer);
    });

    it('adds a product and prices it from the product row', function () {
        $this->postJson('/api/cart/items', [
            'product' => ['id' => $this->product->uuid],
            'quantity' => 2,
        ])
            ->assertCreated()
            ->assertJsonPath('items_count', 2)
            ->assertJsonPath('items.0.unit_price_cents', 850)
            ->assertJsonPath('items.0.line_total_cents', 1700)
            ->assertJsonPath('subtotal_cents', 1700)
            // 17.0 serialises as 17 - JSON cannot carry the distinction, which
            // is exactly why subtotal_cents is the authoritative figure.
            ->assertJsonPath('subtotal', 17);
    });

    it('ignores a price sent by the client', function () {
        $this->postJson('/api/cart/items', [
            'product' => ['id' => $this->product->uuid],
            'quantity' => 1,
            'price' => 0.01,
            'price_cents' => 1,
            'unit_price_cents' => 1,
        ])
            ->assertCreated()
            ->assertJsonPath('items.0.unit_price_cents', 850)
            ->assertJsonPath('subtotal_cents', 850);
    });

    it('defaults the quantity to one', function () {
        $this->postJson('/api/cart/items', ['product' => ['id' => $this->product->uuid]])
            ->assertCreated()
            ->assertJsonPath('items_count', 1);
    });

    it('raises the quantity instead of adding a second line', function () {
        $payload = ['product' => ['id' => $this->product->uuid], 'quantity' => 2];

        $this->postJson('/api/cart/items', $payload)->assertCreated();
        $this->postJson('/api/cart/items', $payload)
            ->assertCreated()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.quantity', 4);

        expect(CartItem::count())->toBe(1);
    });

    it('refuses a product that is not available', function () {
        $unavailable = Product::factory()->unavailable()->create();

        $this->postJson('/api/cart/items', ['product' => ['id' => $unavailable->uuid]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product.id');

        expect(CartItem::count())->toBe(0);
    });

    it('refuses a product that does not exist', function () {
        $this->postJson('/api/cart/items', [
            'product' => ['id' => '00000000-0000-0000-0000-000000000000'],
        ])->assertStatus(422)->assertJsonValidationErrors('product.id');
    });

    it('refuses a quantity below one or above ninety-nine', function () {
        $this->postJson('/api/cart/items', [
            'product' => ['id' => $this->product->uuid],
            'quantity' => 0,
        ])->assertStatus(422)->assertJsonValidationErrors('quantity');

        $this->postJson('/api/cart/items', [
            'product' => ['id' => $this->product->uuid],
            'quantity' => 100,
        ])->assertStatus(422)->assertJsonValidationErrors('quantity');
    });
});

describe('changing quantity', function () {
    beforeEach(function () {
        Sanctum::actingAs($this->customer);

        $this->postJson('/api/cart/items', [
            'product' => ['id' => $this->product->uuid],
            'quantity' => 3,
        ]);

        $this->item = CartItem::first();
    });

    it('updates the quantity and recalculates the subtotal', function () {
        $this->patchJson("/api/cart/items/{$this->item->uuid}", ['quantity' => 5])
            ->assertOk()
            ->assertJsonPath('items.0.quantity', 5)
            ->assertJsonPath('subtotal_cents', 4250);
    });

    it('rejects a quantity of zero, since removing a line is a DELETE', function () {
        $this->patchJson("/api/cart/items/{$this->item->uuid}", ['quantity' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');

        expect($this->item->fresh()->quantity)->toBe(3);
    });

    it('removes an item', function () {
        $this->deleteJson("/api/cart/items/{$this->item->uuid}")
            ->assertOk()
            ->assertJsonPath('items_count', 0)
            ->assertJsonPath('subtotal_cents', 0);

        expect(CartItem::count())->toBe(0);
    });

    it('empties the whole cart but keeps it', function () {
        $second = Product::factory()->create();
        $this->postJson('/api/cart/items', ['product' => ['id' => $second->uuid]]);

        $this->deleteJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('items_count', 0);

        expect(CartItem::count())->toBe(0)
            ->and(Cart::count())->toBe(1);
    });
});

describe('ownership', function () {
    beforeEach(function () {
        $this->intruder = User::factory()->create();
        $this->intruder->assignRole(RoleSlug::CUSTOMER);

        Sanctum::actingAs($this->customer);
        $this->postJson('/api/cart/items', ['product' => ['id' => $this->product->uuid]]);
        $this->item = CartItem::first();
    });

    it('forbids changing someone else\'s cart item', function () {
        Sanctum::actingAs($this->intruder);

        $this->patchJson("/api/cart/items/{$this->item->uuid}", ['quantity' => 99])
            ->assertForbidden();

        expect($this->item->fresh()->quantity)->toBe(1);
    });

    it('forbids deleting someone else\'s cart item', function () {
        Sanctum::actingAs($this->intruder);

        $this->deleteJson("/api/cart/items/{$this->item->uuid}")->assertForbidden();

        expect(CartItem::count())->toBe(1);
    });

    it('does not leak another user\'s items into your cart', function () {
        Sanctum::actingAs($this->intruder);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonCount(0, 'items')
            ->assertJsonPath('subtotal_cents', 0);
    });
});

describe('pricing', function () {
    it('follows the product price when it changes', function () {
        Sanctum::actingAs($this->customer);

        $this->postJson('/api/cart/items', [
            'product' => ['id' => $this->product->uuid],
            'quantity' => 2,
        ])->assertJsonPath('subtotal_cents', 1700);

        $this->product->update(['price_cents' => 900]);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('items.0.unit_price_cents', 900)
            ->assertJsonPath('subtotal_cents', 1800);
    });

    it('totals a mixed cart correctly', function () {
        Sanctum::actingAs($this->customer);

        $fries = Product::factory()->create(['price_cents' => 350]);

        $this->postJson('/api/cart/items', ['product' => ['id' => $this->product->uuid], 'quantity' => 2]);
        $this->postJson('/api/cart/items', ['product' => ['id' => $fries->uuid], 'quantity' => 3]);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('items_count', 5)
            ->assertJsonPath('subtotal_cents', 2750)
            ->assertJsonPath('subtotal', 27.5);
    });
});
