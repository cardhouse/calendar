<laravel-boost-guidelines>
=== .ai/ARCHITECTURE rules ===

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

=== .ai/DATA-MODELS rules ===

# Data Models & Database Schema

## Overview

This document defines the database schema, Eloquent models, and relationships for the Family Morning Dashboard.

## Entity Relationship Diagram

```
+----------------+       +------------------+
|    children    |       |  routine_items   |
+----------------+       +------------------+
| id             |<──────| id               |
| name           |   1:N | child_id (FK)    |
| avatar_color   |       | name             |
| display_order  |       | display_order    |
| created_at     |       | created_at       |
| updated_at     |       | updated_at       |
+----------------+       +------------------+
                                 │
                                 │ 1:N
                                 ▼
                        +--------------------+
                        | routine_completions|
                        +--------------------+
                        | id                 |
                        | routine_item_id(FK)|
                        | completed_at       |
                        | completion_date    |
                        +--------------------+

+------------------+       +------------------+
| departure_times  |       | calendar_events  |
+------------------+       +------------------+
| id               |       | id               |
| name             |       | name             |
| departure_time   |       | starts_at        |
| applicable_days  |       | category         |
| is_active        |       | color            |
| display_order    |       | created_at       |
| created_at       |       | updated_at       |
| updated_at       |       +------------------+
+------------------+
```

---

## Model: Child

Represents a child in the household with their own morning routine.

### Database Schema

```php
Schema::create('children', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('avatar_color')->default('#3B82F6');
    $table->unsignedInteger('display_order')->default(0);
    $table->timestamps();
});
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Child's display name |
| `avatar_color` | string | Hex color code for visual identification |
| `display_order` | integer | Order in which children appear on dashboard |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

### Model Definition

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
        'avatar_color',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<RoutineItem, $this>
     */
    public function routineItems(): HasMany
    {
        return $this->hasMany(RoutineItem::class)->orderBy('display_order');
    }

    /**
     * Get completion percentage for today.
     */
    public function getTodayProgressAttribute(): int
    {
        $total = $this->routineItems()->count();
        if ($total === 0) {
            return 100;
        }

        $completed = $this->routineItems()
            ->whereHas('completions', fn ($q) => $q->whereDate('completion_date', today()))
            ->count();

        return (int) round(($completed / $total) * 100);
    }
}
```

### Factory

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Child>
 */
class ChildFactory extends Factory
{
    protected $model = Child::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName(),
            'avatar_color' => $this->faker->hexColor(),
            'display_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
```

---

## Model: RoutineItem

Represents a single task in a child's morning routine checklist.

### Database Schema

```php
Schema::create('routine_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('child_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->unsignedInteger('display_order')->default(0);
    $table->timestamps();
});
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Primary key |
| `child_id` | bigint | Foreign key to children table |
| `name` | string | Task name (e.g., "Brush teeth") |
| `display_order` | integer | Order within child's checklist |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

