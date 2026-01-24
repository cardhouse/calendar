<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Child>
 */
class ChildFactory extends Factory
{
    protected $model = Child::class;

    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'avatar_color' => fake()->hexColor(),
            'display_order' => fake()->numberBetween(0, 10),
        ];
    }
}
