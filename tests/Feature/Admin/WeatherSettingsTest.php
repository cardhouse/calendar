<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\Weather\OpenMeteoClient;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();
});

test('can view weather settings page', function () {
    $this->get(route('admin.weather.index'))
        ->assertOk()
        ->assertSee('Weather Settings');
});

test('page loads current settings', function () {
    Setting::set('weather.enabled', true);
    Setting::set('weather.location', ['lat' => 40.7128, 'lon' => -74.006, 'name' => 'New York, NY']);
    Setting::set('weather.units', 'celsius');
    Setting::set('weather.widget_size', 'large');

    Livewire::test('pages::admin.weather')
        ->assertSet('enabled', true)
        ->assertSet('locationName', 'New York, NY')
        ->assertSet('units', 'celsius')
        ->assertSet('widgetSize', 'large');
});

test('can toggle weather enabled', function () {
    Setting::set('weather.enabled', false);

    Livewire::test('pages::admin.weather')
        ->assertSet('enabled', false)
        ->toggle('enabled')
        ->assertSet('enabled', true);
});

test('can search for locations', function () {
    // Mock the client to return search results
    $mockClient = Mockery::mock(OpenMeteoClient::class);
    $mockClient->shouldReceive('searchLocations')
        ->with('New York')
        ->andReturn([
            [
                'name' => 'New York',
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'admin1' => 'New York',
                'country' => 'United States',
            ],
        ]);

    $this->app->instance(OpenMeteoClient::class, $mockClient);

    Livewire::test('pages::admin.weather')
        ->set('searchQuery', 'New York')
        ->assertSet('showSearchResults', true)
        ->assertCount('searchResults', 1);
});

test('can select a location from search results', function () {
    Livewire::test('pages::admin.weather')
        ->set('searchResults', [
            [
                'name' => 'New York',
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'display' => 'New York, New York, United States',
            ],
        ])
        ->set('showSearchResults', true)
        ->call('selectLocation', 0)
        ->assertSet('latitude', 40.7128)
        ->assertSet('longitude', -74.006)
        ->assertSet('locationName', 'New York, New York, United States')
        ->assertSet('showSearchResults', false);
});

test('can clear selected location', function () {
    Setting::set('weather.location', ['lat' => 40.7128, 'lon' => -74.006, 'name' => 'New York']);

    Livewire::test('pages::admin.weather')
        ->assertSet('locationName', 'New York')
        ->call('clearLocation')
        ->assertSet('latitude', null)
        ->assertSet('longitude', null)
        ->assertSet('locationName', null);
});

test('can change temperature units', function () {
    Livewire::test('pages::admin.weather')
        ->set('units', 'celsius')
        ->assertSet('units', 'celsius')
        ->set('units', 'fahrenheit')
        ->assertSet('units', 'fahrenheit');
});

test('can change widget size', function () {
    Livewire::test('pages::admin.weather')
        ->set('widgetSize', 'compact')
        ->assertSet('widgetSize', 'compact')
        ->set('widgetSize', 'large')
        ->assertSet('widgetSize', 'large');
});

test('can toggle display options', function () {
    Livewire::test('pages::admin.weather')
        ->assertSet('showFeelsLike', true)
        ->toggle('showFeelsLike')
        ->assertSet('showFeelsLike', false)
        ->toggle('showHighLow')
        ->assertSet('showHighLow', false);
});

test('save persists all settings', function () {
    Livewire::test('pages::admin.weather')
        ->set('enabled', true)
        ->set('latitude', 40.7128)
        ->set('longitude', -74.006)
        ->set('locationName', 'New York, NY')
        ->set('units', 'celsius')
        ->set('widgetSize', 'large')
        ->set('showFeelsLike', false)
        ->set('showHighLow', true)
        ->set('showPrecipitation', false)
        ->call('save')
        ->assertSet('saved', true);

    expect(Setting::get('weather.enabled'))->toBeTrue()
        ->and(Setting::get('weather.units'))->toBe('celsius')
        ->and(Setting::get('weather.widget_size'))->toBe('large')
        ->and(Setting::get('weather.show_feels_like'))->toBeFalse()
        ->and(Setting::get('weather.show_high_low'))->toBeTrue()
        ->and(Setting::get('weather.show_precipitation'))->toBeFalse();

    $location = Setting::get('weather.location');
    expect($location['lat'])->toBe(40.7128)
        ->and($location['lon'])->toBe(-74.006)
        ->and($location['name'])->toBe('New York, NY');
});

test('admin nav includes weather link', function () {
    $this->get(route('admin.weather.index'))
        ->assertSee('Weather')
        ->assertSee(route('admin.weather.index'));
});
