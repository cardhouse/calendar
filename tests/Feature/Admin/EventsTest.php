<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('events admin page loads successfully', function () {
    $this->get(route('admin.events.index'))
        ->assertSuccessful();
});

test('events are listed', function () {
    CalendarEvent::factory()->create([
        'name' => 'Birthday Party',
        'starts_at' => now()->addDays(5),
    ]);

    $this->get(route('admin.events.index'))
        ->assertSuccessful()
        ->assertSee('Birthday Party');
});

test('can create a new event', function () {
    $startsAt = now()->addDays(5)->format('Y-m-d\TH:i');

    Livewire::test('pages::admin.events')
        ->call('create')
        ->set('name', 'Birthday Party')
        ->set('startsAt', $startsAt)
        ->set('category', 'birthday')
        ->set('color', '#EC4899')
        ->call('save');

    expect(CalendarEvent::where('name', 'Birthday Party')->exists())->toBeTrue();
});

test('can edit an event', function () {
    $event = CalendarEvent::factory()->create(['name' => 'Birthday Party']);

    Livewire::test('pages::admin.events')
        ->call('edit', $event->id)
        ->assertSet('name', 'Birthday Party')
        ->set('name', 'Birthday Bash')
        ->call('save');

    expect($event->fresh()->name)->toBe('Birthday Bash');
});

test('can delete an event', function () {
    $event = CalendarEvent::factory()->create();

    Livewire::test('pages::admin.events')
        ->call('confirmDelete', $event->id)
        ->call('delete');

    expect(CalendarEvent::find($event->id))->toBeNull();
});

test('can filter events by upcoming', function () {
    CalendarEvent::factory()->create([
        'name' => 'Future Event',
        'starts_at' => now()->addDays(5),
    ]);
    CalendarEvent::factory()->past()->create([
        'name' => 'Past Event',
    ]);

    Livewire::test('pages::admin.events')
        ->assertSee('Future Event')
        ->assertDontSee('Past Event');
});

test('can filter events by past', function () {
    CalendarEvent::factory()->create([
        'name' => 'Future Event',
        'starts_at' => now()->addDays(5),
    ]);
    CalendarEvent::factory()->past()->create([
        'name' => 'Past Event',
    ]);

    Livewire::test('pages::admin.events')
        ->call('setFilter', 'past')
        ->assertSee('Past Event')
        ->assertDontSee('Future Event');
});

test('validation requires name', function () {
    Livewire::test('pages::admin.events')
        ->call('create')
        ->set('name', '')
        ->set('startsAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['name']);
});

test('can create event with departure time', function () {
    $startsAt = now()->addDays(5)->format('Y-m-d\TH:i');
    $departureTime = now()->addDays(5)->subHour()->format('Y-m-d\TH:i');

    Livewire::test('pages::admin.events')
        ->call('create')
        ->set('name', 'Doctor Appointment')
        ->set('startsAt', $startsAt)
        ->set('departureTime', $departureTime)
        ->set('category', 'appointment')
        ->call('save');

    $event = CalendarEvent::where('name', 'Doctor Appointment')->first();
    expect($event)->not->toBeNull();
    expect($event->departure_time)->not->toBeNull();
});

test('can edit event departure time', function () {
    $event = CalendarEvent::factory()->create([
        'name' => 'Appointment',
        'starts_at' => now()->addDays(5),
        'departure_time' => null,
    ]);

    $departureTime = now()->addDays(5)->subHour()->format('Y-m-d\TH:i');

    Livewire::test('pages::admin.events')
        ->call('edit', $event->id)
        ->assertSet('departureTime', '')
        ->set('departureTime', $departureTime)
        ->call('save');

    expect($event->fresh()->departure_time)->not->toBeNull();
});

test('can clear event departure time', function () {
    $event = CalendarEvent::factory()->withDepartureTime()->create();

    Livewire::test('pages::admin.events')
        ->call('edit', $event->id)
        ->set('departureTime', '')
        ->call('save');

    expect($event->fresh()->departure_time)->toBeNull();
});

test('departure time must be before event start time', function () {
    $startsAt = now()->addDay()->format('Y-m-d\TH:i');
    $departureTime = now()->addDays(2)->format('Y-m-d\TH:i'); // After event

    Livewire::test('pages::admin.events')
        ->call('create')
        ->set('name', 'Test Event')
        ->set('startsAt', $startsAt)
        ->set('departureTime', $departureTime)
        ->call('save')
        ->assertHasErrors(['departureTime']);
});
