<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class WeatherSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            'weather.enabled' => false,
            'weather.location' => [
                'lat' => null,
                'lon' => null,
                'name' => null,
            ],
            'weather.units' => 'fahrenheit',
            'weather.widget_size' => 'medium',
            'weather.show_precipitation' => true,
            'weather.show_feels_like' => true,
            'weather.show_high_low' => true,
        ];

        foreach ($defaults as $key => $value) {
            // Only set if not already exists (don't overwrite user settings)
            if (Setting::get($key) === null) {
                Setting::set($key, $value);
            }
        }
    }
}
