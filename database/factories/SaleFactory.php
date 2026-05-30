<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sale> */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;
        $subtotal = fake()->randomFloat(2, 5000, 5000000);
        $tax = fake()->randomFloat(2, 0, $subtotal * 0.11);

        return [
            'invoice_number' => 'INV-'.now()->format('Ymd').'-'.str_pad((string) self::$counter, 5, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'customer_name' => null,
            'sale_date' => fake()->date(),
            'due_date' => null,
            'payment_method_id' => PaymentMethod::factory()->cash(),
            'tax_id' => null,
            'tax_amount' => $tax,
            'cash_account_id' => null,
            'status' => 'paid',
            'subtotal' => $subtotal,
            'item_discount' => 0,
            'total_discount' => 0,
            'tax' => $tax,
            'total' => $subtotal + $tax,
            'paid_amount' => $subtotal + $tax,
            'change_amount' => 0,
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unpaid',
            'paid_amount' => 0,
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'partial',
            'paid_amount' => $attributes['total'] / 2,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
