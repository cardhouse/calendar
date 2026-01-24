<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\DepartureTime;
use App\Services\DepartureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('returns null when no departures exist', function () {
    $service = new DepartureService;

    expect($service->getNextDeparture())->toBeNull();
});

test('returns DepartureTime when no event departures exist', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(6, 0, 0));

    DepartureTime::factory()->create([
        'name' => 'School bus',
        'departure_time' => '08:00:00',
        'applicable_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'is_active' => true,
    ]);

    $service = new DepartureService;
    $result = $service->getNextDeparture();

    expect($result)->not->toBeNull();
    expect($result['name'])->toBe('School bus');
    expect($result['source'])->toBe('departure_time');

    Carbon::setTestNow();
});

test('returns CalendarEvent departure when no recurring departures exist', function () {
    $event = CalendarEvent::factory()->withDepartureTime()->create([
        'name' => 'Doctor Appointment',
        'starts_at' => now()->addHours(3),
        'departure_time' => now()->addHours(2),
    ]);

    $service = new DepartureService;
    $result = $service->getNextDeparture();

    expect($result)->not->toBeNull();
    expect($result['name'])->toBe('Doctor Appointment');
    expect($result['source'])->toBe('calendar_event');
});

test('returns soonest when DepartureTime is sooner than event', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(7, 0, 0));

    // Recurring departure in 1 hour (8:00 AM)
    DepartureTime::factory()->create([
        'name' => 'School bus',
        'departure_time' => '08:00:00',
        'applicable_days' => ['monday'],
        'is_active' => true,
    ]);

    // Event departure in 3 hours
    CalendarEvent::factory()->create([
        'name' => 'Doctor Appointment',
        'starts_at' => now()->addHours(4),
        'departure_time' => now()->addHours(3),
    ]);

    $service = new DepartureService;
    $result = $service->getNextDeparture();

    expect($result['name'])->toBe('School bus');
    expect($result['source'])->toBe('departure_time');

    Carbon::setTestNow();
});

test('returns soonest when CalendarEvent is sooner than recurring', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(7, 0, 0));

    // Recurring departure in 2 hours (9:00 AM)
    DepartureTime::factory()->create([
        'name' => 'School bus',
        'departure_time' => '09:00:00',
        'applicable_days' => ['monday'],
        'is_active' => true,
    ]);

    // Event departure in 30 minutes
    CalendarEvent::factory()->create([
        'name' => 'Urgent Appointment',
        'starts_at' => now()->addHour(),
        'departure_time' => now()->addMinutes(30),
    ]);

    $service = new DepartureService;
    $result = $service->getNextDeparture();

    expect($result['name'])->toBe('Urgent Appointment');
    expect($result['source'])->toBe('calendar_event');

    Carbon::setTestNow();
});

test('ignores past CalendarEvent departure times', function () {
    // Event with departure time in the past
    CalendarEvent::factory()->create([
        'name' => 'Past Appointment',
        'starts_at' => now()->addHour(),
        'departure_time' => now()->subHour(),
    ]);

    $service = new DepartureService;
    $result = $service->getNextDeparture();

    expect($result)->toBeNull();
});

test('ignores events without departure time', function () {
    // Event without departure time
    CalendarEvent::factory()->create([
        'name' => 'Regular Event',
        'starts_at' => now()->addHour(),
        'departure_time' => null,
    ]);

    $service = new DepartureService;
    $result = $service->getNextDeparture();

    expect($result)->toBeNull();
});