### Model Definition

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class RoutineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'child_id',
        'name',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Child, $this>
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * @return HasMany<RoutineCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(RoutineCompletion::class);
    }

    /**
     * Check if this item is completed for a given date.
     */
    public function isCompletedFor(?Carbon $date = null): bool
    {
        $date ??= today();

        return $this->completions()
            ->whereDate('completion_date', $date)
            ->exists();
    }

    /**
     * Mark this item as completed for today.
     */
    public function markComplete(): void
    {
        if (! $this->isCompletedFor()) {
            $this->completions()->create([
                'completion_date' => today(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Mark this item as incomplete for today.
     */
    public function markIncomplete(): void
    {
        $this->completions()
            ->whereDate('completion_date', today())
            ->delete();
    }

    /**
     * Toggle completion status for today.
     */
    public function toggleCompletion(): bool
    {
        if ($this->isCompletedFor()) {
            $this->markIncomplete();
            return false;
        }

        $this->markComplete();
        return true;
    }
}
```

### Factory

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Child;
use App\Models\RoutineItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoutineItem>
 */
class RoutineItemFactory extends Factory
{
    protected $model = RoutineItem::class;

    private array $routineTasks = [
        'Brush teeth',
        'Wash face',
        'Get dressed',
        'Make bed',
        'Eat breakfast',
        'Pack backpack',
        'Put on shoes',
        'Comb hair',
    ];

    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            'name' => $this->faker->randomElement($this->routineTasks),
            'display_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
```

---

## Model: RoutineCompletion

Tracks daily completion of routine items. Allows historical tracking and daily reset logic.

### Database Schema

```php
Schema::create('routine_completions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('routine_item_id')->constrained()->cascadeOnDelete();
    $table->date('completion_date');
    $table->timestamp('completed_at');

    $table->unique(['routine_item_id', 'completion_date']);
});
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Primary key |
| `routine_item_id` | bigint | Foreign key to routine_items |
| `completion_date` | date | The date this completion is for |
| `completed_at` | timestamp | When the item was marked complete |

### Model Definition

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutineCompletion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'routine_item_id',
        'completion_date',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completion_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<RoutineItem, $this>
     */
    public function routineItem(): BelongsTo
    {
        return $this->belongsTo(RoutineItem::class);
    }
}
```

---

## Model: DepartureTime

Configures departure/bus arrival times for the countdown timer.

### Database Schema

```php
Schema::create('departure_times', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->time('departure_time');
    $table->json('applicable_days')->default('["monday","tuesday","wednesday","thursday","friday"]');
    $table->boolean('is_active')->default(true);
    $table->unsignedInteger('display_order')->default(0);
    $table->timestamps();
});
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Display label (e.g., "School bus") |
| `departure_time` | time | Time of departure (HH:MM:SS) |
| `applicable_days` | json | Array of applicable day names |
| `is_active` | boolean | Whether this departure time is currently active |
| `display_order` | integer | Order for multiple departure times |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

### Applicable Days Format

```json
["monday", "tuesday", "wednesday", "thursday", "friday"]
```

Valid values: `monday`, `tuesday`, `wednesday`, `thursday`, `friday`, `saturday`, `sunday`

### Model Definition

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DepartureTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'departure_time',
        'applicable_days',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'departure_time' => 'datetime:H:i:s',
            'applicable_days' => 'array',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /**
     * Check if this departure time applies to a given date.
     */
    public function appliesToDate(?Carbon $date = null): bool
    {
        $date ??= today();
        $dayName = strtolower($date->format('l'));

        return in_array($dayName, $this->applicable_days, true);
    }

    /**
     * Get the next occurrence of this departure time.
     */
    public function getNextOccurrence(): ?Carbon
    {
        if (! $this->is_active) {
            return null;
        }

        $now = now();
        $todayDeparture = $now->copy()->setTimeFromTimeString($this->departure_time->format('H:i:s'));

        // If today applies and departure hasn't passed
        if ($this->appliesToDate($now) && $now->lt($todayDeparture)) {
            return $todayDeparture;
        }

        // Find next applicable day
        for ($i = 1; $i <= 7; $i++) {
            $nextDate = $now->copy()->addDays($i);
            if ($this->appliesToDate($nextDate)) {
                return $nextDate->setTimeFromTimeString($this->departure_time->format('H:i:s'));
            }
        }

        return null;
    }

    /**
     * Get seconds remaining until this departure.
     */
    public function getSecondsRemaining(): ?int
    {
        $nextOccurrence = $this->getNextOccurrence();

        if (! $nextOccurrence) {
            return null;
        }

        return (int) now()->diffInSeconds($nextOccurrence, false);
    }

    /**
     * Scope to get only active departure times.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the next active departure for today.
     */
    public static function getNextDeparture(): ?self
    {
        return static::active()
            ->orderBy('departure_time')
            ->get()
            ->filter(fn ($d) => $d->getSecondsRemaining() > 0)
            ->sortBy(fn ($d) => $d->getSecondsRemaining())
            ->first();
    }
}
```

### Factory

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DepartureTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepartureTime>
 */
class DepartureTimeFactory extends Factory
{
    protected $model = DepartureTime::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['School bus', 'Leave for school', 'Carpool pickup']),
            'departure_time' => $this->faker->time('H:i:s', '09:00:00'),
            'applicable_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true,
            'display_order' => $this->faker->numberBetween(0, 5),
        ];
    }

    public function weekendsOnly(): static
    {
        return $this->state(fn () => [
            'applicable_days' => ['saturday', 'sunday'],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
```

---

## Model: CalendarEvent

Represents upcoming events displayed on the dashboard.

### Database Schema

```php
Schema::create('calendar_events', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->dateTime('starts_at');
    $table->string('category')->nullable();
    $table->string('color')->default('#3B82F6');
    $table->timestamps();

    $table->index('starts_at');
});
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Event name |
| `starts_at` | datetime | Event start date/time |
| `category` | string | Optional category (birthday, school, etc.) |
| `color` | string | Display color (hex code) |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

### Category Suggestions

- `birthday` - Birthday parties
- `school` - School events
- `sports` - Sports activities
- `appointment` - Doctor, dentist, etc.
- `family` - Family gatherings
- `holiday` - Holidays
- `other` - Uncategorized

### Model Definition

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'starts_at',
        'category',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
        ];
    }

    /**
     * Scope to upcoming events only.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now())
            ->orderBy('starts_at');
    }

    /**
     * Scope to past events.
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('starts_at', '<=', now())
            ->orderByDesc('starts_at');
    }

    /**
     * Get the next N upcoming events.
     */
    public static function getUpcoming(int $limit = 3): \Illuminate\Database\Eloquent\Collection
    {
        return static::upcoming()->limit($limit)->get();
    }

    /**
     * Check if event is in the past.
     */
    public function isPast(): bool
    {
        return $this->starts_at->isPast();
    }

    /**
     * Get human-readable countdown string.
     */
    public function getCountdownAttribute(): string
    {
        if ($this->isPast()) {
            return 'Past';
        }

        $diff = now()->diff($this->starts_at);

        if ($diff->days > 7) {
            return $diff->days . ' days';
        }

        if ($diff->days >= 2) {
            return $diff->days . ' days, ' . $diff->h . ' hours';
        }

        if ($diff->days === 1) {
            return '1 day, ' . $diff->h . ' hours';
        }

        if ($diff->h > 0) {
            return $diff->h . ' hours, ' . $diff->i . ' min';
        }

        return $diff->i . ' minutes';
    }
}
```

### Factory

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    private array $eventNames = [
        'Birthday Party',
        'Soccer Practice',
        'Piano Recital',
        'Dentist Appointment',
        'School Play',
        'Family Dinner',
        'Swimming Lessons',
        'Science Fair',
    ];

    private array $categories = [
        'birthday',
        'sports',
        'school',
        'appointment',
        'family',
    ];

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement($this->eventNames),
            'starts_at' => $this->faker->dateTimeBetween('now', '+30 days'),
            'category' => $this->faker->randomElement($this->categories),
            'color' => $this->faker->hexColor(),
        ];
    }

    public function birthday(): static
    {
        return $this->state(fn () => [
            'name' => $this->faker->firstName() . "'s Birthday",
            'category' => 'birthday',
            'color' => '#EC4899',
        ]);
    }

    public function past(): static
    {
        return $this->state(fn () => [
            'starts_at' => $this->faker->dateTimeBetween('-30 days', '-1 hour'),
        ]);
    }
}
```

---

## Database Seeder

Example seeder for development/demo data:

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\Child;
use App\Models\DepartureTime;
use App\Models\RoutineItem;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        // Create children with routines
        $routines = [
            'Emma' => ['Brush teeth', 'Wash face', 'Get dressed', 'Make bed', 'Eat breakfast', 'Pack backpack'],
            'Jack' => ['Brush teeth', 'Get dressed', 'Eat breakfast', 'Feed the dog', 'Pack backpack', 'Put on shoes'],
        ];

        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
        $colorIndex = 0;

        foreach ($routines as $childName => $items) {
            $child = Child::create([
                'name' => $childName,
                'avatar_color' => $colors[$colorIndex % count($colors)],
                'display_order' => $colorIndex,
            ]);

            foreach ($items as $order => $itemName) {
                RoutineItem::create([
                    'child_id' => $child->id,
                    'name' => $itemName,
                    'display_order' => $order,
                ]);
            }

            $colorIndex++;
        }

        // Create departure times
        DepartureTime::create([
            'name' => 'Bus arrives',
            'departure_time' => '07:45:00',
            'applicable_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true,
            'display_order' => 0,
        ]);

        // Create upcoming events
        CalendarEvent::create([
            'name' => "Emma's Birthday Party",
            'starts_at' => now()->addDays(3)->setTime(14, 0),
            'category' => 'birthday',
            'color' => '#EC4899',
        ]);

        CalendarEvent::create([
            'name' => 'Soccer Tournament',
            'starts_at' => now()->addDays(7)->setTime(9, 0),
            'category' => 'sports',
            'color' => '#10B981',
        ]);

        CalendarEvent::create([
            'name' => 'Parent-Teacher Conference',
            'starts_at' => now()->addDays(14)->setTime(16, 30),
            'category' => 'school',
            'color' => '#3B82F6',
        ]);
    }
}
```

---

## Cleanup Command

Artisan command to clean up old completion records:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RoutineCompletion;
use Illuminate\Console\Command;

class CleanupOldCompletions extends Command
{
    protected $signature = 'dashboard:cleanup {--days=30 : Days of history to keep}';

    protected $description = 'Remove routine completion records older than specified days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = RoutineCompletion::where('completion_date', '<', $cutoff)->delete();

        $this->info("Deleted {$deleted} completion records older than {$days} days.");

        return self::SUCCESS;
    }
}
```

