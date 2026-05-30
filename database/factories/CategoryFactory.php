<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;

        return [
            'name' => fake()->unique()->word(),
            'code' => 'CAT-'.str_pad((string) self::$counter, 3, '0', STR_PAD_LEFT),
            'description' => fake()->sentence(),
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
