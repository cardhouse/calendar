<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ChildFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Child extends Model
{
    /** @use HasFactory<ChildFactory> */
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
     * @return HasMany<EventRoutineItem, $this>
     */
    public function eventRoutineItems(): HasMany
    {
        return $this->hasMany(EventRoutineItem::class)->orderBy('display_order');
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

    /**
     * Get the next upcoming event (CalendarEvent or DepartureTime) and return its routine items for this child.
     * Only returns items if the ACTUAL next event has routines - does not skip to later events.
     *
     * @return array{event: CalendarEvent|DepartureTime, items: Collection<int, EventRoutineItem>}|null
     */
    public function getNextEventWithRoutines(): ?array
    {
        $now = now();

        // Collect ALL upcoming events with their next occurrence times
        $candidates = [];

        // Get ALL upcoming CalendarEvents (not just those with routines)
        $calendarEvents = CalendarEvent::where(function ($query) use ($now) {
            // Event hasn't passed yet (use departure_time if set, otherwise starts_at)
            $query->where(function ($q) use ($now) {
                $q->whereNotNull('departure_time')
                    ->where('departure_time', '>', $now);
            })->orWhere(function ($q) use ($now) {
                $q->whereNull('departure_time')
                    ->where('starts_at', '>', $now);
            });
        })
            ->with(['eventRoutineItems' => fn ($q) => $q->where('child_id', $this->id)])
            ->get();

        foreach ($calendarEvents as $event) {
            $nextTime = $event->departure_time ?? $event->starts_at;
            $candidates[] = [
                'event' => $event,
                'next_time' => $nextTime,
                'items' => $event->eventRoutineItems,
            ];
        }

        // Get ALL active DepartureTimes (not just those with routines)
        $departureTimes = DepartureTime::active()
            ->with(['eventRoutineItems' => fn ($q) => $q->where('child_id', $this->id)])
            ->get();

        foreach ($departureTimes as $departure) {
            $nextOccurrence = $departure->getNextOccurrence();
            if ($nextOccurrence) {
                $candidates[] = [
                    'event' => $departure,
                    'next_time' => $nextOccurrence,
                    'items' => $departure->eventRoutineItems,
                ];
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // Sort by next_time and get the soonest
        usort($candidates, fn ($a, $b) => $a['next_time']->timestamp <=> $b['next_time']->timestamp);

        $next = $candidates[0];

        // Only return if the next event has routine items for this child
        if ($next['items']->isEmpty()) {
            return null;
        }

        return [
            'event' => $next['event'],
            'items' => $next['items'],
        ];
    }
}
