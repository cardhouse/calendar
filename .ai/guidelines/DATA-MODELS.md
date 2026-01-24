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