## Related Documentation

- [PROJECT.md](./PROJECT.md) - Project overview
- [ARCHITECTURE.md](./ARCHITECTURE.md) - Technical architecture
- [FEATURES.md](./FEATURES.md) - Feature specifications
- [UI-COMPONENTS.md](./UI-COMPONENTS.md) - UI guidelines

=== .ai/PROJECT rules ===

# Family Morning Dashboard

## Project Overview

The Family Morning Dashboard is a Laravel-based web application designed to be displayed on a screen every morning to help families organize their day. It serves as a central hub for morning routines, departure countdowns, and upcoming events.

## Core Purpose

This dashboard helps families (particularly those with school-age children) by:
1. Displaying interactive checklists for each child's morning routine
2. Showing a countdown timer to bus/departure time
3. Presenting upcoming calendar events with countdown timers
4. Providing a glanceable, always-visible morning command center

## Target Use Case

- **Primary Display**: Large screen (TV, monitor, or tablet) in a common area (kitchen, hallway)
- **Primary Users**: Parents and children preparing for school/work
- **Usage Pattern**: Displayed automatically each morning, visible during breakfast and preparation time
- **Interaction Mode**: Touch-friendly for quick checklist interactions; minimal interaction needed for viewing

## Key Design Principles

### 1. Glanceability
- Information should be readable from across the room
- Large fonts, high contrast, clear visual hierarchy
- Critical information (time remaining) should be immediately visible

### 2. Simplicity
- No login required for daily viewing (single-family household)
- Admin/configuration mode is separate from daily display mode
- Children should be able to use checklists without assistance

### 3. Modularity
- Each dashboard section is an independent component
- New features can be added without affecting existing ones
- Components can be rearranged or disabled per family preference

### 4. Real-Time Updates
- Countdown timers update in real-time
- Checklist changes reflect immediately
- Time-sensitive information auto-refreshes

## Application Modes

### Display Mode (Default)
- Full-screen dashboard view
- Auto-refreshing content
- Touch-friendly checklist interactions
- No navigation or configuration options visible

### Admin Mode
- Accessed via specific URL or gesture/key combination
- Manage children and their routines
- Configure departure times and bus schedules
- Add/edit/remove calendar events
- Customize dashboard layout and appearance

## Technology Stack

| Component | Technology | Version |
|-----------|------------|---------|
| Framework | Laravel | 12.x |
| Frontend | Livewire + Volt | 3.x / 1.x |
| Styling | Tailwind CSS | 4.x |
| Database | SQLite | - |
| Testing | Pest | 4.x |
| PHP | PHP | 8.4 |

## Success Criteria

A successful implementation will:
1. Display all required information clearly on a single screen
2. Update countdowns in real-time without page refresh
3. Allow children to independently check off routine items
4. Provide parents with easy configuration options
5. Run reliably for extended periods without intervention
6. Support future feature additions through modular design

## Non-Goals (Current Scope)

- User authentication/multi-family support
- Mobile app version
- External calendar integration (future enhancement)
- Notification/alert sounds (future enhancement)
- Weather integration (future enhancement)

## Related Documentation

- [ARCHITECTURE.md](./ARCHITECTURE.md) - Technical architecture and patterns
- [FEATURES.md](./FEATURES.md) - Detailed feature specifications
- [DATA-MODELS.md](./DATA-MODELS.md) - Database schema and relationships
- [UI-COMPONENTS.md](./UI-COMPONENTS.md) - UI/UX guidelines and components

=== .ai/UI-COMPONENTS rules ===

# UI Components & Design Guidelines

## Overview

This document defines the UI/UX guidelines, component specifications, and design system for the Family Morning Dashboard. The design prioritizes **glanceability**, **accessibility**, and **child-friendliness**.

---

## Design Principles

### 1. Glanceability

Information should be readable from 10+ feet away on a TV/monitor display.

- **Large typography** for critical information
- **High contrast** between text and background
- **Clear visual hierarchy** with distinct sections
- **Minimal text** - use icons and colors to convey meaning

### 2. Child-Friendly Interaction

Children should be able to interact without adult assistance.

- **Large touch targets** (minimum 48px, prefer 64px+)
- **Clear visual feedback** on interactions
- **Satisfying completion animations**
- **Forgiving gestures** - easy to tap, hard to make mistakes

### 3. Calm Design

The morning is stressful enough - the UI should be calming.

- **Soft color palette** with purposeful accent colors
- **Smooth animations** (not jarring or distracting)
- **Progressive urgency** - calm by default, urgent only when needed
- **No unnecessary movement** - static unless conveying information

---

## Color System

### Base Palette

