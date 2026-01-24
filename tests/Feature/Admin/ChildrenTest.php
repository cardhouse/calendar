<?php

declare(strict_types=1);

use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('children admin page loads successfully', function () {
    $this->get(route('admin.children.index'))
        ->assertSuccessful();
});

test('children are listed on admin page', function () {
    Child::factory()->create(['name' => 'Emma']);
    Child::factory()->create(['name' => 'Jack']);

    $this->get(route('admin.children.index'))
        ->assertSuccessful()
        ->assertSee('Emma')
        ->assertSee('Jack');
});

test('can create a new child', function () {
    Volt::test('admin.children')
        ->call('create')
        ->set('name', 'Emma')
        ->set('avatarColor', '#3B82F6')
        ->set('displayOrder', 1)
        ->call('save');

    expect(Child::where('name', 'Emma')->exists())->toBeTrue();
});

test('can edit an existing child', function () {
    $child = Child::factory()->create(['name' => 'Emma']);

    Volt::test('admin.children')
        ->call('edit', $child->id)
        ->assertSet('name', 'Emma')
        ->set('name', 'Emma Rose')
        ->call('save');

    expect($child->fresh()->name)->toBe('Emma Rose');
});

test('can delete a child', function () {
    $child = Child::factory()->create();

    Volt::test('admin.children')
        ->call('confirmDelete', $child->id)
        ->assertSet('showDeleteConfirm', true)
        ->call('delete');

    expect(Child::find($child->id))->toBeNull();
});

test('validation prevents empty name', function () {
    Volt::test('admin.children')
        ->call('create')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

