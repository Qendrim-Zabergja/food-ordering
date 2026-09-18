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
    /**
     * name, price in cents, and an Unsplash photo id.
     *
     * Photos are referenced by id and assembled into a URL below, so the
     * sizing parameters live in one place instead of being repeated twenty
     * two times. Unsplash's licence allows this use, and their CDN is what
     * they document linking to.
     *
     * @var array<string, array<int, array{0: string, 1: int, 2: string}>>
     */
    protected array $menu = [
        'Pizza' => [
            ['Margherita', 850, '1680405620826-83b0f0f61b28'],
            ['Pepperoni', 1050, '1604068549290-dea0e4a305ca'],
            ['Quattro Formaggi', 1150, '1664309641932-0e03e0771b97'],
            ['Vegetariana', 950, '1573821663912-6df460f9c684'],
        ],
        'Burgers' => [
            ['Classic Cheeseburger', 900, '1568901346375-23c9450c58cd'],
            ['Double Bacon', 1250, '1572802419224-296b0aeee0d9'],
            ['Crispy Chicken', 950, '1586190848861-99aa4a171e90'],
            ['Veggie Burger', 875, '1607013251379-e6eecfffe234'],
        ],
        'Pasta' => [
            ['Spaghetti Bolognese', 1100, '1600803907087-f56d462fd26b'],
            ['Penne Arrabbiata', 950, '1516100882582-96c3a05fe590'],
            ['Fettuccine Alfredo', 1150, '1473093226795-af9932fe5856'],
        ],
        'Sides' => [
            ['French Fries', 350, '1639024471283-03518883512d'],
            ['Onion Rings', 400, '1652209911920-2700fcbd5011'],
            ['Garlic Bread', 375, '1619535860434-ba1d8fa12536'],
            ['Mixed Salad', 550, '1573140401552-3fab0b24306f'],
        ],
        'Drinks' => [
            ['Still Water', 200, '1600271886742-f049cd451bba'],
            ['Sparkling Water', 200, '1607690506833-498e04ab3ffa'],
            ['Cola', 250, '1583577612013-4fecf7bf8f13'],
            ['Fresh Orange Juice', 400, '1650292390827-51240d74eb0a'],
        ],
        'Desserts' => [
            ['Tiramisu', 600, '1599748724588-e98bf13d01f0'],
            ['Chocolate Brownie', 550, '1732869931523-8fd0437da0f1'],
            ['Ice Cream', 450, '1639744211487-b27e3551b07c'],
        ],
    ];

    protected static function photoUrl(string $photoId): string
    {
        return "https://images.unsplash.com/photo-{$photoId}?w=600&h=400&fit=crop&auto=format&q=70";
    }

    public function run(): void
    {
        $sortOrder = 0;

        foreach ($this->menu as $categoryName => $products) {
            $category = ProductCategory::firstOrCreate(
                ['name' => $categoryName],
                ['sort_order' => $sortOrder++, 'is_active' => true],
            );

            foreach ($products as [$name, $priceCents, $photoId]) {
                Product::firstOrCreate(
                    ['name' => $name],
                    [
                        'product_category_id' => $category->id,
                        'price_cents' => $priceCents,
                        'image_url' => self::photoUrl($photoId),
                        'is_available' => true,
                    ],
                );
            }
        }
    }
}
