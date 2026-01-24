# Weather Feature - Implementation Plan

## Overview
Add weather forecast and current conditions to the Family Morning Dashboard, displayed in the header between the clock and departure timer.

## Requirements Summary
- **Data displayed**: Current temp, feels like, condition/icon (emoji), high/low, precipitation chance
- **Precipitation alerts**: Rain/snow alerts with amounts and timing (early, mid-day, late)
- **Location**: Fixed location with city name search in admin
- **Widget sizes**: Compact, Medium, Large (configurable in admin)
- **Update frequency**: Every 15 minutes via scheduled task + Alpine.js refresh
- **API**: Open-Meteo (free, no API key required)

## Tickets

| # | Ticket | Description | Dependencies |
|---|--------|-------------|--------------|
| 1 | [01-SETTINGS-MODEL.md](./01-SETTINGS-MODEL.md) | Create Setting model and migration | None |
| 2 | [02-WEATHER-SERVICE.md](./02-WEATHER-SERVICE.md) | Create WeatherService, DTOs, and API client | #1 |
| 3 | [03-SCHEDULED-COMMAND.md](./03-SCHEDULED-COMMAND.md) | Create scheduled command to refresh cache | #2 |
| 4 | [04-WEATHER-WIDGET.md](./04-WEATHER-WIDGET.md) | Create Volt component with 3 sizes | #2 |
| 5 | [05-DASHBOARD-INTEGRATION.md](./05-DASHBOARD-INTEGRATION.md) | Add widget to dashboard header | #4 |
| 6 | [06-ADMIN-SETTINGS.md](./06-ADMIN-SETTINGS.md) | Create admin weather settings page | #1 |
| 7 | [07-TESTS.md](./07-TESTS.md) | Write feature and unit tests | #1-6 |

## File Structure

```
app/
├── Console/Commands/
│   └── RefreshWeatherCache.php
├── Models/
│   └── Setting.php
└── Services/Weather/
    ├── WeatherService.php
    ├── OpenMeteoClient.php
    ├── WeatherData.php
    └── PrecipitationAlert.php

database/
├── migrations/
│   └── 2026_01_24_000000_create_settings_table.php
└── seeders/
    └── WeatherSettingsSeeder.php

resources/views/
├── livewire/dashboard/
│   └── weather-widget.blade.php
└── pages/admin/
    └── weather.blade.php

tests/
├── Feature/
│   ├── Dashboard/WeatherWidgetTest.php
│   └── Admin/WeatherSettingsTest.php
└── Unit/Services/
    └── WeatherServiceTest.php
```

## Technical Decisions

1. **API**: Open-Meteo - free, no key required, hourly precipitation data
2. **Caching**: Laravel cache with 20-min TTL, refreshed every 15 min via scheduler
3. **Refresh**: Scheduled task populates cache; Alpine.js interval triggers Livewire to re-read cache
4. **Icons**: Emoji-based weather icons for simplicity
5. **Location search**: Geocoding via Open-Meteo's geocoding API
6. **Precipitation timing**:
   - Early: 5am-10am
   - Mid-day: 10am-3pm
   - Late: 3pm-8pm
