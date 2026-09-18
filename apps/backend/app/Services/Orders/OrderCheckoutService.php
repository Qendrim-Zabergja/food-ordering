<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Carts\Cart;
use App\Models\Carts\CartItem;
use App\Models\Orders\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns a cart into an order.
 *
 * Checkout changes three things: it writes an order, it copies the cart's lines
 * onto it, and it empties the cart. Each is a method here rather than one long
 * block, so the next variant of this action - an admin placing an order for a
 * customer, a repeat of a previous order - can reuse the parts it needs.
 */
class OrderCheckoutService
{
    /**
     * @param  array{delivery_address: string, phone: string, notes?: string|null}  $details
     */
    public function placeOrder(User $user, array $details): Order
    {
        $cart = Cart::forUser($user)->load(['items.product']);

        $this->assertCartIsOrderable($cart);

        // One transaction: an order without its lines, or a cart emptied without
        // an order to show for it, would both be worse than a failed checkout.
        return DB::transaction(function () use ($user, $cart, $details): Order {
            $order = $this->createOrder($user, $cart, $details);

            $this->copyItems($order, $cart);
            $this->clearCart($cart);

            return $order->load('items');
        });
    }

    /**
     * Refuses a cart that cannot become an order.
     *
     * Availability is re-checked here and not only when the item was added: a
     * product can sell out while it sits in someone's cart, and the moment that
     * matters is the moment they commit to buying it.
     */
    public function assertCartIsOrderable(Cart $cart): void
    {
        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $unavailable = $cart->items
            ->filter(fn (CartItem $item): bool => ! $item->product || ! $item->product->is_available)
            ->map(fn (CartItem $item): string => $item->product?->name ?? 'A removed product')
            ->values();

        if ($unavailable->isNotEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'No longer available: '.$unavailable->implode(', ').'. Please remove it and try again.',
            ]);
        }
    }

    /**
     * @param  array{delivery_address: string, phone: string, notes?: string|null}  $details
     */
    public function createOrder(User $user, Cart $cart, array $details): Order
    {
        return Order::create([
            'order_number' => Order::nextOrderNumber(),
            'user_id' => $user->id,

            // Set explicitly rather than left to the column default: a default
            // is applied by the database, so the model returned by create()
            // would carry a null status until it was read back.
            'status' => OrderStatus::PENDING,

            'total_cents' => $cart->subtotalCents(),
            'delivery_address' => $details['delivery_address'],
            'phone' => $details['phone'],
            'notes' => $details['notes'] ?? null,
            'placed_at' => now(),
        ]);
    }

    /**
     * Copies the cart's lines onto the order, capturing each product's name and
     * price as they are right now. This is the single moment a price is fixed.
     */
    public function copyItems(Order $order, Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $order->items()->create([
                'product_id' => $item->product->id,
                'product_name' => $item->product->name,
                'unit_price_cents' => $item->product->price_cents,
                'quantity' => $item->quantity,
                'line_total_cents' => $item->lineTotalCents(),
            ]);
        }
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
    }
}
