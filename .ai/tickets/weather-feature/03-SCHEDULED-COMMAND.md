# Ticket 03: Scheduled Weather Refresh Command

## Summary
Create an Artisan command that refreshes the weather cache, scheduled to run every 15 minutes.

## Acceptance Criteria
- [ ] Command `weather:refresh` fetches fresh weather data
- [ ] Only runs if weather is enabled in settings
- [ ] Logs success/failure
- [ ] Registered in scheduler to run every 15 minutes

## Command Implementation
```php
php artisan weather:refresh
```

### Behavior
1. Check if `weather.enabled` setting is true
2. Get location from `weather.location` setting
3. Call WeatherService to fetch and cache data
4. Log result

## Files to Create/Modify
- `app/Console/Commands/RefreshWeatherCache.php`
- Modify `routes/console.php` or `bootstrap/app.php` for scheduling

## Schedule Registration
```php
Schedule::command('weather:refresh')->everyFifteenMinutes();
```
