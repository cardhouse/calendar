<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'value' => $this->faker->word(),
        ];
    }

    /**
     * State for weather location setting.
     */
    public function weatherLocation(): static
    {
        return $this->state(fn () => [
            'key' => 'weather.location',
            'value' => [
                'lat' => $this->faker->latitude(),
                'lon' => $this->faker->longitude(),
                'name' => $this->faker->city() . ', ' . $this->faker->stateAbbr(),
            ],
        ]);
    }

    /**
     * State for weather enabled setting.
     */
    public function weatherEnabled(bool $enabled = true): static
    {
        return $this->state(fn () => [
            'key' => 'weather.enabled',
            'value' => $enabled,
        ]);
    }
}
