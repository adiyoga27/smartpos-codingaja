<?php

namespace Database\Factories;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Product;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeliveryOrderItem> */
class DeliveryOrderItemFactory extends Factory
{
    protected $model = DeliveryOrderItem::class;

    public function definition(): array
    {
        return [
            'delivery_order_id' => DeliveryOrder::factory(),
            'sales_order_item_id' => SalesOrderItem::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->randomFloat(2, 1, 20),
        ];
    }
}
