# Ticket 02: Weather Service & DTOs

## Summary
Create the weather service layer with API client, data transfer objects, and caching logic.

## Acceptance Criteria
- [ ] OpenMeteoClient fetches weather data from API
- [ ] WeatherData DTO holds current conditions
- [ ] PrecipitationAlert value object for rain/snow alerts
- [ ] WeatherService orchestrates caching and data transformation
- [ ] Graceful handling when API fails (return stale cache)

## API Details

### Open-Meteo Endpoint
```
https://api.open-meteo.com/v1/forecast?
  latitude={lat}&longitude={lon}&
  current=temperature_2m,relative_humidity_2m,apparent_temperature,weather_code&
  daily=temperature_2m_max,temperature_2m_min,precipitation_probability_max&
  hourly=precipitation_probability,precipitation&
  temperature_unit={fahrenheit|celsius}&
  timezone=auto
```

### Weather Codes (WMO)
- 0: Clear
- 1-3: Partly cloudy
- 45-48: Fog
- 51-67: Drizzle/Rain
- 71-77: Snow
- 80-82: Rain showers
- 85-86: Snow showers
- 95-99: Thunderstorm

## Files to Create
- `app/Services/Weather/WeatherData.php`
- `app/Services/Weather/PrecipitationAlert.php`
- `app/Services/Weather/OpenMeteoClient.php`
- `app/Services/Weather/WeatherService.php`

## WeatherData Properties
```php
public readonly float $temperature;
public readonly float $feelsLike;
public readonly int $weatherCode;
public readonly string $conditionText;
public readonly string $conditionEmoji;
public readonly float $high;
public readonly float $low;
public readonly int $precipitationChance;
public readonly array $precipitationAlerts;
public readonly Carbon $fetchedAt;
public readonly string $units;
```

## PrecipitationAlert Properties
```php
public readonly string $type;      // 'rain', 'snow', 'mixed'
public readonly string $timing;    // 'early', 'mid-day', 'late'
public readonly int $probability;  // 0-100
public readonly float $amount;     // mm or inches
```

## Caching Strategy
- Cache key: `weather:{lat},{lon}`
- TTL: 20 minutes
- Return stale cache on API failure
