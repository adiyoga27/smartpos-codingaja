<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeliveryOrder> */
class DeliveryOrderFactory extends Factory
{
    protected $model = DeliveryOrder::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;

        return [
            'document_number' => 'DO-'.now()->format('Ymd').'-'.str_pad((string) self::$counter, 4, '0', STR_PAD_LEFT),
            'sales_order_id' => SalesOrder::factory(),
            'customer_id' => Customer::factory(),
            'delivery_date' => fake()->date(),
            'total' => fake()->randomFloat(2, 10000, 5000000),
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
