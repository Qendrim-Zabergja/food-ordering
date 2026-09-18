<?php

namespace Database\Factories\Orders;

use App\Enums\OrderStatus;
use App\Models\Orders\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'user_id' => User::factory(),
            'status' => OrderStatus::PENDING,
            'total_cents' => fake()->numberBetween(500, 8000),
            'delivery_address' => fake()->streetAddress(),
            'phone' => fake()->numerify('+383 4# ### ###'),
            'notes' => null,
            'placed_at' => now(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
