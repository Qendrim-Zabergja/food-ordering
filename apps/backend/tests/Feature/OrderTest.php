<?php

use App\Enums\OrderStatus;
use App\Enums\RoleSlug;
use App\Models\Carts\CartItem;
use App\Models\Orders\Order;
use App\Models\Products\Product;
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

    $this->pizza = Product::factory()->create(['name' => 'Margherita', 'price_cents' => 850]);
    $this->fries = Product::factory()->create(['name' => 'Fries', 'price_cents' => 350]);

    $this->fillCart = function (?array $lines = null) {
        $lines ??= [[$this->pizza, 2], [$this->fries, 1]];

        foreach ($lines as [$product, $quantity]) {
            $this->postJson('/api/cart/items', [
                'product' => ['id' => $product->uuid],
                'quantity' => $quantity,
            ])->assertCreated();
        }
    };
});

describe('checkout', function () {
    beforeEach(function () {
        Sanctum::actingAs($this->customer);
    });

    it('turns the cart into an order', function () {
        ($this->fillCart)();

        $response = $this->postJson('/api/orders', [
            'delivery_address' => 'Rruga B, Prishtina',
            'phone' => '+383 44 123 456',
            'notes' => 'Ring twice',
        ])->assertCreated();

        expect($response->json('status'))->toBe('pending')
            ->and($response->json('total_cents'))->toBe(2050)
            ->and($response->json('total'))->toBe(20.5)
            ->and($response->json('items'))->toHaveCount(2)
            ->and($response->json('order_number'))->toStartWith('ORD-');
    });

    it('empties the cart afterwards', function () {
        ($this->fillCart)();

        $this->postJson('/api/orders', [
            'delivery_address' => 'Rruga B',
            'phone' => '+383 44 123 456',
        ])->assertCreated();

        expect(CartItem::count())->toBe(0);

        $this->getJson('/api/cart')->assertOk()->assertJsonPath('items_count', 0);
    });

    it('captures the name and price onto each line', function () {
        ($this->fillCart)([[$this->pizza, 2]]);

        $this->postJson('/api/orders', [
            'delivery_address' => 'Rruga B',
            'phone' => '+383 44 123 456',
        ])
            ->assertCreated()
            ->assertJsonPath('items.0.product_name', 'Margherita')
            ->assertJsonPath('items.0.unit_price_cents', 850)
            ->assertJsonPath('items.0.quantity', 2)
            ->assertJsonPath('items.0.line_total_cents', 1700);
    });

    it('does not rewrite an order when the product is later repriced or renamed', function () {
        ($this->fillCart)([[$this->pizza, 2]]);

        $order = $this->postJson('/api/orders', [
            'delivery_address' => 'Rruga B',
            'phone' => '+383 44 123 456',
        ])->json('id');

        $this->pizza->update(['name' => 'Margherita Special', 'price_cents' => 1200]);

        $this->getJson("/api/orders/{$order}")
            ->assertOk()
            ->assertJsonPath('items.0.product_name', 'Margherita')
            ->assertJsonPath('items.0.unit_price_cents', 850)
            ->assertJsonPath('total_cents', 1700);
    });

    it('keeps the line after the product is deleted from the menu', function () {
        ($this->fillCart)([[$this->pizza, 1]]);

        $order = $this->postJson('/api/orders', [
            'delivery_address' => 'Rruga B',
            'phone' => '+383 44 123 456',
        ])->json('id');

        $this->pizza->forceDelete();

        $this->getJson("/api/orders/{$order}")
            ->assertOk()
            ->assertJsonPath('items.0.product_name', 'Margherita')
            ->assertJsonPath('items.0.unit_price_cents', 850);
    });

    it('refuses to check out an empty cart', function () {
        $this->postJson('/api/orders', [
            'delivery_address' => 'Rruga B',
            'phone' => '+383 44 123 456',
        ])->assertStatus(422)->assertJsonValidationErrors('cart');

        expect(Order::count())->toBe(0);
    });

    it('refuses when something in the cart sold out while it sat there', function () {
        ($this->fillCart)([[$this->pizza, 1]]);

        $this->pizza->update(['is_available' => false]);

        $this->postJson('/api/orders', [
            'delivery_address' => 'Rruga B',
            'phone' => '+383 44 123 456',
        ])->assertStatus(422)->assertJsonValidationErrors('cart');

        expect(Order::count())->toBe(0)
            ->and(CartItem::count())->toBe(1);
    });

    it('requires delivery details', function () {
        ($this->fillCart)();

        $this->postJson('/api/orders', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['delivery_address', 'phone']);
    });

    it('ignores a total sent by the client', function () {
        ($this->fillCart)([[$this->pizza, 1]]);

        $this->postJson('/api/orders', [
            'delivery_address' => 'Rruga B',
            'phone' => '+383 44 123 456',
            'total_cents' => 1,
            'total' => 0.01,
            'status' => 'completed',
        ])
            ->assertCreated()
            ->assertJsonPath('total_cents', 850)
            ->assertJsonPath('status', 'pending');
    });

    it('gives each order its own number', function () {
        ($this->fillCart)([[$this->pizza, 1]]);
        $first = $this->postJson('/api/orders', ['delivery_address' => 'A', 'phone' => '1'])->json('order_number');

        ($this->fillCart)([[$this->pizza, 1]]);
        $second = $this->postJson('/api/orders', ['delivery_address' => 'A', 'phone' => '1'])->json('order_number');

        expect($first)->not->toBe($second);
    });
});

