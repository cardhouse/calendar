<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Child;
use App\Models\DepartureTime;
use App\Models\EventRoutineItem;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('child gets next event with routines from departure time', function () {
    $child = Child::factory()->create();
    $departure = DepartureTime::factory()->create([
        'name' => 'School Bus',
        'departure_time' => now()->addHours(2)->format('H:i:s'),
        'applicable_days' => [strtolower(now()->format('l'))],
        'is_active' => true,
    ]);

    EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->create(['name' => 'Pack backpack']);

    $result = $child->getNextEventWithRoutines();

    expect($result)->not->toBeNull();
    expect($result['event']->name)->toBe('School Bus');
    expect($result['items'])->toHaveCount(1);
    expect($result['items']->first()->name)->toBe('Pack backpack');
});

test('child gets next event with routines from calendar event', function () {
    $child = Child::factory()->create();
    $event = CalendarEvent::factory()->create([
        'name' => 'Bowling',
        'starts_at' => now()->addDays(2),
    ]);

    EventRoutineItem::factory()
        ->forCalendarEvent($event)
        ->forChild($child)
        ->create(['name' => 'Get bowling ball']);

    $result = $child->getNextEventWithRoutines();

    expect($result)->not->toBeNull();
    expect($result['event']->name)->toBe('Bowling');
    expect($result['items'])->toHaveCount(1);
    expect($result['items']->first()->name)->toBe('Get bowling ball');
});

test('child gets soonest event when multiple events have routines', function () {
    $child = Child::factory()->create();

    // Create a departure that happens today
    $departure = DepartureTime::factory()->create([
        'name' => 'School Bus',
        'departure_time' => now()->addHours(1)->format('H:i:s'),
        'applicable_days' => [strtolower(now()->format('l'))],
        'is_active' => true,
    ]);

    // Create a calendar event tomorrow
    $event = CalendarEvent::factory()->create([
        'name' => 'Bowling',
        'starts_at' => now()->addDays(1),
    ]);

    EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->create(['name' => 'Pack backpack']);

    EventRoutineItem::factory()
        ->forCalendarEvent($event)
        ->forChild($child)
        ->create(['name' => 'Get bowling ball']);

    $result = $child->getNextEventWithRoutines();

    // Should get the departure since it's sooner
    expect($result['event']->name)->toBe('School Bus');
});

test('child returns null when no events have routines', function () {
    $child = Child::factory()->create();
    DepartureTime::factory()->create();
    CalendarEvent::factory()->create(['starts_at' => now()->addDays(5)]);

    $result = $child->getNextEventWithRoutines();

    expect($result)->toBeNull();
});

test('child only gets routines for their own events', function () {
    $child1 = Child::factory()->create(['name' => 'Emma']);
    $child2 = Child::factory()->create(['name' => 'Jack']);

    $departure = DepartureTime::factory()->create([
        'name' => 'School Bus',
        'departure_time' => now()->addHours(2)->format('H:i:s'),
        'applicable_days' => [strtolower(now()->format('l'))],
        'is_active' => true,
    ]);

    // Create routines for both children
    EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child1)
        ->create(['name' => 'Emma item']);

    EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child2)
        ->create(['name' => 'Jack item']);

    $result = $child1->getNextEventWithRoutines();

    // Emma should only see her own routines
    expect($result['items'])->toHaveCount(1);
    expect($result['items']->first()->name)->toBe('Emma item');
});

test('past calendar events are not included', function () {
    $child = Child::factory()->create();

    $pastEvent = CalendarEvent::factory()->create([
        'name' => 'Past Event',
        'starts_at' => now()->subDay(),
    ]);

    EventRoutineItem::factory()
        ->forCalendarEvent($pastEvent)
        ->forChild($child)
        ->create(['name' => 'Past item']);

    $result = $child->getNextEventWithRoutines();

    expect($result)->toBeNull();
});

