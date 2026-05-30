<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supplier> */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;

        return [
            'code' => 'SUP-'.str_pad((string) self::$counter, 4, '0', STR_PAD_LEFT),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'contact_person' => fake()->name(),
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
