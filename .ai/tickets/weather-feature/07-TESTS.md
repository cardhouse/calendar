# Ticket 07: Tests

## Summary
Write comprehensive tests for the weather feature.

## Test Files to Create

### Unit Tests
- `tests/Unit/Models/SettingTest.php`
- `tests/Unit/Services/WeatherServiceTest.php`

### Feature Tests
- `tests/Feature/Dashboard/WeatherWidgetTest.php`
- `tests/Feature/Admin/WeatherSettingsTest.php`

## Test Cases

### SettingTest
- [ ] Can store and retrieve string value
- [ ] Can store and retrieve array value
- [ ] Returns default when key not found
- [ ] Can forget a setting
- [ ] Cache is used for reads
- [ ] Cache is cleared on writes

### WeatherServiceTest
- [ ] Returns cached weather when available
- [ ] Fetches from API when cache empty
- [ ] Returns stale cache when API fails
- [ ] Correctly transforms API response to WeatherData
- [ ] Generates precipitation alerts from hourly data
- [ ] Maps weather codes to emoji correctly

### WeatherWidgetTest
- [ ] Widget renders when weather enabled
- [ ] Widget hidden when weather disabled
- [ ] Compact size shows only temp and icon
- [ ] Medium size shows temp, feels like, high/low
- [ ] Large size shows precipitation alerts
- [ ] refreshWeather method updates data

### WeatherSettingsTest
- [ ] Can view weather settings page
- [ ] Can search for locations
- [ ] Can select a location
- [ ] Can change temperature units
- [ ] Can change widget size
- [ ] Can toggle feature elements
- [ ] Save persists all settings
