<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DepartureTimeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $departure_time
 * @property array<int, string> $applicable_days
 * @property bool $is_active
 * @property int $display_order
 */
class DepartureTime extends Model
{
    /** @use HasFactory<DepartureTimeFactory> */
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
     *
     * @param  Builder<DepartureTime>  $query
     * @return Builder<DepartureTime>
     */
    public function scopeActive(Builder $query): Builder
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
            ->filter(fn($d) => $d->getSecondsRemaining() > 0)
            ->sortBy(fn($d) => $d->getSecondsRemaining())
            ->first();
    }

    /**
     * Get event routine items for this departure time.
     *
     * @return MorphMany<EventRoutineItem, $this>
     */
    public function eventRoutineItems(): MorphMany
    {
        return $this->morphMany(EventRoutineItem::class, 'eventable')->orderBy('display_order');
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (DepartureTime $departure) {
            $departure->eventRoutineItems()->delete();
        });
    }
}
