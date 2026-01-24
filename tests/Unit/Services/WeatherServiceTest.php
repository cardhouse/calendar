<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\Weather\OpenMeteoClient;
use App\Services\Weather\PrecipitationAlert;
use App\Services\Weather\WeatherData;
use App\Services\Weather\WeatherService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    Setting::set('weather.enabled', true);
    Setting::set('weather.location', ['lat' => 40.7128, 'lon' => -74.006, 'name' => 'New York, NY']);
    Setting::set('weather.units', 'fahrenheit');
});

test('returns null when weather is disabled', function () {
    Setting::set('weather.enabled', false);

    $service = new WeatherService(new OpenMeteoClient);

    expect($service->getWeather())->toBeNull();
});

test('returns null when no location is configured', function () {
    Setting::set('weather.location', ['lat' => null, 'lon' => null, 'name' => null]);

    $service = new WeatherService(new OpenMeteoClient);

    expect($service->getWeather())->toBeNull();
});

test('weather data can be serialized and deserialized', function () {
    $original = new WeatherData(
        temperature: 72.5,
        feelsLike: 74.0,
        weatherCode: 1,
        conditionText: 'Mostly Clear',
        conditionEmoji: '🌤️',
        high: 82.0,
        low: 65.0,
        precipitationChance: 20,
        precipitationAlerts: [
            new PrecipitationAlert(
                type: 'rain',
                timing: 'mid-day',
                probability: 60,
                amount: 5.0
            ),
        ],
        fetchedAt: now(),
        units: 'fahrenheit',
    );

    $array = $original->toArray();
    $restored = WeatherData::fromArray($array);

    expect($restored->temperature)->toBe(72.5)
        ->and($restored->feelsLike)->toBe(74.0)
        ->and($restored->weatherCode)->toBe(1)
        ->and($restored->conditionText)->toBe('Mostly Clear')
        ->and($restored->high)->toBe(82.0)
        ->and($restored->low)->toBe(65.0)
        ->and($restored->precipitationChance)->toBe(20)
        ->and($restored->precipitationAlerts)->toHaveCount(1)
        ->and($restored->precipitationAlerts[0]->type)->toBe('rain')
        ->and($restored->precipitationAlerts[0]->timing)->toBe('mid-day');
});

test('precipitation alert generates correct description', function () {
    $alert = new PrecipitationAlert(
        type: 'rain',
        timing: 'mid-day',
        probability: 60,
        amount: 25.4 // 1 inch in mm
    );

    $description = $alert->getDescription('fahrenheit');

    expect($description)->toContain('60%')
        ->and($description)->toContain('rain')
        ->and($description)->toContain('mid-day');
});

test('weather code maps to correct condition text', function () {
    expect(WeatherData::getConditionText(0))->toBe('Clear')
        ->and(WeatherData::getConditionText(1))->toBe('Mostly Clear')
        ->and(WeatherData::getConditionText(2))->toBe('Partly Cloudy')
        ->and(WeatherData::getConditionText(3))->toBe('Overcast')
        ->and(WeatherData::getConditionText(61))->toBe('Rain')
        ->and(WeatherData::getConditionText(71))->toBe('Snow')
        ->and(WeatherData::getConditionText(95))->toBe('Thunderstorm');
});

test('weather code maps to correct emoji', function () {
    expect(WeatherData::getConditionEmoji(0))->toBe('☀️')
        ->and(WeatherData::getConditionEmoji(3))->toBe('☁️')
        ->and(WeatherData::getConditionEmoji(71))->toBe('🌨️')
        ->and(WeatherData::getConditionEmoji(95))->toBe('⛈️');
});

test('weather data formatted temperature works correctly', function () {
    $weather = new WeatherData(
        temperature: 72.6,
        feelsLike: 74.0,
        weatherCode: 0,
        conditionText: 'Clear',
        conditionEmoji: '☀️',
        high: 82.0,
        low: 65.0,
        precipitationChance: 0,
        precipitationAlerts: [],
        fetchedAt: now(),
        units: 'fahrenheit',
    );

    expect($weather->getFormattedTemperature())->toBe('73°')
        ->and($weather->getFormattedFeelsLike())->toBe('Feels like 74°')
        ->and($weather->getFormattedHighLow())->toBe('H: 82° L: 65°');
});

test('weather data detects stale data', function () {
    $fresh = new WeatherData(
        temperature: 72.0,
        feelsLike: 74.0,
        weatherCode: 0,
        conditionText: 'Clear',
        conditionEmoji: '☀️',
        high: 82.0,
        low: 65.0,
        precipitationChance: 0,
        precipitationAlerts: [],
        fetchedAt: now(),
        units: 'fahrenheit',
    );

    $stale = new WeatherData(
        temperature: 72.0,
        feelsLike: 74.0,
        weatherCode: 0,
        conditionText: 'Clear',
        conditionEmoji: '☀️',
        high: 82.0,
        low: 65.0,
        precipitationChance: 0,
        precipitationAlerts: [],
        fetchedAt: now()->subMinutes(45),
        units: 'fahrenheit',
    );

    expect($fresh->isStale(30))->toBeFalse()
        ->and($stale->isStale(30))->toBeTrue();
});

test('service returns cached weather when available', function () {
    $cachedData = [
        'temperature' => 72.0,
        'feelsLike' => 74.0,
        'weatherCode' => 0,
        'conditionText' => 'Clear',
        'conditionEmoji' => '☀️',
        'high' => 82.0,
        'low' => 65.0,
        'precipitationChance' => 0,
        'precipitationAlerts' => [],
        'fetchedAt' => now()->toIso8601String(),
        'units' => 'fahrenheit',
    ];

    Cache::put('weather:40.71,-74.01', $cachedData, now()->addMinutes(20));

    $mockClient = Mockery::mock(OpenMeteoClient::class);
    $mockClient->shouldNotReceive('fetchWeather');

    $service = new WeatherService($mockClient);
    $weather = $service->getWeather();

    expect($weather)->not->toBeNull()
        ->and($weather->temperature)->toBe(72.0);
});

test('service is enabled check works', function () {
    $service = new WeatherService(new OpenMeteoClient);

    expect($service->isEnabled())->toBeTrue();

    Setting::set('weather.enabled', false);
    Setting::clearCache();

    expect($service->isEnabled())->toBeFalse();
});

test('service returns correct widget size', function () {
    Setting::set('weather.widget_size', 'large');
    Setting::clearCache();

    $service = new WeatherService(new OpenMeteoClient);

    expect($service->getWidgetSize())->toBe('large');
});
