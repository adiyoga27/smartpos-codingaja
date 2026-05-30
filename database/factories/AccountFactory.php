<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Account> */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;

        return [
            'code' => 'ACC-'.str_pad((string) self::$counter, 4, '0', STR_PAD_LEFT),
            'name' => fake()->unique()->word(),
            'parent_code' => null,
            'type' => fake()->randomElement(['asset', 'liability', 'equity', 'revenue', 'expense']),
            'normal_balance' => fake()->randomElement(['debit', 'credit']),
            'opening_balance' => fake()->randomFloat(2, 0, 10000000),
            'is_active' => true,
        ];
    }

    public function asset(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'asset',
            'normal_balance' => 'debit',
        ]);
    }

    public function liability(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'liability',
            'normal_balance' => 'credit',
        ]);
    }

    public function equity(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'equity',
            'normal_balance' => 'credit',
        ]);
    }

    public function revenue(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'revenue',
            'normal_balance' => 'credit',
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
            'normal_balance' => 'debit',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
