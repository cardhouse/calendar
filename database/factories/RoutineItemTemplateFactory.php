<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RoutineItemTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoutineItemTemplate>
 */
class RoutineItemTemplateFactory extends Factory
{
    protected $model = RoutineItemTemplate::class;

    private array $routineTasks = [
        'Brush teeth',
        'Wash face',
        'Get dressed',
        'Make bed',
        'Eat breakfast',
        'Pack backpack',
        'Put on shoes',
        'Comb hair',
        'Feed the dog',
        'Take vitamins',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement($this->routineTasks),
            'display_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
