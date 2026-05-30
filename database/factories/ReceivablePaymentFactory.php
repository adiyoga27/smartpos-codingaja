<?php

namespace Database\Factories;

use App\Models\CashAccount;
use App\Models\Customer;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReceivablePayment> */
class ReceivablePaymentFactory extends Factory
{
    protected $model = ReceivablePayment::class;

    public function definition(): array
    {
        return [
            'receivable_id' => Receivable::factory(),
            'customer_id' => Customer::factory(),
            'cash_account_id' => CashAccount::factory()->cash(),
            'payment_date' => fake()->date(),
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'notes' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
