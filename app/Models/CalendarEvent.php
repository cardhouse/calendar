<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $starts_at
 * @property Carbon|null $departure_time
 */
class CalendarEvent extends Model
{
    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'starts_at',
        'departure_time',
        'category',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'departure_time' => 'datetime',
        ];
    }

    /**
     * Scope to upcoming events only.
     *
     * @param  Builder<CalendarEvent>  $query
     * @return Builder<CalendarEvent>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now())
            ->orderBy('starts_at');
    }

    /**
     * Scope to past events.
     *
     * @param  Builder<CalendarEvent>  $query
     * @return Builder<CalendarEvent>
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('starts_at', '<=', now())
            ->orderByDesc('starts_at');
    }

    /**
     * Get the next N upcoming events.
     *
     * @return Collection<int, CalendarEvent>
     */
    public static function getUpcoming(int $limit = 3): Collection
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
            return $diff->days.' days';
        }

        if ($diff->days >= 2) {
            return $diff->days.' days, '.$diff->h.' hours';
        }

        if ($diff->days === 1) {
            return '1 day, '.$diff->h.' hours';
        }

        if ($diff->h > 0) {
            return $diff->h.' hours, '.$diff->i.' min';
        }

        return $diff->i.' minutes';
    }

    /**
     * Check if event has a departure time set.
     */
    public function hasDepartureTime(): bool
    {
        return $this->departure_time !== null;
    }

    /**
     * Get event routine items for this calendar event.
     *
     * @return MorphMany<EventRoutineItem, $this>
     */
    public function eventRoutineItems(): MorphMany
    {
        return $this->morphMany(EventRoutineItem::class, 'eventable')->orderBy('display_order');
    }

    /**
     * Get seconds remaining until departure time.
     * Returns null if no departure time set or departure time has passed.
     */
    public function getDepartureSecondsRemaining(): ?int
    {
        if (! $this->hasDepartureTime()) {
            return null;
        }

        $seconds = (int) now()->diffInSeconds($this->departure_time, false);

        return $seconds > 0 ? $seconds : null;
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (CalendarEvent $event) {
            $event->eventRoutineItems()->delete();
        });
    }
}
