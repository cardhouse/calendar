<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Child;
use App\Models\DepartureTime;
use App\Models\EventRoutineCompletion;
use App\Models\EventRoutineItem;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('event routine item belongs to a child', function () {
    $child = Child::factory()->create();
    $item = EventRoutineItem::factory()
        ->forChild($child)
        ->create();

    expect($item->child->id)->toBe($child->id);
});

test('event routine item can be associated with departure time', function () {
    $departure = DepartureTime::factory()->create();
    $item = EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->create();

    expect($item->eventable)->toBeInstanceOf(DepartureTime::class);
    expect($item->eventable->id)->toBe($departure->id);
});

test('event routine item can be associated with calendar event', function () {
    $event = CalendarEvent::factory()->create();
    $item = EventRoutineItem::factory()
        ->forCalendarEvent($event)
        ->create();

    expect($item->eventable)->toBeInstanceOf(CalendarEvent::class);
    expect($item->eventable->id)->toBe($event->id);
});

test('event routine item can be marked complete', function () {
    $item = EventRoutineItem::factory()->create();

    expect($item->isCompletedFor())->toBeFalse();

    $item->markComplete();

    expect($item->isCompletedFor())->toBeTrue();
    expect(EventRoutineCompletion::where('event_routine_item_id', $item->id)->exists())->toBeTrue();
});

test('event routine item can be marked incomplete', function () {
    $item = EventRoutineItem::factory()->create();
    $item->markComplete();

    expect($item->isCompletedFor())->toBeTrue();

    $item->markIncomplete();

    expect($item->isCompletedFor())->toBeFalse();
});

test('event routine item completion can be toggled', function () {
    $item = EventRoutineItem::factory()->create();

    expect($item->toggleCompletion())->toBeTrue();
    expect($item->isCompletedFor())->toBeTrue();

    expect($item->toggleCompletion())->toBeFalse();
    expect($item->isCompletedFor())->toBeFalse();
});

test('event routine item completion is per date', function () {
    $item = EventRoutineItem::factory()->create();

    // Complete for today
    $item->markComplete();
    expect($item->isCompletedFor(today()))->toBeTrue();

    // Not completed for tomorrow
    expect($item->isCompletedFor(today()->addDay()))->toBeFalse();
});

test('event name attribute returns event name', function () {
    $departure = DepartureTime::factory()->create(['name' => 'School Bus']);
    $item = EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->create();

    expect($item->event_name)->toBe('School Bus');
});

test('departure time has many event routine items', function () {
    $departure = DepartureTime::factory()->create();
    $child = Child::factory()->create();

    EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->count(3)
        ->create();

    expect($departure->eventRoutineItems)->toHaveCount(3);
});

test('calendar event has many event routine items', function () {
    $event = CalendarEvent::factory()->create();
    $child = Child::factory()->create();

    EventRoutineItem::factory()
        ->forCalendarEvent($event)
        ->forChild($child)
        ->count(2)
        ->create();

    expect($event->eventRoutineItems)->toHaveCount(2);
});

test('child has many event routine items', function () {
    $child = Child::factory()->create();
    $departure = DepartureTime::factory()->create();

    EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->count(4)
        ->create();

    expect($child->eventRoutineItems)->toHaveCount(4);
});

test('deleting child cascades to event routine items', function () {
    $child = Child::factory()->create();
    $departure = DepartureTime::factory()->create();
    $item = EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->create();

    $itemId = $item->id;
    $child->delete();

    expect(EventRoutineItem::find($itemId))->toBeNull();
});

test('deleting departure time cascades to event routine items', function () {
    $departure = DepartureTime::factory()->create();
    $child = Child::factory()->create();
    $item = EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->create();

    $itemId = $item->id;
    $departure->delete();

    expect(EventRoutineItem::find($itemId))->toBeNull();
});

test('deleting calendar event cascades to event routine items', function () {
    $event = CalendarEvent::factory()->create();
    $child = Child::factory()->create();
    $item = EventRoutineItem::factory()
        ->forCalendarEvent($event)
        ->forChild($child)
        ->create();

    $itemId = $item->id;
    $event->delete();

    expect(EventRoutineItem::find($itemId))->toBeNull();
});

