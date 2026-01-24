<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Child;
use App\Models\RoutineItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoutineItem>
 */
class RoutineItemFactory extends Factory
{
    protected $model = RoutineItem::class;

    /** @var array<int, string> */
    private array $routineTasks = [
        'Brush teeth',
        'Wash face',
        'Get dressed',
        'Make bed',
        'Eat breakfast',
        'Pack backpack',
        'Put on shoes',
        'Comb hair',
    ];

    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            'name' => fake()->randomElement($this->routineTasks),
            'display_order' => fake()->numberBetween(0, 10),
        ];
    }
}