```css
/* Background colors */
--color-bg-primary: #0F172A;     /* Dark slate - main background */
--color-bg-secondary: #1E293B;   /* Lighter slate - card backgrounds */
--color-bg-tertiary: #334155;    /* Even lighter - hover states */

/* Text colors */
--color-text-primary: #F8FAFC;   /* Near white - primary text */
--color-text-secondary: #CBD5E1; /* Light gray - secondary text */
--color-text-muted: #64748B;     /* Muted - less important info */

/* Accent colors */
--color-accent-blue: #3B82F6;    /* Primary accent */
--color-accent-green: #10B981;   /* Success/complete */
--color-accent-yellow: #F59E0B;  /* Warning/approaching */
--color-accent-orange: #F97316;  /* Urgent */
--color-accent-red: #EF4444;     /* Critical */
--color-accent-purple: #8B5CF6;  /* Events */
--color-accent-pink: #EC4899;    /* Birthdays */
```

### Timer State Colors

| State | Remaining | Background | Text |
|-------|-----------|------------|------|
| Normal | > 30 min | `bg-slate-800` | `text-white` |
| Approaching | 15-30 min | `bg-yellow-900/50` | `text-yellow-200` |
| Urgent | 5-15 min | `bg-orange-900/50` | `text-orange-200` |
| Critical | < 5 min | `bg-red-900/50` | `text-red-200` |

### Child Avatar Colors

Provide distinct, easily distinguishable colors:

```php
$avatarColors = [
    '#3B82F6', // Blue
    '#10B981', // Emerald
    '#F59E0B', // Amber
    '#EF4444', // Red
    '#8B5CF6', // Violet
    '#EC4899', // Pink
    '#06B6D4', // Cyan
    '#84CC16', // Lime
];
```

---

## Typography

### Font Stack

Use system fonts for performance and native feel:

```css
font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont,
             "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
```

### Size Scale

| Element | Size | Tailwind Class |
|---------|------|----------------|
| Current Time | 6rem+ | `text-7xl` or `text-8xl` |
| Timer Countdown | 4rem+ | `text-5xl` or `text-6xl` |
| Section Headers | 2rem | `text-3xl` |
| Child Name | 1.5rem | `text-2xl` |
| Checklist Item | 1.25rem | `text-xl` |
| Event Name | 1.25rem | `text-xl` |
| Secondary Info | 1rem | `text-base` |

### Font Weights

- **Time displays**: `font-bold` (700)
- **Names/Headers**: `font-semibold` (600)
- **Body text**: `font-medium` (500) or `font-normal` (400)

---

## Layout

### Dashboard Grid

The main dashboard uses a responsive grid layout:

```blade
<div class="min-h-screen bg-slate-900 p-6">
    <!-- Header -->
    <header class="mb-8 text-center">
        <!-- Time and date display -->
    </header>

    <!-- Main content grid -->
    <main class="grid grid-cols-12 gap-6">
        <!-- Checklists: span 8 columns -->
        <section class="col-span-8">
            <div class="grid grid-cols-2 gap-6">
                <!-- Child checklist cards -->
            </div>
        </section>

        <!-- Departure timer: span 4 columns -->
        <aside class="col-span-4">
            <!-- Countdown timer -->
        </aside>
    </main>

    <!-- Upcoming events footer -->
    <footer class="mt-8">
        <div class="grid grid-cols-3 gap-6">
            <!-- Event cards -->
        </div>
    </footer>
</div>
```

### Responsive Considerations

While primary use is on large displays, support smaller screens:

```blade
<main class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <section class="lg:col-span-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Child checklists -->
        </div>
    </section>
    <aside class="lg:col-span-4">
        <!-- Timer -->
    </aside>
</main>
```

---

## Component Specifications

### 1. Header / Clock Display (Alpine.js - NO server polling)

**IMPORTANT**: Use Alpine.js for time display. NEVER use `wire:poll` for clocks.

```blade
<header class="text-center mb-8"
        x-data="clockDisplay()"
        x-init="startClock()">
    <time class="text-8xl font-bold text-white tabular-nums">
        <span x-text="timeDisplay"></span>
        <span class="text-4xl text-slate-400" x-text="amPm"></span>
    </time>
    <p class="text-2xl text-slate-400 mt-2" x-text="dateDisplay"></p>
</header>
```

**Alpine.js Component:**

```javascript
// resources/js/alpine/clock-display.js
Alpine.data('clockDisplay', () => ({
    timeDisplay: '',
    amPm: '',
    dateDisplay: '',
    interval: null,

    startClock() {
        this.updateClock();
        this.interval = setInterval(() => this.updateClock(), 1000);
    },

    updateClock() {
        const now = new Date();
        this.timeDisplay = now.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        }).replace(/\s?(AM|PM)/, '');
        this.amPm = now.toLocaleTimeString('en-US', { hour12: true }).slice(-2);
        this.dateDisplay = now.toLocaleDateString('en-US', {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
    },

    destroy() {
        if (this.interval) clearInterval(this.interval);
    }
}));
```

**Key Points:**
- Use `tabular-nums` for consistent width as time changes
- AM/PM smaller than hour:minute
- Date in secondary color
- **Alpine.js handles all updates - zero server requests**

### 2. Child Checklist Card

```blade
<div class="bg-slate-800 rounded-2xl p-6 border-l-4"
     style="border-color: {{ $child->avatar_color }}">
    <!-- Header with name and progress -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-semibold text-white flex items-center gap-3">
            <span class="w-4 h-4 rounded-full"
                  style="background: {{ $child->avatar_color }}"></span>
            {{ $child->name }}
        </h2>
        <span class="text-lg text-slate-400">
            {{ $completedCount }}/{{ $totalCount }}
        </span>
    </div>

    <!-- Progress bar -->
    <div class="h-2 bg-slate-700 rounded-full mb-4 overflow-hidden">
        <div class="h-full bg-emerald-500 transition-all duration-300"
             style="width: {{ $progressPercent }}%"></div>
    </div>

    <!-- Checklist items -->
    <ul class="space-y-3">
        @foreach($child->routineItems as $item)
            <li wire:key="item-{{ $item->id }}">
                <button wire:click="toggleItem({{ $item->id }})"
                        class="w-full flex items-center gap-4 p-3 rounded-xl
                               transition-all duration-200
                               {{ $item->isCompletedFor()
                                  ? 'bg-emerald-900/30 text-emerald-300'
                                  : 'bg-slate-700/50 text-white hover:bg-slate-700' }}">
                    <!-- Checkbox indicator -->
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center
                                 {{ $item->isCompletedFor()
                                    ? 'bg-emerald-500'
                                    : 'bg-slate-600 border-2 border-slate-500' }}">
                        @if($item->isCompletedFor())
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        @endif
                    </span>
                    <!-- Item name -->
                    <span class="text-xl {{ $item->isCompletedFor() ? 'line-through opacity-75' : '' }}">
                        {{ $item->name }}
                    </span>
                </button>
            </li>
        @endforeach
    </ul>
</div>
```

