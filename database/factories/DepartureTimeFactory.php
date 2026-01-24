<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DepartureTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepartureTime>
 */
class DepartureTimeFactory extends Factory
{
    protected $model = DepartureTime::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['School bus', 'Leave for school', 'Carpool pickup']),
            'departure_time' => fake()->time('H:i:s', '09:00:00'),
            'applicable_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true,
            'display_order' => fake()->numberBetween(0, 5),
        ];
    }

    public function weekendsOnly(): static
    {
        return $this->state(fn () => [
            'applicable_days' => ['saturday', 'sunday'],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
