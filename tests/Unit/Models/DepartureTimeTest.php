<?php

declare(strict_types=1);

use App\Models\DepartureTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('appliesToDate returns true for applicable day', function () {
    $departure = DepartureTime::factory()->create([
        'applicable_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    ]);

    // Test with a Monday
    $monday = Carbon::parse('next monday');

    expect($departure->appliesToDate($monday))->toBeTrue();
});

test('appliesToDate returns false for non-applicable day', function () {
    $departure = DepartureTime::factory()->create([
        'applicable_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    ]);

    // Test with a Saturday
    $saturday = Carbon::parse('next saturday');

    expect($departure->appliesToDate($saturday))->toBeFalse();
});

test('getNextOccurrence returns null when inactive', function () {
    $departure = DepartureTime::factory()->inactive()->create();

    expect($departure->getNextOccurrence())->toBeNull();
});

test('getNextOccurrence returns today if time has not passed', function () {
    // Freeze time to a Monday at 6am
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(6, 0, 0));

    $departure = DepartureTime::factory()->create([
        'departure_time' => '08:00:00',
        'applicable_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'is_active' => true,
    ]);

    $nextOccurrence = $departure->getNextOccurrence();

    expect($nextOccurrence)->not->toBeNull();
    expect($nextOccurrence->isToday())->toBeTrue();
    expect($nextOccurrence->format('H:i:s'))->toBe('08:00:00');

    Carbon::setTestNow();
});

test('getNextOccurrence returns next applicable day if time passed', function () {
    // Freeze time to a Monday at 10am (after 8am departure)
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(10, 0, 0));

    $departure = DepartureTime::factory()->create([
        'departure_time' => '08:00:00',
        'applicable_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'is_active' => true,
    ]);

    $nextOccurrence = $departure->getNextOccurrence();

    expect($nextOccurrence)->not->toBeNull();
    expect($nextOccurrence->isTuesday())->toBeTrue();

    Carbon::setTestNow();
});

test('getSecondsRemaining returns positive value for future departure', function () {
    // Freeze time to a Monday at 7am
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(7, 0, 0));

    $departure = DepartureTime::factory()->create([
        'departure_time' => '08:00:00',
        'applicable_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'is_active' => true,
    ]);

    $seconds = $departure->getSecondsRemaining();

    expect($seconds)->toBe(3600); // 1 hour = 3600 seconds

    Carbon::setTestNow();
});

test('getSecondsRemaining returns null when inactive', function () {
    $departure = DepartureTime::factory()->inactive()->create();

    expect($departure->getSecondsRemaining())->toBeNull();
});

test('scopeActive returns only active departures', function () {
    DepartureTime::factory()->count(2)->create(['is_active' => true]);
    DepartureTime::factory()->count(1)->inactive()->create();

    expect(DepartureTime::active()->count())->toBe(2);
});

test('getNextDeparture returns soonest departure', function () {
    // Freeze time to a Monday at 6am
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(6, 0, 0));

    $later = DepartureTime::factory()->create([
        'name' => 'Later',
        'departure_time' => '09:00:00',
        'applicable_days' => ['monday'],
        'is_active' => true,
    ]);

    $sooner = DepartureTime::factory()->create([
        'name' => 'Sooner',
        'departure_time' => '07:00:00',
        'applicable_days' => ['monday'],
        'is_active' => true,
    ]);

    $next = DepartureTime::getNextDeparture();

    expect($next)->not->toBeNull();
    expect($next->name)->toBe('Sooner');

    Carbon::setTestNow();
});

