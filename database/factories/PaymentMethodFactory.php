<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentMethod> */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;

        return [
            'code' => 'PM-'.str_pad((string) self::$counter, 3, '0', STR_PAD_LEFT),
            'name' => fake()->unique()->word(),
            'account_id' => Account::factory()->asset(),
            'is_available_pos' => true,
            'is_available_purchase' => true,
            'is_credit' => false,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Tunai',
            'is_credit' => false,
        ]);
    }

    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_credit' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
