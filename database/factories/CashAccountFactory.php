<?php

namespace Database\Factories;

use App\Models\CashAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CashAccount> */
class CashAccountFactory extends Factory
{
    protected $model = CashAccount::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;

        return [
            'name' => fake()->unique()->word().' Cash',
            'code' => 'CSH-'.str_pad((string) self::$counter, 3, '0', STR_PAD_LEFT),
            'type' => fake()->randomElement(['cash', 'bank']),
            'bank_name' => fake()->optional()->company(),
            'account_number' => fake()->optional()->bankAccountNumber(),
            'opening_balance' => fake()->randomFloat(2, 0, 10000000),
            'current_balance' => fake()->randomFloat(2, 0, 20000000),
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cash',
            'bank_name' => null,
            'account_number' => null,
        ]);
    }

    public function bank(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bank',
            'bank_name' => fake()->company().' Bank',
            'account_number' => fake()->bankAccountNumber(),
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