describe('order history', function () {
    it('shows a customer only their own orders', function () {
        $mine = Order::factory()->create(['user_id' => $this->customer->id]);
        $theirs = Order::factory()->create();

        Sanctum::actingAs($this->customer);

        $response = $this->getJson('/api/orders')->assertOk();

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.id'))->toBe($mine->uuid);
    });

    it('shows an administrator every order', function () {
        Order::factory()->count(3)->create();

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/orders')->assertOk()->assertJsonCount(3, 'data');
    });

    it('forbids reading someone else\'s order directly', function () {
        $theirs = Order::factory()->create();

        Sanctum::actingAs($this->customer);

        $this->getJson("/api/orders/{$theirs->uuid}")->assertForbidden();
    });

    it('lets an administrator read any order', function () {
        $order = Order::factory()->create();

        Sanctum::actingAs($this->admin);

        $this->getJson("/api/orders/{$order->uuid}")->assertOk();
    });

    it('filters by status', function () {
        Order::factory()->count(2)->create(['user_id' => $this->customer->id]);
        Order::factory()->status(OrderStatus::COMPLETED)->create(['user_id' => $this->customer->id]);

        Sanctum::actingAs($this->customer);

        $this->getJson('/api/orders?filter[status]=completed')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('returns nothing for an unrecognised status rather than everything', function () {
        Order::factory()->count(3)->create(['user_id' => $this->customer->id]);

        Sanctum::actingAs($this->customer);

        $this->getJson('/api/orders?filter[status]=not-a-status')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });
});

describe('status transitions', function () {
    beforeEach(function () {
        $this->order = Order::factory()->create(['user_id' => $this->customer->id]);
    });

    it('moves an order forward through its lifecycle', function () {
        Sanctum::actingAs($this->admin);

        foreach (['confirmed', 'preparing', 'delivering', 'completed'] as $status) {
            $this->patchJson("/api/orders/{$this->order->uuid}/status", ['status' => $status])
                ->assertOk()
                ->assertJsonPath('status', $status);
        }

        expect($this->order->fresh()->status)->toBe(OrderStatus::COMPLETED);
    });

    it('refuses to skip backwards', function () {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/orders/{$this->order->uuid}/status", ['status' => 'delivering'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        expect($this->order->fresh()->status)->toBe(OrderStatus::PENDING);
    });

    it('refuses to change a completed order', function () {
        $this->order->update(['status' => OrderStatus::COMPLETED]);

        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/orders/{$this->order->uuid}/status", ['status' => 'preparing'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    });

    it('rejects a status that is not a real one', function () {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/orders/{$this->order->uuid}/status", ['status' => 'teleporting'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    });

    it('forbids a customer from changing status', function () {
        Sanctum::actingAs($this->customer);

        $this->patchJson("/api/orders/{$this->order->uuid}/status", ['status' => 'completed'])
            ->assertForbidden();

        expect($this->order->fresh()->status)->toBe(OrderStatus::PENDING);
    });

    it('tells the client which transitions are available', function () {
        Sanctum::actingAs($this->admin);

        $this->getJson("/api/orders/{$this->order->uuid}")
            ->assertOk()
            ->assertJsonPath('allowed_transitions', ['confirmed', 'preparing', 'cancelled'])
            ->assertJsonPath('is_final', false);
    });
});

describe('cancelling', function () {
    it('lets a customer cancel their own pending order', function () {
        $order = Order::factory()->create(['user_id' => $this->customer->id]);

        Sanctum::actingAs($this->customer);

        $this->patchJson("/api/orders/{$order->uuid}/cancel")
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');
    });

    it('stops a customer cancelling once the kitchen has started', function () {
        $order = Order::factory()->status(OrderStatus::PREPARING)->create(['user_id' => $this->customer->id]);

        Sanctum::actingAs($this->customer);

        $this->patchJson("/api/orders/{$order->uuid}/cancel")->assertForbidden();

        expect($order->fresh()->status)->toBe(OrderStatus::PREPARING);
    });

    it('lets an administrator cancel an order that is already being prepared', function () {
        $order = Order::factory()->status(OrderStatus::PREPARING)->create(['user_id' => $this->customer->id]);

        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/orders/{$order->uuid}/cancel")
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');
    });

    it('forbids cancelling someone else\'s order', function () {
        $order = Order::factory()->create();

        Sanctum::actingAs($this->customer);

        $this->patchJson("/api/orders/{$order->uuid}/cancel")->assertForbidden();
    });

    it('refuses to cancel a completed order even for an administrator', function () {
        $order = Order::factory()->status(OrderStatus::COMPLETED)->create();

        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/orders/{$order->uuid}/cancel")
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    });
});

describe('lifecycle visibility', function () {
    it('sends the whole pipeline with the current position marked', function () {
        $order = Order::factory()->status(OrderStatus::PREPARING)->create(['user_id' => $this->customer->id]);

        Sanctum::actingAs($this->customer);

        $response = $this->getJson("/api/orders/{$order->uuid}")->assertOk();

        expect($response->json('step'))->toBe(3)
            ->and($response->json('total_steps'))->toBe(5)
            ->and(array_column($response->json('progress'), 'status'))
            ->toBe(['pending', 'confirmed', 'preparing', 'delivering', 'completed'])
            ->and(array_column($response->json('progress'), 'reached'))
            ->toBe([true, true, true, false, false])
            ->and(array_column($response->json('progress'), 'current'))
            ->toBe([false, false, true, false, false]);
    });

    it('treats cancelled as off the pipeline, not the end of it', function () {
        $order = Order::factory()->status(OrderStatus::CANCELLED)->create(['user_id' => $this->customer->id]);

        Sanctum::actingAs($this->customer);

        $response = $this->getJson("/api/orders/{$order->uuid}")->assertOk();

        expect($response->json('step'))->toBeNull()
            ->and(array_column($response->json('progress'), 'reached'))
            ->toBe([false, false, false, false, false]);
    });

    it('marks every stage reached once an order is completed', function () {
        $order = Order::factory()->status(OrderStatus::COMPLETED)->create(['user_id' => $this->customer->id]);

        Sanctum::actingAs($this->customer);

        $response = $this->getJson("/api/orders/{$order->uuid}")->assertOk();

        expect($response->json('step'))->toBe(5)
            ->and(array_column($response->json('progress'), 'reached'))
            ->toBe([true, true, true, true, true]);
    });
});
