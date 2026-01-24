# Ticket 01: Settings Model & Migration

## Summary
Create a key-value settings storage system for weather configuration and future app settings.

## Acceptance Criteria
- [ ] Migration creates `settings` table with `key`, `value` (JSON), `timestamps`
- [ ] Setting model with static `get()` and `set()` methods
- [ ] Settings are cached for performance
- [ ] Seeder creates default weather settings

## Implementation

### Migration
```php
Schema::create('settings', function (Blueprint $table) {
    $table->string('key', 100)->primary();
    $table->json('value');
    $table->timestamps();
});
```

### Model Methods
- `Setting::get(string $key, mixed $default = null): mixed`
- `Setting::set(string $key, mixed $value): void`
- `Setting::forget(string $key): void`

### Default Weather Settings
```php
[
    'weather.enabled' => false,
    'weather.location' => ['lat' => null, 'lon' => null, 'name' => null],
    'weather.units' => 'fahrenheit',
    'weather.widget_size' => 'medium',
    'weather.show_precipitation' => true,
    'weather.show_feels_like' => true,
    'weather.show_high_low' => true,
]
```

## Files to Create
- `database/migrations/2026_01_24_000000_create_settings_table.php`
- `app/Models/Setting.php`
- `database/seeders/WeatherSettingsSeeder.php`
- `database/factories/SettingFactory.php`

## Tests
- Setting can be stored and retrieved
- JSON values are properly cast
- Cache is invalidated on update
