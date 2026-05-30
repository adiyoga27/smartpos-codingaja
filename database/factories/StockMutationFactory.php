<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMutation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockMutation> */
class StockMutationFactory extends Factory
{
    protected $model = StockMutation::class;

    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 1, 20);
        $stockBefore = fake()->randomFloat(2, 10, 100);

        return [
            'product_id' => Product::factory(),
            'type' => fake()->randomElement(['in', 'out', 'adjustment', 'opname']),
            'quantity' => $qty,
            'stock_before' => $stockBefore,
            'stock_after' => $stockBefore + $qty,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function stockIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'in',
            'stock_after' => $attributes['stock_before'] + $attributes['quantity'],
        ]);
    }

    public function stockOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'out',
            'stock_after' => $attributes['stock_before'] - $attributes['quantity'],
        ]);
    }
}
