<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SaleItem> */
class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 1, 10);
        $price = fake()->randomFloat(2, 1000, 50000);
        $discount = fake()->randomFloat(2, 0, 5000);

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'quantity' => $qty,
            'unit_price' => $price,
            'discount' => $discount,
            'total' => max(0, ($qty * $price) - $discount),
        ];
    }
}
