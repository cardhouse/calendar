# Ticket 04: Weather Widget Volt Component

## Summary
Create a Livewire Volt component that displays weather data with three configurable size variants.

## Acceptance Criteria
- [ ] Widget loads weather from cache via WeatherService
- [ ] Three size variants: compact, medium, large
- [ ] Matches dark theme of dashboard
- [ ] Alpine.js triggers refresh every 15 minutes (NO wire:poll)
- [ ] Graceful empty state when weather disabled or unavailable

## Size Variants

### Compact (~100px)
- Temperature + emoji icon only
- Example: `72° ☀️`

### Medium (~200px)
- Temperature, feels like, condition text, high/low
- Example:
  ```
  72° ☀️ Clear
  Feels like 75° · H: 82° L: 65°
  ```

### Large (~300px)
- All medium content + precipitation alerts
- Example:
  ```
  72° ☀️ Clear
  Feels like 75° · H: 82° L: 65°
  🌧️ 60% chance rain mid-day (~0.3")
  ```

## Files to Create
- `resources/views/livewire/dashboard/weather-widget.blade.php`

## Alpine.js Refresh Pattern
```javascript
x-data="{
    init() {
        setInterval(() => this.$wire.refreshWeather(), 15 * 60 * 1000);
    }
}"
```

## Component Methods
- `mount()`: Load weather from service
- `refreshWeather()`: Re-fetch from cache (called by Alpine interval)
