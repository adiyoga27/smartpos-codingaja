<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalesOrderItem> */
class SalesOrderItemFactory extends Factory
{
    protected $model = SalesOrderItem::class;

    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 1, 50);
        $price = fake()->randomFloat(2, 1000, 50000);
        $discount = fake()->randomFloat(2, 0, 5000);

        return [
            'sales_order_id' => SalesOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => $qty,
            'delivered_quantity' => 0,
            'unit_price' => $price,
            'discount' => $discount,
            'total' => max(0, ($qty * $price) - $discount),
        ];
    }
}
