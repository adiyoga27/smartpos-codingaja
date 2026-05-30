<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JournalEntry> */
class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 1000, 5000000);
        $isDebit = fake()->boolean();

        return [
            'journal_id' => Journal::factory(),
            'account_id' => Account::factory(),
            'description' => fake()->sentence(),
            'debit' => $isDebit ? $amount : 0,
            'credit' => $isDebit ? 0 : $amount,
        ];
    }
}
