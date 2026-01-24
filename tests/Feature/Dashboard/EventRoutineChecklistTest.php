<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Child;
use App\Models\DepartureTime;
use App\Models\EventRoutineItem;
use App\Models\RoutineItem;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('child checklist shows event routine items for next event', function () {
    $child = Child::factory()->create(['name' => 'Emma']);

    $departure = DepartureTime::factory()->create([
        'name' => 'School Bus',
        'departure_time' => now()->addHours(2)->format('H:i:s'),
        'applicable_days' => [strtolower(now()->format('l'))],
        'is_active' => true,
    ]);

    $eventItem = EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->create(['name' => 'Pack backpack']);

    // Load event routine items with completions (as dashboard would)
    $eventRoutineItems = EventRoutineItem::where('child_id', $child->id)
        ->with(['completions' => fn ($q) => $q->whereDate('completion_date', today())])
        ->get();

    Volt::test('dashboard.child-checklist', [
        'child' => $child,
        'eventRoutineItems' => $eventRoutineItems,
        'eventName' => 'School Bus',
    ])
        ->assertSee('For: School Bus')
        ->assertSee('Pack backpack');
});

test('child checklist does not show event routines when no upcoming event', function () {
    $child = Child::factory()->create(['name' => 'Emma']);

    // Create a departure but no routines
    DepartureTime::factory()->create([
        'name' => 'School Bus',
        'departure_time' => now()->addHours(2)->format('H:i:s'),
        'applicable_days' => [strtolower(now()->format('l'))],
        'is_active' => true,
    ]);

    Volt::test('dashboard.child-checklist', ['child' => $child])
        ->assertDontSee('For:');
});

test('event routine items can be toggled', function () {
    $child = Child::factory()->create(['name' => 'Emma']);

    $departure = DepartureTime::factory()->create([
        'name' => 'School Bus',
        'departure_time' => now()->addHours(2)->format('H:i:s'),
        'applicable_days' => [strtolower(now()->format('l'))],
        'is_active' => true,
    ]);

    $eventItem = EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->create(['name' => 'Pack backpack']);

    // Load event routine items with completions (as dashboard would)
    $eventRoutineItems = EventRoutineItem::where('child_id', $child->id)
        ->with(['completions' => fn ($q) => $q->whereDate('completion_date', today())])
        ->get();

    Volt::test('dashboard.child-checklist', [
        'child' => $child,
        'eventRoutineItems' => $eventRoutineItems,
        'eventName' => 'School Bus',
    ])
        ->call('toggleEventItem', $eventItem->id);

    expect($eventItem->fresh()->isCompletedFor())->toBeTrue();
});

test('progress includes event routine items', function () {
    $child = Child::factory()
        ->has(RoutineItem::factory()->count(2))
        ->create(['name' => 'Emma']);

    // Load child with routine items and completions
    $child->load(['routineItems.completions' => fn ($q) => $q->whereDate('completion_date', today())]);

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

    // Load event routine items with completions (as dashboard would)
    $eventRoutineItems = EventRoutineItem::where('child_id', $child->id)
        ->with(['completions' => fn ($q) => $q->whereDate('completion_date', today())])
        ->get();

    // 2 daily + 1 event = 3 total
    Volt::test('dashboard.child-checklist', [
        'child' => $child,
        'eventRoutineItems' => $eventRoutineItems,
        'eventName' => 'School Bus',
    ])
        ->assertSee('0/3');
});

test('all complete celebration includes event items', function () {
    $child = Child::factory()->create(['name' => 'Emma']);

    // Create daily routine item and mark complete
    $dailyItem = RoutineItem::factory()->create([
        'child_id' => $child->id,
        'name' => 'Daily task',
    ]);
    $dailyItem->markComplete();

    $departure = DepartureTime::factory()->create([
        'name' => 'School Bus',
        'departure_time' => now()->addHours(2)->format('H:i:s'),
        'applicable_days' => [strtolower(now()->format('l'))],
        'is_active' => true,
    ]);

    $eventItem = EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->create(['name' => 'Pack backpack']);

    // Load child with completions for proper state
    $child->load(['routineItems.completions' => function ($q) {
        $q->whereDate('completion_date', today());
    }]);

    // Load event routine items with completions (as dashboard would)
    $eventRoutineItems = EventRoutineItem::where('child_id', $child->id)
        ->with(['completions' => fn ($q) => $q->whereDate('completion_date', today())])
        ->get();

    // Not complete yet (event item not done)
    Volt::test('dashboard.child-checklist', [
        'child' => $child,
        'eventRoutineItems' => $eventRoutineItems,
        'eventName' => 'School Bus',
    ])
        ->assertDontSee('All done!');

    // Mark event item complete
    $eventItem->markComplete();

    // Reload child and event routine items
    $child->refresh();
    $child->load(['routineItems.completions' => function ($q) {
        $q->whereDate('completion_date', today());
    }]);

    $eventRoutineItems = EventRoutineItem::where('child_id', $child->id)
        ->with(['completions' => fn ($q) => $q->whereDate('completion_date', today())])
        ->get();

    Volt::test('dashboard.child-checklist', [
        'child' => $child,
        'eventRoutineItems' => $eventRoutineItems,
        'eventName' => 'School Bus',
    ])
        ->assertSee('All done!');
});

test('only shows routines for next upcoming event', function () {
    $child = Child::factory()->create(['name' => 'Emma']);

    // Closer departure (1 hour)
    $closerDeparture = DepartureTime::factory()->create([
        'name' => 'School Bus',
        'departure_time' => now()->addHour()->format('H:i:s'),
        'applicable_days' => [strtolower(now()->format('l'))],
        'is_active' => true,
    ]);

    // Farther event (tomorrow)
    $fartherEvent = CalendarEvent::factory()->create([
        'name' => 'Bowling',
        'starts_at' => now()->addDay(),
    ]);

    EventRoutineItem::factory()
        ->forDepartureTime($closerDeparture)
        ->forChild($child)
        ->create(['name' => 'School item']);

    EventRoutineItem::factory()
        ->forCalendarEvent($fartherEvent)
        ->forChild($child)
        ->create(['name' => 'Bowling item']);

    // Load only the event routine items for the closer departure (as dashboard would)
    $eventRoutineItems = EventRoutineItem::where('child_id', $child->id)
        ->where('eventable_type', DepartureTime::class)
        ->where('eventable_id', $closerDeparture->id)
        ->with(['completions' => fn ($q) => $q->whereDate('completion_date', today())])
        ->get();

    // Should only show School Bus routines
    Volt::test('dashboard.child-checklist', [
        'child' => $child,
        'eventRoutineItems' => $eventRoutineItems,
        'eventName' => 'School Bus',
    ])
        ->assertSee('For: School Bus')
        ->assertSee('School item')
        ->assertDontSee('Bowling item');
});

