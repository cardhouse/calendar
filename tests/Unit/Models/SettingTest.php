<?php

declare(strict_types=1);

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Clear cache before each test
    Cache::flush();
});

test('can store and retrieve a string value', function () {
    Setting::set('test.key', 'test value');

    expect(Setting::get('test.key'))->toBe('test value');
});

test('can store and retrieve an array value', function () {
    $data = ['lat' => 40.7128, 'lon' => -74.006, 'name' => 'New York'];

    Setting::set('test.location', $data);

    expect(Setting::get('test.location'))->toBe($data);
});

test('can store and retrieve a boolean value', function () {
    Setting::set('test.enabled', true);

    expect(Setting::get('test.enabled'))->toBeTrue();

    Setting::set('test.disabled', false);

    expect(Setting::get('test.disabled'))->toBeFalse();
});

test('returns default when key not found', function () {
    expect(Setting::get('nonexistent.key', 'default'))->toBe('default');
});

test('returns null when key not found and no default provided', function () {
    expect(Setting::get('nonexistent.key'))->toBeNull();
});

test('can forget a setting', function () {
    Setting::set('test.key', 'value');

    expect(Setting::get('test.key'))->toBe('value');

    Setting::forget('test.key');

    expect(Setting::get('test.key'))->toBeNull();
});

test('can update an existing setting', function () {
    Setting::set('test.key', 'original');

    expect(Setting::get('test.key'))->toBe('original');

    Setting::set('test.key', 'updated');

    expect(Setting::get('test.key'))->toBe('updated');
});

test('can get settings by prefix', function () {
    Setting::set('weather.enabled', true);
    Setting::set('weather.units', 'fahrenheit');
    Setting::set('other.setting', 'value');

    $weatherSettings = Setting::getByPrefix('weather.');

    expect($weatherSettings)->toHaveCount(2)
        ->and($weatherSettings)->toHaveKey('weather.enabled')
        ->and($weatherSettings)->toHaveKey('weather.units')
        ->and($weatherSettings)->not->toHaveKey('other.setting');
});

test('cache is cleared when setting is updated', function () {
    Setting::set('test.key', 'original');

    // Value should be cached
    expect(Setting::get('test.key'))->toBe('original');

    // Update directly in database to verify cache behavior
    Setting::query()->where('key', 'test.key')->update(['value' => json_encode('direct-update')]);

    // Cache should still return original value
    expect(Cache::get('settings:test.key'))->toBe('original');

    // Using Setting::set should clear cache
    Setting::set('test.key', 'via-set');

    expect(Setting::get('test.key'))->toBe('via-set');
});
