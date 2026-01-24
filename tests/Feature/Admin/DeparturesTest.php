<?php

declare(strict_types=1);

use App\Models\DepartureTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('departures admin page loads successfully', function () {
    $this->get(route('admin.departures.index'))
        ->assertSuccessful();
});

test('departure times are listed', function () {
    DepartureTime::factory()->create(['name' => 'School bus']);

    $this->get(route('admin.departures.index'))
        ->assertSuccessful()
        ->assertSee('School bus');
});

test('can create a new departure time', function () {
    Volt::test('admin.departures')
        ->call('create')
        ->set('name', 'School bus')
        ->set('departureTime', '07:45')
        ->set('applicableDays', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
        ->set('isActive', true)
        ->call('save');

    expect(DepartureTime::where('name', 'School bus')->exists())->toBeTrue();
});

test('can edit a departure time', function () {
    $departure = DepartureTime::factory()->create(['name' => 'School bus']);

    Volt::test('admin.departures')
        ->call('edit', $departure->id)
        ->assertSet('name', 'School bus')
        ->set('name', 'Morning bus')
        ->call('save');

    expect($departure->fresh()->name)->toBe('Morning bus');
});

test('can toggle departure active status', function () {
    $departure = DepartureTime::factory()->create(['is_active' => true]);

    Volt::test('admin.departures')
        ->call('toggleActive', $departure->id);

    expect($departure->fresh()->is_active)->toBeFalse();
});

test('can delete a departure time', function () {
    $departure = DepartureTime::factory()->create();

    Volt::test('admin.departures')
        ->call('confirmDelete', $departure->id)
        ->call('delete');

    expect(DepartureTime::find($departure->id))->toBeNull();
});

test('validation requires at least one day', function () {
    Volt::test('admin.departures')
        ->call('create')
        ->set('name', 'Test')
        ->set('departureTime', '07:45')
        ->set('applicableDays', [])
        ->call('save')
        ->assertHasErrors(['applicableDays']);
});

