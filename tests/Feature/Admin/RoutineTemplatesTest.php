<?php

declare(strict_types=1);

use App\Models\Child;
use App\Models\RoutineItem;
use App\Models\RoutineItemTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('routine templates admin page loads successfully', function () {
    $this->get(route('admin.routine-templates.index'))
        ->assertSuccessful();
});

test('templates are listed on admin page', function () {
    RoutineItemTemplate::factory()->create(['name' => 'Brush teeth']);
    RoutineItemTemplate::factory()->create(['name' => 'Make bed']);

    $this->get(route('admin.routine-templates.index'))
        ->assertSuccessful()
        ->assertSee('Brush teeth')
        ->assertSee('Make bed');
});

test('can create a new template', function () {
    Livewire::test('pages::admin.routine-templates')
        ->call('showAddTemplateForm')
        ->set('newTemplateName', 'Brush teeth')
        ->call('addTemplate');

    expect(RoutineItemTemplate::where('name', 'Brush teeth')->exists())->toBeTrue();
});

test('validation prevents duplicate template names', function () {
    RoutineItemTemplate::factory()->create(['name' => 'Brush teeth']);

    Livewire::test('pages::admin.routine-templates')
        ->call('showAddTemplateForm')
        ->set('newTemplateName', 'Brush teeth')
        ->call('addTemplate')
        ->assertHasErrors(['newTemplateName']);
});

test('validation prevents empty template name', function () {
    Livewire::test('pages::admin.routine-templates')
        ->call('showAddTemplateForm')
        ->set('newTemplateName', '')
        ->call('addTemplate')
        ->assertHasErrors(['newTemplateName']);
});

test('can edit an existing template', function () {
    $template = RoutineItemTemplate::factory()->create(['name' => 'Brush teeth']);

    Livewire::test('pages::admin.routine-templates')
        ->call('startEditTemplate', $template->id)
        ->assertSet('editingTemplateName', 'Brush teeth')
        ->set('editingTemplateName', 'Brush teeth thoroughly')
        ->call('saveTemplateEdit');

    expect($template->fresh()->name)->toBe('Brush teeth thoroughly');
});

test('can delete a template', function () {
    $template = RoutineItemTemplate::factory()->create();

    Livewire::test('pages::admin.routine-templates')
        ->call('confirmDeleteTemplate', $template->id)
        ->assertSet('showDeleteConfirm', true)
        ->call('deleteTemplate');

    expect(RoutineItemTemplate::find($template->id))->toBeNull();
});

test('can add template to child routine', function () {
    $template = RoutineItemTemplate::factory()->create(['name' => 'Brush teeth']);
    $child = Child::factory()->create(['name' => 'Emma']);

    Livewire::test('pages::admin.routine-templates')
        ->call('addTemplateToChild', $template->id, $child->id);

    expect(RoutineItem::where('child_id', $child->id)->where('name', 'Brush teeth')->exists())->toBeTrue();
});

test('prevents adding duplicate item to child routine', function () {
    $template = RoutineItemTemplate::factory()->create(['name' => 'Brush teeth']);
    $child = Child::factory()->create(['name' => 'Emma']);

    // Add the item first time
    RoutineItem::factory()->create([
        'child_id' => $child->id,
        'name' => 'Brush teeth',
    ]);

    // Try to add again
    Livewire::test('pages::admin.routine-templates')
        ->call('addTemplateToChild', $template->id, $child->id)
        ->assertDispatched('notify');

    // Should still only have one item
    expect(RoutineItem::where('child_id', $child->id)->where('name', 'Brush teeth')->count())->toBe(1);
});

test('can remove routine item from child', function () {
    $child = Child::factory()->create();
    $item = RoutineItem::factory()->create(['child_id' => $child->id]);

    Livewire::test('pages::admin.routine-templates')
        ->call('removeRoutineItem', $item->id);

    expect(RoutineItem::find($item->id))->toBeNull();
});

test('children with routines are displayed', function () {
    $child = Child::factory()->create(['name' => 'Emma']);
    RoutineItem::factory()->create(['child_id' => $child->id, 'name' => 'Wake up']);

    $this->get(route('admin.routine-templates.index'))
        ->assertSuccessful()
        ->assertSee('Emma')
        ->assertSee('Wake up');
});

test('can reorder templates', function () {
    $template1 = RoutineItemTemplate::factory()->create(['name' => 'First', 'display_order' => 0]);
    $template2 = RoutineItemTemplate::factory()->create(['name' => 'Second', 'display_order' => 1]);

    Livewire::test('pages::admin.routine-templates')
        ->call('reorderTemplates', [$template2->id, $template1->id]);

    expect($template1->fresh()->display_order)->toBe(1);
    expect($template2->fresh()->display_order)->toBe(0);
});
