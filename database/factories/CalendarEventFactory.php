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

    /** @var array<int, string> */
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

    /** @var array<int, string> */
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
            'name' => fake()->randomElement($this->eventNames),
            'starts_at' => fake()->dateTimeBetween('now', '+30 days'),
            'category' => fake()->randomElement($this->categories),
            'color' => fake()->hexColor(),
        ];
    }

    public function birthday(): static
    {
        return $this->state(fn () => [
            'name' => fake()->firstName()."'s Birthday",
            'category' => 'birthday',
            'color' => '#EC4899',
        ]);
    }

    public function past(): static
    {
        return $this->state(fn () => [
            'starts_at' => fake()->dateTimeBetween('-30 days', '-1 hour'),
        ]);
    }

    /**
     * Set a departure time for this event.
     * Defaults to 1 hour before the event starts.
     */
    public function withDepartureTime(?\DateTime $time = null): static
    {
        return $this->state(fn (array $attributes) => [
            'departure_time' => $time ?? \Carbon\Carbon::parse($attributes['starts_at'])->subHour(),
        ]);
    }
}
