<?php

namespace Database\Factories\Products;

use App\Models\Products\Product;
use App\Models\Products\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'product_category_id' => ProductCategory::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'price_cents' => fake()->numberBetween(300, 4500),
            'image_url' => null,
            'is_available' => true,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (): array => ['is_available' => false]);
    }
}
