<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;

        return [
            'code' => 'PRD-'.str_pad((string) self::$counter, 5, '0', STR_PAD_LEFT),
            'name' => fake()->unique()->words(2, true),
            'category_id' => Category::factory(),
            'supplier_id' => Supplier::factory(),
            'barcode' => fake()->unique()->ean13(),
            'unit' => 'PCS',
            'purchase_unit' => 'PCS',
            'conversion_factor' => 1,
            'purchase_price' => fake()->randomFloat(2, 1000, 50000),
            'selling_price' => fake()->randomFloat(2, 2000, 100000),
            'wholesale_price' => fake()->randomFloat(2, 1500, 75000),
            'stock' => fake()->randomFloat(2, 10, 500),
            'min_stock' => 10,
            'max_stock' => 1000,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 5,
            'min_stock' => 10,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
