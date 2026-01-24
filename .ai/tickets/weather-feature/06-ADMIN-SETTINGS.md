# Ticket 06: Admin Weather Settings Page

## Summary
Create an admin page for configuring weather settings including location search, units, and widget options.

## Acceptance Criteria
- [ ] Location search with city name autocomplete (Open-Meteo Geocoding API)
- [ ] Display selected location name and coordinates
- [ ] Toggle for Fahrenheit/Celsius
- [ ] Widget size selector (compact/medium/large)
- [ ] Toggles for show/hide: feels like, high/low, precipitation
- [ ] Enable/disable weather widget entirely
- [ ] Save triggers immediate cache refresh

## Location Search
Use Open-Meteo Geocoding API:
```
https://geocoding-api.open-meteo.com/v1/search?name={query}&count=5
```

Returns:
```json
{
  "results": [
    {"name": "New York", "latitude": 40.71, "longitude": -74.01, "admin1": "New York", "country": "United States"}
  ]
}
```

## Files to Create
- `resources/views/pages/admin/weather.blade.php`

## Files to Modify
- `routes/web.php` - Add route
- `resources/views/components/layouts/admin.blade.php` - Add nav link

## Form Fields
1. **Enable Weather** - Toggle (wire:model="enabled")
2. **Location Search** - Text input with debounced search, dropdown results
3. **Selected Location** - Display card with name, lat/lon
4. **Temperature Units** - Radio: Fahrenheit / Celsius
5. **Widget Size** - Radio: Compact / Medium / Large
6. **Show Options** - Checkboxes: Feels Like, High/Low, Precipitation Alerts
