# Technical Architecture

## Overview

This document describes the technical architecture, patterns, and conventions to be followed when building the Family Morning Dashboard.

## Directory Structure

```
app/
├── Http/
│   └── Controllers/
│       └── Admin/              # Admin controllers for configuration
├── Models/
│   ├── Child.php               # Child model with routines
│   ├── RoutineItem.php         # Individual routine checklist items
│   ├── DepartureTime.php       # Bus/departure time configurations
│   └── CalendarEvent.php       # Upcoming calendar events
├── Providers/
│   ├── AppServiceProvider.php
│   └── VoltServiceProvider.php
└── Services/                   # Business logic services
    └── Dashboard/              # Dashboard-related services

resources/
├── views/
│   ├── components/             # Reusable Blade components
│   │   └── layouts/
│   │       └── app.blade.php   # Main layout
│   ├── livewire/               # Livewire component views
│   │   └── dashboard/          # Dashboard components
│   └── pages/                  # Volt page components
│       ├── dashboard.blade.php # Main dashboard display
│       └── admin/              # Admin configuration pages
│           ├── children.blade.php
│           ├── routines.blade.php
│           ├── departures.blade.php
│           └── events.blade.php
├── css/
│   └── app.css                 # Tailwind CSS entry
└── js/
    ├── app.js                  # JavaScript entry (imports Alpine components)
    └── alpine/                 # Alpine.js components (CRITICAL for timers)
        ├── clock-display.js    # Current time display
        ├── departure-timer.js  # Departure countdown timer
        └── event-countdown.js  # Event countdown cards

database/
├── migrations/                 # Database migrations
├── factories/                  # Model factories for testing
└── seeders/                    # Database seeders

tests/
├── Feature/                    # Feature/integration tests
│   ├── Dashboard/              # Dashboard display tests
│   └── Admin/                  # Admin functionality tests
└── Unit/                       # Unit tests
    └── Models/                 # Model unit tests
```

## Architectural Patterns

### 1. Volt Single-File Components

Use Livewire Volt for all interactive components. Prefer **class-based** Volt components for consistency:

```php
<?php

use Livewire\Volt\Component;
use App\Models\Child;

new class extends Component {
    public Child $child;

    public function mount(Child $child): void
    {
        $this->child = $child;
    }

    public function toggleItem(int $itemId): void
    {
        // Toggle logic
    }
}; ?>

<div>
    <!-- Component template -->
</div>
```

### 2. Component Hierarchy

```
Dashboard Page
├── Header Component (time, date display)
├── Checklist Section
│   └── Child Checklist Component (one per child)
│       └── Routine Item Component (one per item)
├── Departure Timer Section
│   └── Countdown Timer Component
└── Upcoming Events Section
    └── Event Card Component (one per event)
```

### 3. Real-Time Updates - Alpine.js First

**CRITICAL**: Use Alpine.js for all time-based displays and calculations. Do NOT use `wire:poll` for timers as this creates unnecessary server requests.

#### When to Use Alpine.js (Frontend Only)

- Current time/clock display
- Countdown timers (departure, events)
- Time-based visual state changes (urgency colors)
- Any calculation that doesn't require server data

#### When to Use Livewire (Server Required)

- Checklist toggle interactions (persists to database)
- Loading initial data
- Admin CRUD operations
- Any action that modifies database state

#### Alpine.js Timer Pattern

```blade
<!-- Clock display - NEVER poll for this -->
<div x-data="{ time: new Date() }"
     x-init="setInterval(() => time = new Date(), 1000)">
    <span x-text="time.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })"></span>
</div>

<!-- Countdown timer - calculate entirely on frontend -->
<div x-data="countdownTimer(@js($departureTimestamp))"
     x-init="startTimer()">
    <span x-text="display"></span>
</div>
```

#### Alpine.js Component for Countdown

```javascript
// resources/js/alpine/countdown-timer.js
document.addEventListener('alpine:init', () => {
    Alpine.data('countdownTimer', (targetTimestamp) => ({
        targetTime: new Date(targetTimestamp * 1000),
        secondsRemaining: 0,
        display: '',
        urgencyClass: 'bg-slate-800 text-white',
        interval: null,

        startTimer() {
            this.updateCountdown();
            this.interval = setInterval(() => this.updateCountdown(), 1000);
        },

        updateCountdown() {
            const now = new Date();
            this.secondsRemaining = Math.max(0, Math.floor((this.targetTime - now) / 1000));
            this.display = this.formatTime(this.secondsRemaining);
            this.urgencyClass = this.getUrgencyClass(this.secondsRemaining);
        },

        formatTime(seconds) {
            if (seconds <= 0) return 'Time to go!';
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            if (h > 0) return `${h}:${String(m).padStart(2, '0')}`;
            return `${m}:${String(s).padStart(2, '0')}`;
        },

        getUrgencyClass(seconds) {
            if (seconds <= 0) return 'bg-slate-700 text-slate-400';
            if (seconds < 300) return 'bg-red-900/50 text-red-200 animate-pulse';
            if (seconds < 900) return 'bg-orange-900/50 text-orange-200';
            if (seconds < 1800) return 'bg-yellow-900/50 text-yellow-200';
            return 'bg-slate-800 text-white';
        },

        destroy() {
            if (this.interval) clearInterval(this.interval);
        }
    }));
});
```

### 4. State Management

- **Server State**: All persistent data (routines, events, completion status) stored in database via Livewire
- **Frontend State**: All time-based calculations and display logic handled by Alpine.js
- **Data Flow**: Livewire loads initial data → Alpine.js handles real-time display updates
- **No Polling for Timers**: NEVER use `wire:poll` for countdown displays

