<?php

declare(strict_types=1);

use App\Models\Child;
use App\Models\RoutineItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('child has routine items relationship', function () {
    $child = Child::factory()->create();
    $item = RoutineItem::factory()->create(['child_id' => $child->id]);

    expect($child->routineItems)->toHaveCount(1);
    expect($child->routineItems->first()->id)->toBe($item->id);
});

test('routine items are ordered by display order', function () {
    $child = Child::factory()->create();
    RoutineItem::factory()->create(['child_id' => $child->id, 'display_order' => 3]);
    RoutineItem::factory()->create(['child_id' => $child->id, 'display_order' => 1]);
    RoutineItem::factory()->create(['child_id' => $child->id, 'display_order' => 2]);

    $child->refresh();
    $orders = $child->routineItems->pluck('display_order')->toArray();

    expect($orders)->toBe([1, 2, 3]);
});

test('today progress returns 100 when no routine items', function () {
    $child = Child::factory()->create();

    expect($child->today_progress)->toBe(100);
});

test('today progress calculates correctly', function () {
    $child = Child::factory()->create();
    $item1 = RoutineItem::factory()->create(['child_id' => $child->id]);
    $item2 = RoutineItem::factory()->create(['child_id' => $child->id]);

    // Mark one item complete
    $item1->markComplete();
    $child->refresh();

    expect($child->today_progress)->toBe(50);
});

test('today progress returns 100 when all items complete', function () {
    $child = Child::factory()->create();
    $item1 = RoutineItem::factory()->create(['child_id' => $child->id]);
    $item2 = RoutineItem::factory()->create(['child_id' => $child->id]);

    $item1->markComplete();
    $item2->markComplete();
    $child->refresh();

    expect($child->today_progress)->toBe(100);
});

