# Ticket 05: Dashboard Integration

## Summary
Integrate the weather widget into the dashboard header, positioned between the clock and departure timer.

## Acceptance Criteria
- [ ] Weather widget appears in header when enabled
- [ ] Positioned between clock (left) and departure timer (right)
- [ ] Responsive layout maintains spacing
- [ ] Widget hidden gracefully when weather disabled

## Current Layout
```
+------------------------------------------+
| [Clock]                  [Departure Timer]|
+------------------------------------------+
```

## New Layout
```
+--------------------------------------------------+
| [Clock]      [Weather Widget]      [Departure Timer]|
+--------------------------------------------------+
```

## Files to Modify
- `resources/views/pages/dashboard.blade.php`

## Implementation
Add weather widget between clock and timer in header:
```blade
<header class="flex items-center justify-between gap-6 mb-8">
    <!-- Clock -->
    <div>...</div>

    <!-- Weather Widget (new) -->
    @if(Setting::get('weather.enabled'))
        <livewire:dashboard.weather-widget />
    @endif

    <!-- Departure Timer -->
    <div>...</div>
</header>
```
