<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Weather\WeatherService;
use Illuminate\Console\Command;

class RefreshWeatherCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the weather cache from the API';

    /**
     * Execute the console command.
     */
    public function handle(WeatherService $weatherService): int
    {
        if (! $weatherService->isEnabled()) {
            $this->info('Weather feature is not enabled. Skipping refresh.');

            return self::SUCCESS;
        }

        $location = $weatherService->getLocation();
        if (! $location || $location['lat'] === null) {
            $this->warn('No location configured for weather. Skipping refresh.');

            return self::SUCCESS;
        }

        $this->info('Refreshing weather cache...');

        $weather = $weatherService->refreshWeather();

        if ($weather === null) {
            $this->error('Failed to refresh weather cache.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Weather cache refreshed: %s %s, %s',
            $weather->getFormattedTemperature(),
            $weather->conditionEmoji,
            $weather->conditionText
        ));

        return self::SUCCESS;
    }
}
