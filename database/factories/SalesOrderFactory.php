<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalesOrder> */
class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;
        $subtotal = fake()->randomFloat(2, 50000, 10000000);

        return [
            'document_number' => 'SO-'.now()->format('Ymd').'-'.str_pad((string) self::$counter, 4, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'order_date' => fake()->date(),
            'due_date' => fake()->optional()->date(),
            'status' => 'draft',
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax' => 0,
            'total' => $subtotal,
            'paid_amount' => 0,
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
