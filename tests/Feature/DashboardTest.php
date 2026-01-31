<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Child;
use App\Models\DepartureTime;
use App\Models\RoutineItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('dashboard page loads successfully', function () {
    $this->get(route('dashboard'))
        ->assertSuccessful();
});

test('dashboard displays children with routine items', function () {
    $child = Child::factory()->create(['name' => 'Emma']);
    RoutineItem::factory()->create([
        'child_id' => $child->id,
        'name' => 'Brush teeth',
    ]);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Emma')
        ->assertSee('Brush teeth');
});

test('dashboard displays departure timer when configured', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(6, 0, 0));

    DepartureTime::factory()->create([
        'name' => 'School bus',
        'departure_time' => '08:00:00',
        'applicable_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'is_active' => true,
    ]);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('School bus');

    Carbon::setTestNow();
});

test('dashboard displays upcoming events', function () {
    CalendarEvent::factory()->create([
        'name' => "Emma's Birthday Party",
        'starts_at' => now()->addDays(3),
    ]);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee("Emma's Birthday Party");
});

test('child checklist can toggle routine items', function () {
    $child = Child::factory()->create();
    $item = RoutineItem::factory()->create([
        'child_id' => $child->id,
        'name' => 'Brush teeth',
    ]);

    Livewire::test('dashboard.child-checklist', ['child' => $child])
        ->assertSee('Brush teeth')
        ->call('toggleItem', $item->id)
        ->assertSet('child.routineItems.0.id', $item->id);

    expect($item->fresh()->isCompletedFor())->toBeTrue();
});

test('child checklist shows progress percentage', function () {
    $child = Child::factory()->create();
    $item1 = RoutineItem::factory()->create(['child_id' => $child->id]);
    $item2 = RoutineItem::factory()->create(['child_id' => $child->id]);

    $item1->markComplete();

    Livewire::test('dashboard.child-checklist', ['child' => $child->fresh()])
        ->assertSee('1/2');
});

test('child checklist shows celebration when all complete', function () {
    $child = Child::factory()->create(['name' => 'Emma']);
    $item = RoutineItem::factory()->create(['child_id' => $child->id]);

    $item->markComplete();

    Livewire::test('dashboard.child-checklist', ['child' => $child->fresh()])
        ->assertSee('All done!')
        ->assertSee('Emma');
});

test('dashboard displays event departure when soonest', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(7, 0, 0));

    // Recurring departure at 9 AM (2 hours away)
    DepartureTime::factory()->create([
        'name' => 'School bus',
        'departure_time' => '09:00:00',
        'applicable_days' => ['monday'],
        'is_active' => true,
    ]);

    // Event departure at 7:30 AM (30 minutes away) - sooner!
    CalendarEvent::factory()->create([
        'name' => 'Dentist',
        'starts_at' => now()->addHour(),
        'departure_time' => now()->addMinutes(30),
    ]);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Dentist');

    Carbon::setTestNow();
});

test('dashboard shows recurring departure when event departure is later', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(7, 0, 0));

    // Recurring departure at 7:30 AM (30 minutes away)
    DepartureTime::factory()->create([
        'name' => 'School bus',
        'departure_time' => '07:30:00',
        'applicable_days' => ['monday'],
        'is_active' => true,
    ]);

    // Event departure at 10 AM (3 hours away) - later
    CalendarEvent::factory()->create([
        'name' => 'Dentist',
        'starts_at' => now()->addHours(4),
        'departure_time' => now()->addHours(3),
    ]);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('School bus');

    Carbon::setTestNow();
});
