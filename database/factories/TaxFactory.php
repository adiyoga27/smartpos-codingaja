<?php

namespace Database\Factories;

use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tax> */
class TaxFactory extends Factory
{
    protected $model = Tax::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;

        return [
            'code' => 'TAX-'.str_pad((string) self::$counter, 3, '0', STR_PAD_LEFT),
            'name' => 'PPN '.fake()->randomFloat(2, 5, 15).'%',
            'rate' => fake()->randomFloat(2, 5, 15),
            'type' => 'ppn',
            'applies_to' => 'all',
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function ppn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ppn',
            'rate' => 11,
            'name' => 'PPN 11%',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
