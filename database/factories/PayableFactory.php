<?php

namespace Database\Factories;

use App\Models\Payable;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payable> */
class PayableFactory extends Factory
{
    protected $model = Payable::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;
        $amount = fake()->randomFloat(2, 100000, 10000000);

        return [
            'supplier_id' => Supplier::factory(),
            'purchase_id' => Purchase::factory(),
            'document_number' => 'HUT-'.now()->format('Ymd').'-'.str_pad((string) self::$counter, 4, '0', STR_PAD_LEFT),
            'due_date' => fake()->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'amount' => $amount,
            'paid_amount' => 0,
            'remaining_amount' => $amount,
            'status' => 'open',
        ];
    }

    public function paid(): static
    {
        $amount = 100000;

        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'paid_amount' => $amount,
            'remaining_amount' => 0,
            'status' => 'paid',
        ]);
    }

    public function partial(): static
    {
        $amount = 100000;

        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'paid_amount' => 30000,
            'remaining_amount' => 70000,
            'status' => 'partial',
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => fake()->dateTimeBetween('-60 days', '-1 day')->format('Y-m-d'),
            'status' => 'overdue',
        ]);
    }
}
