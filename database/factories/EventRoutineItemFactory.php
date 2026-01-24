<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\Child;
use App\Models\DepartureTime;
use App\Models\EventRoutineItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRoutineItem>
 */
class EventRoutineItemFactory extends Factory
{
    protected $model = EventRoutineItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'eventable_type' => DepartureTime::class,
            'eventable_id' => DepartureTime::factory(),
            'child_id' => Child::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'display_order' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Associate with a CalendarEvent.
     */
    public function forCalendarEvent(?CalendarEvent $event = null): static
    {
        return $this->state(fn () => [
            'eventable_type' => CalendarEvent::class,
            'eventable_id' => $event?->id ?? CalendarEvent::factory(),
        ]);
    }

    /**
     * Associate with a DepartureTime.
     */
    public function forDepartureTime(?DepartureTime $departure = null): static
    {
        return $this->state(fn () => [
            'eventable_type' => DepartureTime::class,
            'eventable_id' => $departure?->id ?? DepartureTime::factory(),
        ]);
    }

    /**
     * Associate with a specific child.
     */
    public function forChild(Child $child): static
    {
        return $this->state(fn () => [
            'child_id' => $child->id,
        ]);
    }
}
