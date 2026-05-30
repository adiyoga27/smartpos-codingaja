<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Journal> */
class JournalFactory extends Factory
{
    protected $model = Journal::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;
        $debit = fake()->randomFloat(2, 1000, 5000000);

        return [
            'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((string) self::$counter, 4, '0', STR_PAD_LEFT),
            'journal_date' => fake()->date(),
            'description' => fake()->sentence(),
            'source' => fake()->randomElement(['manual', 'purchase', 'sale', 'payment', 'cash']),
            'total_debit' => $debit,
            'total_credit' => $debit,
            'created_by' => User::factory(),
        ];
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'manual',
        ]);
    }
}