**Key Points:**
- Left border color indicates child
- Large touch targets (entire row is clickable)
- Clear visual distinction between complete/incomplete
- Smooth transitions on state change

### 3. Departure Countdown Timer (Alpine.js - NO server polling)

**IMPORTANT**: Use Alpine.js for countdown. NEVER use `wire:poll` for timers.

Livewire passes the departure timestamp once on mount. Alpine.js handles all countdown logic.

```blade
{{-- Livewire passes $departureTimestamp (Unix timestamp) and $departureName --}}
@if($departureTimestamp)
    <div x-data="departureTimer({{ $departureTimestamp }})"
         x-init="startTimer()"
         class="rounded-2xl p-8 transition-colors duration-500"
         :class="urgencyClass">
        <h2 class="text-xl text-center opacity-75 mb-4">
            {{ $departureName }}
        </h2>

        <div class="text-center">
            <div class="text-6xl font-bold tabular-nums" x-text="display"></div>
            <p class="text-lg opacity-75 mt-2" x-text="label"></p>
        </div>
    </div>
@else
    <div class="rounded-2xl p-8 bg-slate-800">
        <p class="text-center text-xl text-slate-400">
            No departure scheduled
        </p>
    </div>
@endif
```

**Alpine.js Component:**

```javascript
// resources/js/alpine/departure-timer.js
Alpine.data('departureTimer', (targetTimestamp) => ({
    targetTime: new Date(targetTimestamp * 1000),
    secondsRemaining: 0,
    display: '',
    label: '',
    urgencyClass: 'bg-slate-800 text-white',
    interval: null,

    startTimer() {
        this.updateCountdown();
        this.interval = setInterval(() => this.updateCountdown(), 1000);
    },

    updateCountdown() {
        const now = new Date();
        this.secondsRemaining = Math.max(0, Math.floor((this.targetTime - now) / 1000));

        const h = Math.floor(this.secondsRemaining / 3600);
        const m = Math.floor((this.secondsRemaining % 3600) / 60);
        const s = this.secondsRemaining % 60;

        if (this.secondsRemaining <= 0) {
            this.display = 'Time to go!';
            this.label = '';
        } else if (h > 0) {
            this.display = `${h}:${String(m).padStart(2, '0')}`;
            this.label = 'hours remaining';
        } else {
            this.display = `${m}:${String(s).padStart(2, '0')}`;
            this.label = 'minutes remaining';
        }

        this.urgencyClass = this.getUrgencyClass();
    },

    getUrgencyClass() {
        if (this.secondsRemaining <= 0) return 'bg-slate-700 text-slate-400';
        if (this.secondsRemaining < 300) return 'bg-red-900/50 text-red-200 animate-pulse';
        if (this.secondsRemaining < 900) return 'bg-orange-900/50 text-orange-200';
        if (this.secondsRemaining < 1800) return 'bg-yellow-900/50 text-yellow-200';
        return 'bg-slate-800 text-white';
    },

    destroy() {
        if (this.interval) clearInterval(this.interval);
    }
}));
```

**Key Points:**
- Background color changes based on urgency (calculated by Alpine.js)
- Pulse animation for critical state
- Large, readable countdown numbers
- **Zero server requests** - all calculation happens in browser

### 4. Event Countdown Card (Alpine.js - NO server polling)

**IMPORTANT**: Use Alpine.js for event countdowns. Livewire passes event data once.

```blade
{{-- Livewire passes $eventData array with timestamps --}}
@foreach($eventData as $event)
    <div x-data="eventCountdown({{ $event['timestamp'] }})"
         x-init="startTimer()"
         class="bg-slate-800 rounded-xl p-5 border-l-4"
         style="border-color: {{ $event['color'] }}">
        <h3 class="text-xl font-semibold text-white mb-2">
            {{ $event['name'] }}
        </h3>
        <p class="text-3xl font-bold text-slate-200 mb-1" x-text="countdown"></p>
        <p class="text-sm text-slate-400">
            {{ \Carbon\Carbon::createFromTimestamp($event['timestamp'])->format('M j, g:i A') }}
        </p>
    </div>
@endforeach
```

**Alpine.js Component:**

```javascript
// resources/js/alpine/event-countdown.js
Alpine.data('eventCountdown', (targetTimestamp) => ({
    targetTime: new Date(targetTimestamp * 1000),
    countdown: '',
    interval: null,

    startTimer() {
        this.updateCountdown();
        // Events update every minute (not every second - less critical)
        this.interval = setInterval(() => this.updateCountdown(), 60000);
    },

    updateCountdown() {
        const now = new Date();
        const diff = this.targetTime - now;

        if (diff <= 0) {
            this.countdown = 'Now';
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

        if (days > 7) {
            this.countdown = `${days} days`;
        } else if (days >= 2) {
            this.countdown = `${days} days, ${hours} hours`;
        } else if (days === 1) {
            this.countdown = `1 day, ${hours} hours`;
        } else if (hours > 0) {
            this.countdown = `${hours} hours, ${minutes} min`;
        } else {
            this.countdown = `${minutes} minutes`;
        }
    },

    destroy() {
        if (this.interval) clearInterval(this.interval);
    }
}));
```

