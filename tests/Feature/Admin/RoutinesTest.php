<?php

declare(strict_types=1);

use App\Models\Child;
use App\Models\RoutineItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('routines admin page loads successfully', function () {
    $child = Child::factory()->create();

    $this->get(route('admin.children.routines', $child))
        ->assertSuccessful();
});

test('routine items are listed', function () {
    $child = Child::factory()->create();
    RoutineItem::factory()->create([
        'child_id' => $child->id,
        'name' => 'Brush teeth',
    ]);

    $this->get(route('admin.children.routines', $child))
        ->assertSuccessful()
        ->assertSee('Brush teeth');
});

test('can create a new routine item', function () {
    $child = Child::factory()->create();

    Livewire::test('pages::admin.routines', ['child' => $child])
        ->call('create')
        ->set('name', 'Brush teeth')
        ->set('displayOrder', 1)
        ->call('save');

    expect(RoutineItem::where('name', 'Brush teeth')->exists())->toBeTrue();
});

test('can edit a routine item', function () {
    $child = Child::factory()->create();
    $item = RoutineItem::factory()->create([
        'child_id' => $child->id,
        'name' => 'Brush teeth',
    ]);

    Livewire::test('pages::admin.routines', ['child' => $child])
        ->call('edit', $item->id)
        ->assertSet('name', 'Brush teeth')
        ->set('name', 'Brush teeth twice')
        ->call('save');

    expect($item->fresh()->name)->toBe('Brush teeth twice');
});

test('can delete a routine item', function () {
    $child = Child::factory()->create();
    $item = RoutineItem::factory()->create(['child_id' => $child->id]);

    Livewire::test('pages::admin.routines', ['child' => $child])
        ->call('confirmDelete', $item->id)
        ->call('delete');

    expect(RoutineItem::find($item->id))->toBeNull();
});

test('can move items up and down', function () {
    $child = Child::factory()->create();
    $item1 = RoutineItem::factory()->create([
        'child_id' => $child->id,
        'display_order' => 0,
    ]);
    $item2 = RoutineItem::factory()->create([
        'child_id' => $child->id,
        'display_order' => 1,
    ]);

    Livewire::test('pages::admin.routines', ['child' => $child])
        ->call('moveDown', $item1->id);

    expect($item1->fresh()->display_order)->toBe(1);
});
