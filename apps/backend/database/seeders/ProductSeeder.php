<?php

namespace Database\Seeders;

use App\Models\Products\Product;
use App\Models\Products\ProductCategory;
use Illuminate\Database\Seeder;

/**
 * A small, realistic menu so the frontend has something to render and the admin
 * panel has something to manage.
 */
class ProductSeeder extends Seeder
{
    /**
     * @var array<string, array<int, array{0: string, 1: int}>>
     */
    protected array $menu = [
        'Pizza' => [
            ['Margherita', 850],
            ['Pepperoni', 1050],
            ['Quattro Formaggi', 1150],
            ['Vegetariana', 950],
        ],
        'Burgers' => [
            ['Classic Cheeseburger', 900],
            ['Double Bacon', 1250],
            ['Crispy Chicken', 950],
            ['Veggie Burger', 875],
        ],
        'Pasta' => [
            ['Spaghetti Bolognese', 1100],
            ['Penne Arrabbiata', 950],
            ['Fettuccine Alfredo', 1150],
        ],
        'Sides' => [
            ['French Fries', 350],
            ['Onion Rings', 400],
            ['Garlic Bread', 375],
            ['Mixed Salad', 550],
        ],
        'Drinks' => [
            ['Still Water', 200],
            ['Sparkling Water', 200],
            ['Cola', 250],
            ['Fresh Orange Juice', 400],
        ],
        'Desserts' => [
            ['Tiramisu', 600],
            ['Chocolate Brownie', 550],
            ['Ice Cream', 450],
        ],
    ];

    public function run(): void
    {
        $sortOrder = 0;

        foreach ($this->menu as $categoryName => $products) {
            $category = ProductCategory::firstOrCreate(
                ['name' => $categoryName],
                ['sort_order' => $sortOrder++, 'is_active' => true],
            );

            foreach ($products as [$name, $priceCents]) {
                Product::firstOrCreate(
                    ['name' => $name],
                    [
                        'product_category_id' => $category->id,
                        'price_cents' => $priceCents,
                        'is_available' => true,
                    ],
                );
            }
        }
    }
}
