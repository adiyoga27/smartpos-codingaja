<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseItem> */
class PurchaseItemFactory extends Factory
{
    protected $model = PurchaseItem::class;

    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 1, 50);
        $price = fake()->randomFloat(2, 1000, 50000);
        $discount = fake()->randomFloat(2, 0, 5000);

        return [
            'purchase_id' => Purchase::factory(),
            'product_id' => Product::factory(),
            'quantity' => $qty,
            'received_quantity' => 0,
            'unit_price' => $price,
            'discount' => $discount,
            'total' => max(0, ($qty * $price) - $discount),
        ];
    }
}
