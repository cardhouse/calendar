<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Child;
use App\Models\DepartureTime;
use App\Models\EventRoutineItem;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('event routines admin page loads successfully', function () {
    $this->get(route('admin.event-routines.index'))
        ->assertStatus(200);
});

test('event routines page displays departure times', function () {
    $departure = DepartureTime::factory()->create(['name' => 'School Bus']);

    Volt::test('admin.event-routines')
        ->assertSee('School Bus');
});

test('event routines page displays calendar events', function () {
    $event = CalendarEvent::factory()->create([
        'name' => 'Birthday Party',
        'starts_at' => now()->addDays(5),
    ]);

    Volt::test('admin.event-routines')
        ->call('setTab', 'events')
        ->assertSee('Birthday Party');
});

test('can select a departure time to manage routines', function () {
    $departure = DepartureTime::factory()->create(['name' => 'School Bus']);
    Child::factory()->create(['name' => 'Emma']);

    $component = Volt::test('admin.event-routines')
        ->call('selectEventable', 'departure', $departure->id)
        ->assertSet('selectedEventableId', $departure->id)
        ->assertSet('selectedEventableType', 'departure');

    // The computed property should return the departure
    expect($component->get('selectedEventable')->name)->toBe('School Bus');
});

test('can select a calendar event to manage routines', function () {
    $event = CalendarEvent::factory()->create([
        'name' => 'Soccer Game',
        'starts_at' => now()->addDays(3),
    ]);

    $component = Volt::test('admin.event-routines')
        ->call('setTab', 'events')
        ->call('selectEventable', 'event', $event->id)
        ->assertSet('selectedEventableId', $event->id)
        ->assertSet('selectedEventableType', 'event');

    // The computed property should return the event
    expect($component->get('selectedEventable')->name)->toBe('Soccer Game');
});

test('can create a routine item for a departure time', function () {
    $departure = DepartureTime::factory()->create(['name' => 'School Bus']);
    $child = Child::factory()->create(['name' => 'Emma']);

    Volt::test('admin.event-routines')
        ->call('selectEventable', 'departure', $departure->id)
        ->call('create')
        ->set('childId', $child->id)
        ->set('name', 'Pack backpack')
        ->set('displayOrder', 1)
        ->call('save')
        ->assertHasNoErrors();

    expect(EventRoutineItem::where('name', 'Pack backpack')->exists())->toBeTrue();
    expect(EventRoutineItem::where('name', 'Pack backpack')->first())
        ->child_id->toBe($child->id)
        ->eventable_type->toBe(DepartureTime::class)
        ->eventable_id->toBe($departure->id);
});

test('can create a routine item for a calendar event', function () {
    $event = CalendarEvent::factory()->create([
        'name' => 'Bowling',
        'starts_at' => now()->addDays(2),
    ]);
    $child = Child::factory()->create(['name' => 'Jack']);

    Volt::test('admin.event-routines')
        ->call('setTab', 'events')
        ->call('selectEventable', 'event', $event->id)
        ->call('create')
        ->set('childId', $child->id)
        ->set('name', 'Get bowling ball')
        ->set('displayOrder', 1)
        ->call('save')
        ->assertHasNoErrors();

    expect(EventRoutineItem::where('name', 'Get bowling ball')->exists())->toBeTrue();
    expect(EventRoutineItem::where('name', 'Get bowling ball')->first())
        ->child_id->toBe($child->id)
        ->eventable_type->toBe(CalendarEvent::class)
        ->eventable_id->toBe($event->id);
});

test('can edit a routine item', function () {
    $departure = DepartureTime::factory()->create();
    $child = Child::factory()->create();
    $item = EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->create(['name' => 'Original Name']);

    Volt::test('admin.event-routines')
        ->call('selectEventable', 'departure', $departure->id)
        ->call('edit', $item->id)
        ->assertSet('editingId', $item->id)
        ->assertSet('name', 'Original Name')
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($item->fresh()->name)->toBe('Updated Name');
});

test('can delete a routine item', function () {
    $departure = DepartureTime::factory()->create();
    $child = Child::factory()->create();
    $item = EventRoutineItem::factory()
        ->forDepartureTime($departure)
        ->forChild($child)
        ->create(['name' => 'To Delete']);

    Volt::test('admin.event-routines')
        ->call('selectEventable', 'departure', $departure->id)
        ->call('confirmDelete', $item->id)
        ->assertSet('showDeleteConfirm', true)
        ->call('delete');

    expect(EventRoutineItem::find($item->id))->toBeNull();
});

test('validation requires name', function () {
    $departure = DepartureTime::factory()->create();
    $child = Child::factory()->create();

    Volt::test('admin.event-routines')
        ->call('selectEventable', 'departure', $departure->id)
        ->call('create')
        ->set('childId', $child->id)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('validation requires child', function () {
    $departure = DepartureTime::factory()->create();

    Volt::test('admin.event-routines')
        ->call('selectEventable', 'departure', $departure->id)
        ->call('create')
        ->set('childId', 999999)
        ->set('name', 'Test')
        ->call('save')
        ->assertHasErrors(['childId']);
});

test('switching tabs clears selection', function () {
    $departure = DepartureTime::factory()->create();

    Volt::test('admin.event-routines')
        ->call('selectEventable', 'departure', $departure->id)
        ->assertSet('selectedEventableId', $departure->id)
        ->call('setTab', 'events')
        ->assertSet('selectedEventableId', null)
        ->assertSet('selectedEventableType', null);
});

test('cancel clears form state', function () {
    $departure = DepartureTime::factory()->create();
    $child = Child::factory()->create();

    Volt::test('admin.event-routines')
        ->call('selectEventable', 'departure', $departure->id)
        ->call('create')
        ->set('name', 'Test Item')
        ->call('cancel')
        ->assertSet('showForm', false)
        ->assertSet('name', '');
});

