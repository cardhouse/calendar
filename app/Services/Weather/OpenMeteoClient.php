<?php

declare(strict_types=1);

namespace App\Services\Weather;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for the Open-Meteo weather API.
 */
class OpenMeteoClient
{
    private const BASE_URL = 'https://api.open-meteo.com/v1/forecast';

    private const GEOCODING_URL = 'https://geocoding-api.open-meteo.com/v1/search';

    private const TIMEOUT_SECONDS = 10;

    /**
     * Fetch weather data for a location.
     *
     * @return array<string, mixed>|null Returns null on failure
     */
    public function fetchWeather(float $latitude, float $longitude, string $units = 'fahrenheit'): ?array
    {
        $temperatureUnit = $units === 'celsius' ? 'celsius' : 'fahrenheit';

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->get(self::BASE_URL, [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code',
                    'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_probability_max',
                    'hourly' => 'precipitation_probability,precipitation,weather_code',
                    'temperature_unit' => $temperatureUnit,
                    'timezone' => 'auto',
                    'forecast_days' => 1,
                ]);

            if ($response->failed()) {
                Log::warning('Open-Meteo API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json();
        } catch (ConnectionException $e) {
            Log::warning('Open-Meteo API connection failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Search for locations by name.
     *
     * @return list<array{name: string, latitude: float, longitude: float, admin1: ?string, country: string}>
     */
    public function searchLocations(string $query, int $count = 5): array
    {
        if (strlen(trim($query)) < 2) {
            return [];
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->get(self::GEOCODING_URL, [
                    'name' => $query,
                    'count' => $count,
                    'language' => 'en',
                    'format' => 'json',
                ]);

            if ($response->failed()) {
                Log::warning('Open-Meteo Geocoding API request failed', [
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json();

            if (! isset($data['results']) || ! is_array($data['results'])) {
                return [];
            }

            return array_map(fn (array $result) => [
                'name' => $result['name'] ?? 'Unknown',
                'latitude' => (float) ($result['latitude'] ?? 0),
                'longitude' => (float) ($result['longitude'] ?? 0),
                'admin1' => $result['admin1'] ?? null,
                'country' => $result['country'] ?? 'Unknown',
            ], $data['results']);
        } catch (ConnectionException $e) {
            Log::warning('Open-Meteo Geocoding API connection failed', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
