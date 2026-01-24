<?php

declare(strict_types=1);

namespace App\Services\Weather;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for fetching and caching weather data.
 */
class WeatherService
{
    private const CACHE_PREFIX = 'weather:';

    private const CACHE_TTL_MINUTES = 20;

    public function __construct(
        private readonly OpenMeteoClient $client,
    ) {}

    /**
     * Get the current weather data.
     * Returns cached data if available, otherwise fetches from API.
     */
    public function getWeather(): ?WeatherData
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $location = $this->getLocation();
        if (! $location || ! isset($location['lat'], $location['lon']) || $location['lat'] === null) {
            return null;
        }

        $cacheKey = $this->getCacheKey($location['lat'], $location['lon']);

        // Try to get from cache
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            try {
                return WeatherData::fromArray($cached);
            } catch (\Throwable $e) {
                Log::warning('Failed to deserialize cached weather data', [
                    'error' => $e->getMessage(),
                ]);
                Cache::forget($cacheKey);
            }
        }

        // Fetch fresh data
        return $this->refreshWeather();
    }

    /**
     * Force refresh the weather data from API.
     */
    public function refreshWeather(): ?WeatherData
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $location = $this->getLocation();
        if (! $location || ! isset($location['lat'], $location['lon']) || $location['lat'] === null) {
            return null;
        }

        $units = $this->getUnits();
        $cacheKey = $this->getCacheKey($location['lat'], $location['lon']);

        // Fetch from API
        $response = $this->client->fetchWeather(
            (float) $location['lat'],
            (float) $location['lon'],
            $units
        );

        if ($response === null) {
            // API failed - try to return stale cache
            $staleCache = Cache::get($cacheKey);
            if ($staleCache !== null) {
                Log::info('Returning stale weather cache after API failure');

                try {
                    return WeatherData::fromArray($staleCache);
                } catch (\Throwable) {
                    return null;
                }
            }

            return null;
        }

        // Transform API response
        $weatherData = $this->transformResponse($response, $units);

        // Cache the result
        Cache::put($cacheKey, $weatherData->toArray(), now()->addMinutes(self::CACHE_TTL_MINUTES));

        return $weatherData;
    }

    /**
     * Search for locations by name.
     *
     * @return list<array{name: string, latitude: float, longitude: float, display: string}>
     */
    public function searchLocations(string $query): array
    {
        $results = $this->client->searchLocations($query);

        return array_map(fn (array $result) => [
            'name' => $result['name'],
            'latitude' => $result['latitude'],
            'longitude' => $result['longitude'],
            'display' => $this->formatLocationDisplay($result),
        ], $results);
    }

    /**
     * Check if weather feature is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) Setting::get('weather.enabled', false);
    }

    /**
     * Get the configured location.
     *
     * @return array{lat: float|null, lon: float|null, name: string|null}|null
     */
    public function getLocation(): ?array
    {
        return Setting::get('weather.location');
    }

    /**
     * Get the configured temperature units.
     */
    public function getUnits(): string
    {
        return Setting::get('weather.units', 'fahrenheit');
    }

    /**
     * Get the configured widget size.
     */
    public function getWidgetSize(): string
    {
        return Setting::get('weather.widget_size', 'medium');
    }

    /**
     * Check if "feels like" should be shown.
     */
    public function shouldShowFeelsLike(): bool
    {
        return (bool) Setting::get('weather.show_feels_like', true);
    }

    /**
     * Check if high/low should be shown.
     */
    public function shouldShowHighLow(): bool
    {
        return (bool) Setting::get('weather.show_high_low', true);
    }

    /**
     * Check if precipitation alerts should be shown.
     */
    public function shouldShowPrecipitation(): bool
    {
        return (bool) Setting::get('weather.show_precipitation', true);
    }

    /**
     * Generate cache key for a location.
     */
    private function getCacheKey(float $lat, float $lon): string
    {
        return self::CACHE_PREFIX . round($lat, 2) . ',' . round($lon, 2);
    }

    /**
     * Transform API response to WeatherData object.
     *
     * @param  array<string, mixed>  $response
     */
    private function transformResponse(array $response, string $units): WeatherData
    {
        $current = $response['current'] ?? [];
        $daily = $response['daily'] ?? [];
        $hourly = $response['hourly'] ?? [];

        $weatherCode = (int) ($current['weather_code'] ?? 0);
        $precipitationAlerts = $this->extractPrecipitationAlerts($hourly, $weatherCode);

        return new WeatherData(
            temperature: (float) ($current['temperature_2m'] ?? 0),
            feelsLike: (float) ($current['apparent_temperature'] ?? 0),
            weatherCode: $weatherCode,
            conditionText: WeatherData::getConditionText($weatherCode),
            conditionEmoji: WeatherData::getConditionEmoji($weatherCode),
            high: (float) ($daily['temperature_2m_max'][0] ?? 0),
            low: (float) ($daily['temperature_2m_min'][0] ?? 0),
            precipitationChance: (int) ($daily['precipitation_probability_max'][0] ?? 0),
            precipitationAlerts: $precipitationAlerts,
            fetchedAt: Carbon::now(),
            units: $units,
        );
    }

    /**
     * Extract precipitation alerts from hourly data.
     *
     * @param  array<string, mixed>  $hourly
     * @return list<PrecipitationAlert>
     */
    private function extractPrecipitationAlerts(array $hourly, int $currentWeatherCode): array
    {
        if (! isset($hourly['time'], $hourly['precipitation_probability'], $hourly['precipitation'])) {
            return [];
        }

        $times = $hourly['time'] ?? [];
        $probabilities = $hourly['precipitation_probability'] ?? [];
        $amounts = $hourly['precipitation'] ?? [];
        $weatherCodes = $hourly['weather_code'] ?? [];

        // Group hours by time period
        $periods = [
            'early' => ['start' => 5, 'end' => 10],
            'mid-day' => ['start' => 10, 'end' => 15],
            'late' => ['start' => 15, 'end' => 20],
        ];

        $alerts = [];

        foreach ($periods as $timing => $hours) {
            $periodProbabilities = [];
            $periodAmounts = [];
            $periodCodes = [];

            foreach ($times as $index => $time) {
                $hour = (int) Carbon::parse($time)->format('G');

                if ($hour >= $hours['start'] && $hour < $hours['end']) {
                    $periodProbabilities[] = $probabilities[$index] ?? 0;
                    $periodAmounts[] = $amounts[$index] ?? 0;
                    $periodCodes[] = $weatherCodes[$index] ?? 0;
                }
            }

            if (empty($periodProbabilities)) {
                continue;
            }

            $maxProbability = (int) max($periodProbabilities);
            $totalAmount = array_sum($periodAmounts);

            // Only create alert if probability >= 30%
            if ($maxProbability < 30) {
                continue;
            }

            // Determine precipitation type from weather codes
            $type = $this->determinePrecipitationType($periodCodes);

            $alerts[] = new PrecipitationAlert(
                type: $type,
                timing: $timing,
                probability: $maxProbability,
                amount: (float) $totalAmount,
            );
        }

        return $alerts;
    }

    /**
     * Determine precipitation type from weather codes.
     *
     * @param  list<int>  $codes
     */
    private function determinePrecipitationType(array $codes): string
    {
        $hasRain = false;
        $hasSnow = false;

        foreach ($codes as $code) {
            // Snow codes
            if (in_array($code, [71, 73, 75, 77, 85, 86], true)) {
                $hasSnow = true;
            }
            // Rain codes
            if (in_array($code, [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82], true)) {
                $hasRain = true;
            }
        }

        if ($hasRain && $hasSnow) {
            return 'mixed';
        }

        if ($hasSnow) {
            return 'snow';
        }

        return 'rain';
    }

    /**
     * Format location for display.
     *
     * @param  array{name: string, admin1: ?string, country: string}  $location
     */
    private function formatLocationDisplay(array $location): string
    {
        $parts = [$location['name']];

        if (! empty($location['admin1'])) {
            $parts[] = $location['admin1'];
        }

        $parts[] = $location['country'];

        return implode(', ', $parts);
    }
}
