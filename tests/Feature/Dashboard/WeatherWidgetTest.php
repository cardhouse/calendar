<?php

declare(strict_types=1);

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();
});

test('widget is hidden when weather is disabled', function () {
    Setting::set('weather.enabled', false);

    $this->get(route('dashboard'))
        ->assertDontSeeLivewire('dashboard.weather-widget');
});

test('widget is shown when weather is enabled', function () {
    Setting::set('weather.enabled', true);
    Setting::set('weather.location', ['lat' => 40.7128, 'lon' => -74.006, 'name' => 'New York']);
    Setting::set('weather.widget_size', 'medium');

    $this->get(route('dashboard'))
        ->assertSeeLivewire('dashboard.weather-widget');
});

test('widget shows unavailable message when no weather data', function () {
    Setting::set('weather.enabled', true);
    Setting::set('weather.location', ['lat' => null, 'lon' => null, 'name' => null]);

    Livewire::test('dashboard.weather-widget')
        ->assertSee('Weather unavailable');
});

test('widget shows weather data when available', function () {
    Setting::set('weather.enabled', true);
    Setting::set('weather.location', ['lat' => 40.7128, 'lon' => -74.006, 'name' => 'New York']);
    Setting::set('weather.widget_size', 'medium');

    // Mock cached weather data
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

    Livewire::test('dashboard.weather-widget')
        ->assertSee('72°')
        ->assertSee('Clear');
});

test('widget respects size setting for compact', function () {
    Setting::set('weather.enabled', true);
    Setting::set('weather.location', ['lat' => 40.7128, 'lon' => -74.006, 'name' => 'New York']);
    Setting::set('weather.widget_size', 'compact');

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

    Livewire::test('dashboard.weather-widget')
        ->assertSee('72°')
        ->assertDontSee('Feels like'); // Compact doesn't show feels like
});

test('widget refreshWeather method reloads data', function () {
    Setting::set('weather.enabled', true);
    Setting::set('weather.location', ['lat' => 40.7128, 'lon' => -74.006, 'name' => 'New York']);
    Setting::set('weather.widget_size', 'medium');

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

    Livewire::test('dashboard.weather-widget')
        ->assertSee('72°')
        ->call('refreshWeather')
        ->assertSee('72°');
});
