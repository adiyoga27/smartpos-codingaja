<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Purchase> */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;
        $subtotal = fake()->randomFloat(2, 50000, 10000000);
        $total = $subtotal;

        return [
            'document_number' => 'PO-'.now()->format('Ymd').'-'.str_pad((string) self::$counter, 4, '0', STR_PAD_LEFT),
            'supplier_id' => Supplier::factory(),
            'purchase_date' => fake()->date(),
            'due_date' => fake()->optional()->date(),
            'status' => 'draft',
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax' => 0,
            'total' => $total,
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

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
