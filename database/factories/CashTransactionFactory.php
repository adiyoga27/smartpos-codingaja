<?php

namespace Database\Factories;

use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CashTransaction> */
class CashTransactionFactory extends Factory
{
    protected $model = CashTransaction::class;

    public function definition(): array
    {
        return [
            'cash_account_id' => CashAccount::factory(),
            'type' => fake()->randomElement(['in', 'out']),
            'reference_number' => 'REF-'.fake()->unique()->numerify('######'),
            'transaction_date' => fake()->date(),
            'amount' => fake()->randomFloat(2, 1000, 5000000),
            'account_id' => null,
            'target_account_id' => null,
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function cashIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'in',
        ]);
    }

    public function cashOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'out',
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'transfer',
            'target_account_id' => CashAccount::factory(),
        ]);
    }
}