## Coding Conventions

### Models

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Child extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_order',
        'avatar_color',
    ];

    /**
     * @return HasMany<RoutineItem, $this>
     */
    public function routineItems(): HasMany
    {
        return $this->hasMany(RoutineItem::class)->orderBy('display_order');
    }
}
```

### Migrations

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('display_order')->default(0);
            $table->string('avatar_color')->default('#3B82F6');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
```

### Volt Components

- Place page components in `resources/views/pages/`
- Place reusable components in `resources/views/livewire/`
- Use descriptive file names: `child-checklist.blade.php`, `countdown-timer.blade.php`

### Testing

```php
<?php

declare(strict_types=1);

use App\Models\Child;
use App\Models\RoutineItem;
use Livewire\Volt\Volt;

test('child checklist displays all routine items', function () {
    $child = Child::factory()
        ->has(RoutineItem::factory()->count(3))
        ->create();

    Volt::test('dashboard.child-checklist', ['child' => $child])
        ->assertSee($child->name)
        ->assertSee($child->routineItems->first()->name);
});

test('routine item can be toggled complete', function () {
    $child = Child::factory()
        ->has(RoutineItem::factory()->state(['is_completed' => false]))
        ->create();

    $item = $child->routineItems->first();

    Volt::test('dashboard.child-checklist', ['child' => $child])
        ->call('toggleItem', $item->id)
        ->assertSet('child.routineItems.0.is_completed', true);
});
```

## URL Structure

### Display Routes

| Route | Purpose |
|-------|---------|
| `/` | Main dashboard display |
| `/fullscreen` | Fullscreen mode (hides browser chrome instructions) |

### Admin Routes

| Route | Purpose |
|-------|---------|
| `/admin` | Admin dashboard/overview |
| `/admin/children` | Manage children |
| `/admin/children/{child}/routines` | Manage child's routine items |
| `/admin/departures` | Manage departure times |
| `/admin/events` | Manage calendar events |

## Security Considerations

### Current Scope (Single-Family)

- No authentication required for display mode
- Admin routes optionally protected by simple PIN or local-only access
- All data is local to the installation

### Future Multi-Family Support

If authentication is added later:
- Use Laravel's built-in authentication
- Scope all queries to authenticated family
- Add proper authorization policies

## Performance Guidelines

### Critical: Zero-Polling Dashboard

The dashboard should make **ZERO server requests** while simply displaying time-based information. Server requests should ONLY occur when:
- Initial page load
- User interacts with a checklist item
- Admin makes configuration changes

### Dashboard Display

1. **Eager Load Relationships**: Always eager load children with routine items on initial load
2. **NO Polling for Timers**: Use Alpine.js for ALL countdown/clock displays
3. **Efficient Queries**: Dashboard initial load should require only 3-4 queries total
4. **Pass Timestamps to Frontend**: Send Unix timestamps to Alpine.js, let it calculate display

### What MUST Use Alpine.js (No Server Calls)

| Feature | Implementation |
|---------|----------------|
| Current time display | `setInterval` updating local Date object |
| Departure countdown | Alpine component with target timestamp |
| Event countdowns | Alpine component calculating days/hours remaining |
| Urgency color changes | Alpine reactive class binding |
| Timer animations | CSS + Alpine state |

### What Uses Livewire (Server Calls OK)

| Feature | Implementation |
|---------|----------------|
| Toggle checklist item | `wire:click` to persist completion |
| Initial data load | Volt component `mount()` method |
| Admin CRUD operations | Standard Livewire forms |

### Livewire Optimization

```php
// Good: Load data once in mount, pass timestamps for Alpine
public function mount(): void
{
    $this->children = Child::with(['routineItems.completions' => function ($q) {
        $q->whereDate('completion_date', today());
    }])->orderBy('display_order')->get();

    $this->departure = DepartureTime::getNextDeparture();
    // Pass as Unix timestamp for Alpine.js
    $this->departureTimestamp = $this->departure?->getNextOccurrence()?->timestamp;

    $this->events = CalendarEvent::getUpcoming(3);
    // Convert to array with timestamps for Alpine.js
    $this->eventData = $this->events->map(fn ($e) => [
        'id' => $e->id,
        'name' => $e->name,
        'timestamp' => $e->starts_at->timestamp,
        'color' => $e->color,
    ])->toArray();
}

// BAD: Never do this - creates server request every second!
// <div wire:poll.1s> ... </div>
```

### Data Flow Pattern

```
┌─────────────────────────────────────────────────────────────┐
│                      INITIAL PAGE LOAD                       │
│  Livewire loads data → Passes timestamps to Alpine.js       │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    CONTINUOUS DISPLAY                        │
│  Alpine.js calculates countdowns locally (NO server calls)  │
│  setInterval updates display every second                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    USER INTERACTION                          │
│  User clicks checklist item → Livewire persists to DB       │
│  Only time server is contacted after initial load           │
└─────────────────────────────────────────────────────────────┘
```

## Error Handling

### Display Mode

- Gracefully degrade if data is missing
- Show placeholder content rather than error messages
- Log errors but don't interrupt the display

### Admin Mode

- Use Laravel validation with clear error messages
- Provide confirmation for destructive actions
- Support undo where practical

## Related Documentation

- [PROJECT.md](./PROJECT.md) - Project overview
- [FEATURES.md](./FEATURES.md) - Feature specifications
- [DATA-MODELS.md](./DATA-MODELS.md) - Database schema
- [UI-COMPONENTS.md](./UI-COMPONENTS.md) - UI guidelines