test('inactive departure times are not included', function () {
    $child = Child::factory()->create();

    $departure = DepartureTime::factory()->create([
        'name' => 'Inactive Bus',
        'departure_time' => now()->addHours(2)->format('H:i:s'),
        'applicable_days' => [strtolower(now()->format('l'))],
        'is_active' => false,
    ]);

    EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->create(['name' => 'Inactive item']);

    $result = $child->getNextEventWithRoutines();

    expect($result)->toBeNull();
});

test('departure times not applicable today are not included for today', function () {
    $child = Child::factory()->create();
    $tomorrow = now()->addDay()->format('l');

    $departure = DepartureTime::factory()->create([
        'name' => 'Tomorrow Bus',
        'departure_time' => now()->addHours(2)->format('H:i:s'),
        'applicable_days' => [strtolower($tomorrow)], // Only applicable tomorrow
        'is_active' => true,
    ]);

    EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->create(['name' => 'Tomorrow item']);

    $result = $child->getNextEventWithRoutines();

    // Should still show, but the next occurrence is tomorrow
    expect($result)->not->toBeNull();
    expect($result['event']->name)->toBe('Tomorrow Bus');
});

test('calendar event with departure time uses departure time for sorting', function () {
    $child = Child::factory()->create();

    // Event starts in 5 hours but departure is in 4 hours
    $event = CalendarEvent::factory()->create([
        'name' => 'Event with Departure',
        'starts_at' => now()->addHours(5),
        'departure_time' => now()->addHours(4),
    ]);

    // Another event starts in 3 hours but has no departure time
    $event2 = CalendarEvent::factory()->create([
        'name' => 'Event without Departure',
        'starts_at' => now()->addHours(3),
        'departure_time' => null,
    ]);

    EventRoutineItem::factory()
        ->forCalendarEvent($event)
        ->forChild($child)
        ->create(['name' => 'Item 1']);

    EventRoutineItem::factory()
        ->forCalendarEvent($event2)
        ->forChild($child)
        ->create(['name' => 'Item 2']);

    $result = $child->getNextEventWithRoutines();

    // Event2 should come first because starts_at (3 hours) < departure_time (4 hours)
    expect($result['event']->name)->toBe('Event without Departure');
});

test('returns null when next event has no routines even if later events do', function () {
    $child = Child::factory()->create();

    // Bills Game is the next event (in 2 hours) but has NO routines for this child
    CalendarEvent::factory()->create([
        'name' => 'Bills Game',
        'starts_at' => now()->addHours(2),
    ]);

    // School Bus is later (in 24 hours) and HAS routines
    $departure = DepartureTime::factory()->create([
        'name' => 'School Bus',
        'departure_time' => now()->addDay()->format('H:i:s'),
        'applicable_days' => [strtolower(now()->addDay()->format('l'))],
        'is_active' => true,
    ]);

    EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->create(['name' => 'Pack backpack']);

    $result = $child->getNextEventWithRoutines();

    // Should return null because Bills Game is next, even though School Bus has routines
    expect($result)->toBeNull();
});

test('shows routines only when the next event is the one with routines', function () {
    $child = Child::factory()->create();

    // School Bus is the next event (in 1 hour) and HAS routines
    $departure = DepartureTime::factory()->create([
        'name' => 'School Bus',
        'departure_time' => now()->addHour()->format('H:i:s'),
        'applicable_days' => [strtolower(now()->format('l'))],
        'is_active' => true,
    ]);

    // Bills Game is later (in 5 hours) with no routines
    CalendarEvent::factory()->create([
        'name' => 'Bills Game',
        'starts_at' => now()->addHours(5),
    ]);

    EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->create(['name' => 'Pack backpack']);

    $result = $child->getNextEventWithRoutines();

    // Should show School Bus routines since it's the next event AND has routines
    expect($result)->not->toBeNull();
    expect($result['event']->name)->toBe('School Bus');
    expect($result['items']->first()->name)->toBe('Pack backpack');
});

