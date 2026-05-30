<?php

namespace Database\Factories;

use App\Models\CashAccount;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayablePayment> */
class PayablePaymentFactory extends Factory
{
    protected $model = PayablePayment::class;

    public function definition(): array
    {
        return [
            'payable_id' => Payable::factory(),
            'supplier_id' => Supplier::factory(),
            'cash_account_id' => CashAccount::factory()->cash(),
            'payment_date' => fake()->date(),
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'notes' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
