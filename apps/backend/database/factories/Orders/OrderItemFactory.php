<?php

namespace Database\Factories\Orders;

use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $unitPriceCents = fake()->numberBetween(300, 2500);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(2, true),
            'unit_price_cents' => $unitPriceCents,
            'quantity' => $quantity,
            'line_total_cents' => $unitPriceCents * $quantity,
        ];
    }
}