**Key Points:**
- Colored left border indicates category
- Prominent countdown display
- Full date/time in secondary text
- **Updates every 60 seconds** (events don't need per-second precision)
- **Zero server requests** - all calculation happens in browser

### 5. Completion Celebration

When all items are complete, show a subtle celebration:

```blade
@if($allComplete)
    <div class="text-center py-4 bg-emerald-900/30 rounded-xl">
        <p class="text-2xl text-emerald-300 font-semibold">
            All done! Great job, {{ $child->name }}!
        </p>
    </div>
@endif
```

---

## Animations

### Transition Utilities

Use Tailwind's built-in transitions:

```blade
<!-- Smooth background color changes -->
<div class="transition-colors duration-300">

<!-- Smooth all property changes -->
<div class="transition-all duration-200">

<!-- Scale on hover (for buttons) -->
<button class="hover:scale-105 active:scale-95 transition-transform">
```

### Completion Animation

CSS for checkmark animation:

```css
@keyframes checkmark {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.animate-checkmark {
    animation: checkmark 0.3s ease-out;
}
```

### Pulse for Urgency

Use Tailwind's `animate-pulse` for critical states:

```blade
<div class="{{ $isCritical ? 'animate-pulse' : '' }}">
```

---

## Admin Interface

### Admin Layout

The admin interface uses a simpler, form-focused design:

```blade
<div class="min-h-screen bg-slate-100 dark:bg-slate-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-slate-800 shadow">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold">Dashboard Admin</h1>
                <a href="/" class="text-blue-600 hover:text-blue-800">
                    View Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar + Content -->
    <div class="max-w-7xl mx-auto px-4 py-8 flex gap-8">
        <aside class="w-64 shrink-0">
            <!-- Admin navigation links -->
        </aside>
        <main class="flex-1">
            <!-- Page content -->
        </main>
    </div>
</div>
```

### Form Components

Use standard form styling with clear labels:

```blade
<!-- Text Input -->
<div class="mb-4">
    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
        Child Name
    </label>
    <input type="text"
           id="name"
           wire:model="name"
           class="w-full rounded-lg border-slate-300 dark:border-slate-600
                  dark:bg-slate-700 shadow-sm
                  focus:border-blue-500 focus:ring-blue-500">
    @error('name')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<!-- Color Picker -->
<div class="mb-4">
    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
        Avatar Color
    </label>
    <input type="color"
           wire:model="avatarColor"
           class="h-10 w-20 rounded cursor-pointer">
</div>

<!-- Time Input -->
<div class="mb-4">
    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
        Departure Time
    </label>
    <input type="time"
           wire:model="departureTime"
           class="rounded-lg border-slate-300 dark:border-slate-600
                  dark:bg-slate-700 shadow-sm">
</div>
```

### List Management

Sortable lists for reordering:

```blade
<ul class="space-y-2">
    @foreach($items as $index => $item)
        <li wire:key="item-{{ $item->id }}"
            class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800
                   rounded-lg shadow-sm">
            <!-- Drag handle -->
            <button class="cursor-move text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 8h16M4 16h16"/>
                </svg>
            </button>

            <!-- Item content -->
            <span class="flex-1">{{ $item->name }}</span>

            <!-- Actions -->
            <button wire:click="edit({{ $item->id }})" class="text-blue-600 hover:text-blue-800">
                Edit
            </button>
            <button wire:click="confirmDelete({{ $item->id }})" class="text-red-600 hover:text-red-800">
                Delete
            </button>
        </li>
    @endforeach
</ul>
```

---

## Accessibility

### Focus States

Ensure visible focus indicators:

```blade
<button class="focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
```

### Color Contrast

- Primary text on dark backgrounds: minimum 7:1 ratio
- Secondary text: minimum 4.5:1 ratio
- Use color + another indicator (icon, text) for status

### Screen Reader Support

```blade
<!-- Announce checklist completion -->
<button wire:click="toggleItem({{ $item->id }})"
        aria-pressed="{{ $item->isCompletedFor() ? 'true' : 'false' }}"
        aria-label="{{ $item->name }} - {{ $item->isCompletedFor() ? 'completed' : 'not completed' }}">
```

---

## Dark Mode

The dashboard uses dark mode by default (better for morning viewing in low light):

```blade
<!-- In app layout -->
<html class="dark">
```

Admin interface supports both modes:

```blade
<!-- Toggle based on system preference or user setting -->
<html class="dark:bg-slate-900">
```

---

## Icons

Use Heroicons (included with Tailwind) for consistency:

```blade
<!-- Checkmark -->
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
</svg>

<!-- Clock -->
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
</svg>

<!-- Calendar -->
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
</svg>
```

---

## Related Documentation

- [PROJECT.md](./PROJECT.md) - Project overview
- [ARCHITECTURE.md](./ARCHITECTURE.md) - Technical architecture
- [FEATURES.md](./FEATURES.md) - Feature specifications
- [DATA-MODELS.md](./DATA-MODELS.md) - Database schema

=== .ai/FEATURES rules ===

# Feature Specifications

## Overview

This document provides detailed specifications for each feature of the Family Morning Dashboard. Each feature section includes requirements, behavior descriptions, and acceptance criteria.

---

## Feature 1: Morning Routine Checklists

### Description

Interactive checklists for each child displaying their morning routine tasks. Children can tap/click items to mark them complete, providing visual feedback on their progress.

### Requirements

#### 1.1 Child Profiles

- Support multiple children (2-6 typical)
- Each child has:
  - Name (displayed prominently)
  - Avatar color (for visual distinction)
  - Display order (customizable)
  - Individual routine items

#### 1.2 Routine Items

- Each routine item has:
  - Name (e.g., "Brush teeth", "Make bed", "Eat breakfast")
  - Display order within child's list
  - Completion status (resets daily)
  - Optional icon (future enhancement)

#### 1.3 Checklist Behavior

- Items display with checkbox/indicator
- Tapping an item toggles completion status
- Completed items show visual distinction (strikethrough, checkmark, color change)
- Completion persists until daily reset
- Daily reset occurs at configurable time (default: midnight)

#### 1.4 Progress Indicator

- Each child's section shows overall progress
- Visual indicator (progress bar or X of Y completed)
- Celebration state when all items complete (subtle animation or color)

### Acceptance Criteria

- [ ] Multiple children can be displayed simultaneously
- [ ] Each child's routine items are independent
- [ ] Tapping an item toggles completion immediately (no page reload)
- [ ] Completed items are visually distinct from incomplete
- [ ] Progress updates in real-time as items are checked
- [ ] Checklist state persists across page refreshes
- [ ] Daily reset clears all completion statuses

### Admin Configuration

- Add/edit/remove children
- Add/edit/remove routine items per child
- Reorder children and items via drag-and-drop or order input
- Set avatar colors per child
- Configure daily reset time

---

## Feature 2: Departure Countdown Timer

### Description

A prominent countdown timer showing time remaining until the bus arrives or the family needs to leave. Changes visual state as departure approaches.

### Requirements

#### 2.1 Timer Display

- Large, easily readable countdown format
- Shows hours, minutes, and seconds (or just minutes:seconds when under 1 hour)
- Updates in real-time (every second)
- Displays associated label (e.g., "Bus arrives in", "Leave for school in")

#### 2.2 Multiple Departure Times

- Support multiple departure times (e.g., different buses, different days)
- System automatically shows the next relevant departure time
- Ability to configure which days each departure applies (weekdays, specific days)

#### 2.3 Visual States

| State | Condition | Visual Treatment |
|-------|-----------|------------------|
| Normal | > 30 minutes remaining | Standard display, calm color |
| Approaching | 15-30 minutes remaining | Yellow/warning color |
| Urgent | 5-15 minutes remaining | Orange color, possibly larger |
| Critical | < 5 minutes remaining | Red color, high emphasis |
| Passed | Departure time passed | Different message, muted color |

#### 2.4 Configurable Thresholds

- Allow customization of time thresholds for each visual state
- Different departure times can have different thresholds

### Acceptance Criteria

- [ ] Timer counts down accurately in real-time
- [ ] Timer automatically selects the next relevant departure time
- [ ] Visual state changes based on remaining time
- [ ] Timer handles day transitions correctly
- [ ] Non-applicable departure times (wrong day) are skipped
- [ ] Passed departure times show appropriate message

### Admin Configuration

- Add/edit/remove departure times
- Set time for each departure
- Set label/name for each departure
- Configure applicable days (weekdays, weekends, specific days)
- Adjust visual state thresholds (optional)

---

## Feature 3: Upcoming Events Countdown

### Description

A section displaying the next 3 upcoming calendar events with countdown timers showing time remaining until each event.

### Requirements

#### 3.1 Event Display

- Show the 3 soonest upcoming events
- Each event displays:
  - Event name
  - Countdown (days, hours format: "2 days, 4 hours")
  - Event date/time
  - Optional: category/color

#### 3.2 Countdown Format

| Time Remaining | Format |
|----------------|--------|
| > 7 days | "X days" |
| 2-7 days | "X days, Y hours" |
| 1-2 days | "Tomorrow" or "1 day, Y hours" |
| < 24 hours | "X hours, Y minutes" |
| < 1 hour | "X minutes" |
| Event started | "Now" or "In progress" |

#### 3.3 Event Sorting

- Events sorted by start date/time ascending
- Past events automatically removed from display
- If fewer than 3 upcoming events, show what's available

#### 3.4 Event Categories (Optional Enhancement)

- Birthdays
- School events
- Family activities
- Appointments
- Custom categories

### Acceptance Criteria

- [ ] Next 3 upcoming events are displayed
- [ ] Countdown timers update periodically (every minute is sufficient)
- [ ] Past events are automatically hidden
- [ ] Events display in chronological order
- [ ] Empty state handled gracefully when no events exist
- [ ] Event countdowns are accurate across time zones

### Admin Configuration

- Add new calendar events
- Edit existing events
- Delete events
- Set event name, date, time
- Set optional category/color
- View list of all events (including past)

---

## Feature 4: Dashboard Display

### Description

The main display page that combines all components into a cohesive, glanceable morning dashboard.

### Requirements

#### 4.1 Layout

- Single-page display (no scrolling required on target display)
- Responsive to different screen sizes
- Clear visual hierarchy with sections for each feature
- Clock/current time always visible

#### 4.2 Header Section

- Current time (large, prominent)
- Current date
- Day of week
- Optional: greeting or family name

#### 4.3 Content Sections

```
+--------------------------------------------------+
|                    HEADER                         |
|              8:15 AM - Monday, Jan 6              |
+--------------------------------------------------+
|                                                   |
|   +-------------+  +-------------+                |
|   |   CHILD 1   |  |   CHILD 2   |    DEPARTURE  |
|   |  CHECKLIST  |  |  CHECKLIST  |     TIMER     |
|   |             |  |             |               |
|   |  [ ] Item   |  |  [ ] Item   |    23:45      |
|   |  [x] Item   |  |  [ ] Item   |   until bus   |
|   |  [ ] Item   |  |  [x] Item   |               |
|   +-------------+  +-------------+                |
|                                                   |
+--------------------------------------------------+
|              UPCOMING EVENTS                      |
|  +------------+ +------------+ +------------+     |
|  | Birthday   | | Soccer     | | Dentist    |     |
|  | 2d 4h      | | 5d 12h     | | 1w 2d      |     |
|  +------------+ +------------+ +------------+     |
+--------------------------------------------------+
```

#### 4.4 Auto-Refresh

- Dashboard automatically updates without manual refresh
- Use Livewire polling for time-sensitive elements
- Minimize unnecessary re-renders

#### 4.5 Display Modes

- **Normal Mode**: Full dashboard with all sections
- **Fullscreen Mode**: Hints for entering browser fullscreen, optimized for kiosk display
- **Night Mode** (Future): Dimmed display during non-morning hours

### Acceptance Criteria

- [ ] All sections visible without scrolling on 1080p display
- [ ] Current time updates every second
- [ ] All sections are clearly distinguishable
- [ ] Dashboard works on various screen sizes
- [ ] No manual refresh needed for updates

---

## Feature 5: Admin Configuration Panel

### Description

A separate administrative interface for managing all dashboard configuration.

### Requirements

#### 5.1 Access

- Accessed via `/admin` URL
- Optionally protected by PIN code (future enhancement)
- Clear navigation between admin sections

#### 5.2 Admin Sections

| Section | Purpose |
|---------|---------|
| Children | Add/edit/remove child profiles |
| Routines | Manage routine items per child |
| Departures | Configure departure times |
| Events | Manage calendar events |
| Settings | General dashboard settings (future) |

#### 5.3 CRUD Operations

All admin sections support:
- Create new items
- Read/list existing items
- Update item details
- Delete items (with confirmation)

#### 5.4 User Experience

- Changes take effect immediately on dashboard
- Form validation with clear error messages
- Success feedback on save operations
- Confirmation dialogs for destructive actions

### Acceptance Criteria

- [ ] All CRUD operations work for each entity type
- [ ] Form validation prevents invalid data
- [ ] Changes reflect immediately on main dashboard
- [ ] Delete operations require confirmation
- [ ] Navigation between admin sections is intuitive

---

## Feature Priority

For initial implementation, features should be built in this order:

1. **Data Models & Migrations** - Foundation for all features
2. **Dashboard Display Layout** - Basic structure and styling
3. **Departure Countdown Timer** - High-impact, standalone feature
4. **Morning Routine Checklists** - Core feature with interactivity
5. **Upcoming Events Countdown** - Completes main display
6. **Admin Configuration Panel** - Enables customization

---

## Future Enhancements

These features are out of scope for initial implementation but should be considered in architecture:

- **Weather Integration**: Display current weather and forecast
- **Google Calendar Sync**: Import events from external calendars
- **Audio Alerts**: Sound notifications at key times
- **Multiple Households**: Authentication and family-specific data
- **Custom Widgets**: User-created dashboard sections
- **Mobile Companion App**: Remote checklist completion
- **Theme Customization**: Colors, fonts, layout options
- **Recurring Events**: Support for repeating calendar events
- **Routine Templates**: Pre-built routine sets to choose from

## Related Documentation

- [PROJECT.md](./PROJECT.md) - Project overview
- [ARCHITECTURE.md](./ARCHITECTURE.md) - Technical architecture
- [DATA-MODELS.md](./DATA-MODELS.md) - Database schema
- [UI-COMPONENTS.md](./UI-COMPONENTS.md) - UI guidelines

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.16
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- livewire/volt (VOLT) - v1
- larastan/larastan (LARASTAN) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version-specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

## Livewire

- Use the `search-docs` tool to find exact version-specific documentation for how to write Livewire and Livewire tests.
- Use the `php artisan make:livewire [Posts\CreatePost]` Artisan command to create new components.
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend; they're like regular HTTP requests. Always validate form data and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle Hook Examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>

## Testing Livewire

<code-snippet name="Example Livewire Component Test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>

<code-snippet name="Testing Livewire Component Exists on Page" lang="php">
    $this->get('/posts/create')
    ->assertSeeLivewire(CreatePost::class);
</code-snippet>

=== volt/core rules ===

## Livewire Volt

- This project uses Livewire Volt for interactivity within its pages. New pages requiring interactivity must also use Livewire Volt.
- Make new Volt components using `php artisan make:volt [name] [--test] [--pest]`.
- Volt is a class-based and functional API for Livewire that supports single-file components, allowing a component's PHP logic and Blade templates to coexist in the same file.
- Livewire Volt allows PHP logic and Blade templates in one file. Components use the `@volt` directive.
- You must check existing Volt components to determine if they're functional or class-based. If you can't detect that, ask the user which they prefer before writing a Volt component.

### Volt Functional Component Example

<code-snippet name="Volt Functional Component Example" lang="php">
@volt
<?php
use function Livewire\Volt\{state, computed};

state(['count' => 0]);

$increment = fn () => $this->count++;
$decrement = fn () => $this->count--;

$double = computed(fn () => $this->count * 2);
?>

<div>
    <h1>Count: {{ $count }}</h1>
    <h2>Double: {{ $this->double }}</h2>
    <button wire:click="increment">+</button>
    <button wire:click="decrement">-</button>
</div>
@endvolt
</code-snippet>

### Volt Class Based Component Example
To get started, define an anonymous class that extends Livewire\Volt\Component. Within the class, you may utilize all of the features of Livewire using traditional Livewire syntax:

<code-snippet name="Volt Class-based Volt Component Example" lang="php">
use Livewire\Volt\Component;

new class extends Component {
    public $count = 0;

    public function increment()
    {
        $this->count++;
    }
} ?>

<div>
    <h1>{{ $count }}</h1>
    <button wire:click="increment">+</button>
</div>
</code-snippet>

### Testing Volt & Volt Components
- Use the existing directory for tests if it already exists. Otherwise, fallback to `tests/Feature/Volt`.

<code-snippet name="Livewire Test Example" lang="php">
use Livewire\Volt\Volt;

test('counter increments', function () {
    Volt::test('counter')
        ->assertSee('Count: 0')
        ->call('increment')
        ->assertSee('Count: 1');
});
</code-snippet>

<code-snippet name="Volt Component Test Using Pest" lang="php">
declare(strict_types=1);

use App\Models\{User, Product};
use Livewire\Volt\Volt;

test('product form creates product', function () {
    $user = User::factory()->create();

    Volt::test('pages.products.create')
        ->actingAs($user)
        ->set('form.name', 'Test Product')
        ->set('form.description', 'Test Description')
        ->set('form.price', 99.99)
        ->call('create')
        ->assertHasNoErrors();

    expect(Product::where('name', 'Test Product')->exists())->toBeTrue();
});
</code-snippet>

### Common Patterns

<code-snippet name="CRUD With Volt" lang="php">
<?php

use App\Models\Product;
use function Livewire\Volt\{state, computed};

state(['editing' => null, 'search' => '']);

$products = computed(fn() => Product::when($this->search,
    fn($q) => $q->where('name', 'like', "%{$this->search}%")
)->get());

$edit = fn(Product $product) => $this->editing = $product->id;
$delete = fn(Product $product) => $product->delete();

?>

<!-- HTML / UI Here -->
</code-snippet>

<code-snippet name="Real-Time Search With Volt" lang="php">
    <flux:input
        wire:model.live.debounce.300ms="search"
        placeholder="Search..."
    />
</code-snippet>

<code-snippet name="Loading States With Volt" lang="php">
    <flux:button wire:click="save" wire:loading.attr="disabled">
        <span wire:loading.remove>Save</span>
        <span wire:loading>Saving...</span>
    </flux:button>
</code-snippet>

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest
### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest {name}`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests that have a lot of duplicated data. This is often the case when testing validation rules, so consider this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>

=== pest/v4 rules ===

## Pest 4

- Pest 4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest 4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v4 rules ===

## Tailwind CSS 4

- Always use Tailwind CSS v4; do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.

<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>

### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option; use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>
