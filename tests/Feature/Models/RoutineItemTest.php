<?php

declare(strict_types=1);

use App\Models\Child;
use App\Models\RoutineCompletion;
use App\Models\RoutineItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('routine item belongs to child', function () {
    $child = Child::factory()->create();
    $item = RoutineItem::factory()->create(['child_id' => $child->id]);

    expect($item->child->id)->toBe($child->id);
});

test('routine item has completions relationship', function () {
    $item = RoutineItem::factory()->create();
    $item->markComplete();

    expect($item->completions)->toHaveCount(1);
});

test('isCompletedFor returns false when not completed', function () {
    $item = RoutineItem::factory()->create();

    expect($item->isCompletedFor())->toBeFalse();
});

test('isCompletedFor returns true when completed today', function () {
    $item = RoutineItem::factory()->create();
    $item->markComplete();

    expect($item->isCompletedFor())->toBeTrue();
});

test('isCompletedFor returns false for different date', function () {
    $item = RoutineItem::factory()->create();
    $item->markComplete();

    expect($item->isCompletedFor(today()->subDay()))->toBeFalse();
});

test('markComplete creates completion record', function () {
    $item = RoutineItem::factory()->create();
    $item->markComplete();

    expect(RoutineCompletion::count())->toBe(1);
    expect($item->isCompletedFor())->toBeTrue();
});

test('markComplete does not duplicate completion', function () {
    $item = RoutineItem::factory()->create();
    $item->markComplete();
    $item->markComplete();

    expect(RoutineCompletion::count())->toBe(1);
});

test('markIncomplete removes completion record', function () {
    $item = RoutineItem::factory()->create();
    $item->markComplete();
    $item->markIncomplete();

    expect(RoutineCompletion::count())->toBe(0);
    expect($item->isCompletedFor())->toBeFalse();
});

test('toggleCompletion marks complete when incomplete', function () {
    $item = RoutineItem::factory()->create();
    $result = $item->toggleCompletion();

    expect($result)->toBeTrue();
    expect($item->isCompletedFor())->toBeTrue();
});

test('toggleCompletion marks incomplete when complete', function () {
    $item = RoutineItem::factory()->create();
    $item->markComplete();
    $result = $item->toggleCompletion();

    expect($result)->toBeFalse();
    expect($item->isCompletedFor())->toBeFalse();
});
