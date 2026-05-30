<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;

        return [
            'code' => 'CUS-'.str_pad((string) self::$counter, 4, '0', STR_PAD_LEFT),
            'name' => fake()->name(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'type' => fake()->randomElement(['retail', 'wholesale']),
            'credit_limit' => fake()->randomFloat(2, 100000, 10000000),
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ];
    }

    public function wholesale(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'wholesale',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
