<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('scopeUpcoming returns only future events', function () {
    CalendarEvent::factory()->count(2)->create([
        'starts_at' => now()->addDay(),
    ]);
    CalendarEvent::factory()->past()->create();

    expect(CalendarEvent::upcoming()->count())->toBe(2);
});

test('scopePast returns only past events', function () {
    CalendarEvent::factory()->create([
        'starts_at' => now()->addDay(),
    ]);
    CalendarEvent::factory()->count(2)->past()->create();

    expect(CalendarEvent::past()->count())->toBe(2);
});

test('getUpcoming returns limited results', function () {
    CalendarEvent::factory()->count(5)->create([
        'starts_at' => now()->addDay(),
    ]);

    expect(CalendarEvent::getUpcoming(3))->toHaveCount(3);
});

test('getUpcoming returns events ordered by starts_at', function () {
    CalendarEvent::factory()->create([
        'name' => 'Later',
        'starts_at' => now()->addDays(5),
    ]);
    CalendarEvent::factory()->create([
        'name' => 'Sooner',
        'starts_at' => now()->addDays(2),
    ]);

    $events = CalendarEvent::getUpcoming(2);

    expect($events->first()->name)->toBe('Sooner');
    expect($events->last()->name)->toBe('Later');
});

test('isPast returns true for past events', function () {
    $event = CalendarEvent::factory()->past()->create();

    expect($event->isPast())->toBeTrue();
});

test('isPast returns false for future events', function () {
    $event = CalendarEvent::factory()->create([
        'starts_at' => now()->addDay(),
    ]);

    expect($event->isPast())->toBeFalse();
});

test('countdown returns Past for past events', function () {
    $event = CalendarEvent::factory()->past()->create();

    expect($event->countdown)->toBe('Past');
});

test('countdown returns days for events more than 7 days away', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-04 12:00:00'));

    $event = CalendarEvent::factory()->create([
        'starts_at' => Carbon::parse('2026-01-15 12:00:00'),
    ]);

    expect($event->countdown)->toBe('11 days');

    Carbon::setTestNow();
});

test('countdown returns days and hours for events 2-7 days away', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-04 12:00:00'));

    $event = CalendarEvent::factory()->create([
        'starts_at' => Carbon::parse('2026-01-07 15:00:00'),
    ]);

    expect($event->countdown)->toBe('3 days, 3 hours');

    Carbon::setTestNow();
});

test('countdown returns hours and minutes for events less than 24 hours away', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-04 12:00:00'));

    $event = CalendarEvent::factory()->create([
        'starts_at' => Carbon::parse('2026-01-04 18:30:00'),
    ]);

    expect($event->countdown)->toBe('6 hours, 30 min');

    Carbon::setTestNow();
});

test('countdown returns minutes for events less than 1 hour away', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-04 12:00:00'));

    $event = CalendarEvent::factory()->create([
        'starts_at' => Carbon::parse('2026-01-04 12:45:00'),
    ]);

    expect($event->countdown)->toBe('45 minutes');

    Carbon::setTestNow();
});

test('birthday factory state works correctly', function () {
    $event = CalendarEvent::factory()->birthday()->create();

    expect($event->category)->toBe('birthday');
    expect($event->color)->toBe('#EC4899');
    expect($event->name)->toContain('Birthday');
});

test('hasDepartureTime returns false when not set', function () {
    $event = CalendarEvent::factory()->create([
        'departure_time' => null,
    ]);

    expect($event->hasDepartureTime())->toBeFalse();
});

test('hasDepartureTime returns true when set', function () {
    $event = CalendarEvent::factory()->withDepartureTime()->create();

    expect($event->hasDepartureTime())->toBeTrue();
});

test('getDepartureSecondsRemaining returns null when no departure time', function () {
    $event = CalendarEvent::factory()->create([
        'departure_time' => null,
    ]);

    expect($event->getDepartureSecondsRemaining())->toBeNull();
});

test('getDepartureSecondsRemaining returns positive seconds for future departure', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-04 12:00:00'));

    $event = CalendarEvent::factory()->create([
        'starts_at' => Carbon::parse('2026-01-04 15:00:00'),
        'departure_time' => Carbon::parse('2026-01-04 14:00:00'),
    ]);

    expect($event->getDepartureSecondsRemaining())->toBe(7200); // 2 hours = 7200 seconds

    Carbon::setTestNow();
});

test('getDepartureSecondsRemaining returns null for past departure', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-04 12:00:00'));

    $event = CalendarEvent::factory()->create([
        'starts_at' => Carbon::parse('2026-01-04 15:00:00'),
        'departure_time' => Carbon::parse('2026-01-04 11:00:00'),
    ]);

    expect($event->getDepartureSecondsRemaining())->toBeNull();

    Carbon::setTestNow();
});

test('withDepartureTime factory state works correctly', function () {
    $startsAt = now()->addHours(3);
    $event = CalendarEvent::factory()
        ->state(['starts_at' => $startsAt])
        ->withDepartureTime()
        ->create();

    expect($event->departure_time)->not->toBeNull();
    expect($event->departure_time->lt($event->starts_at))->toBeTrue();
});

