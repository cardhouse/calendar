<?php

declare(strict_types=1);

use App\Models\Child;
use App\Models\RoutineItem;
use App\Models\RoutineItemTemplate;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('components.layouts.admin')] #[Title('Routine Templates')] class extends Component {
    /** @var Collection<int, RoutineItemTemplate> */
    public Collection $templates;

    /** @var Collection<int, Child> */
    public Collection $children;

    #[Validate('required|string|max:255|unique:routine_item_templates,name')]
    public string $newTemplateName = '';

    public bool $showAddForm = false;

    public ?int $editingTemplateId = null;
    public string $editingTemplateName = '';

    public bool $showDeleteConfirm = false;
    public ?int $deleteTemplateId = null;

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->templates = RoutineItemTemplate::orderBy('display_order')->orderBy('name')->get();
        $this->children = Child::with(['routineItems' => fn ($q) => $q->orderBy('display_order')])
            ->orderBy('display_order')
            ->get();
    }

    public function showAddTemplateForm(): void
    {
        $this->newTemplateName = '';
        $this->showAddForm = true;
        $this->editingTemplateId = null;
    }

    public function addTemplate(): void
    {
        $this->validate([
            'newTemplateName' => 'required|string|max:255|unique:routine_item_templates,name',
        ]);

        $maxOrder = $this->templates->max('display_order') ?? 0;

        RoutineItemTemplate::create([
            'name' => $this->newTemplateName,
            'display_order' => $maxOrder + 1,
        ]);

        $this->newTemplateName = '';
        $this->showAddForm = false;
        $this->loadData();
    }

    public function startEditTemplate(int $id): void
    {
        $template = RoutineItemTemplate::findOrFail($id);
        $this->editingTemplateId = $id;
        $this->editingTemplateName = $template->name;
        $this->showAddForm = false;
    }

    public function saveTemplateEdit(): void
    {
        $this->validate([
            'editingTemplateName' => 'required|string|max:255|unique:routine_item_templates,name,' . $this->editingTemplateId,
        ]);

        $template = RoutineItemTemplate::findOrFail($this->editingTemplateId);
        $template->update(['name' => $this->editingTemplateName]);

        $this->editingTemplateId = null;
        $this->editingTemplateName = '';
        $this->loadData();
    }

    public function cancelEdit(): void
    {
        $this->editingTemplateId = null;
        $this->editingTemplateName = '';
        $this->showAddForm = false;
        $this->newTemplateName = '';
    }

    public function confirmDeleteTemplate(int $id): void
    {
        $this->deleteTemplateId = $id;
        $this->showDeleteConfirm = true;
    }

    public function deleteTemplate(): void
    {
        if ($this->deleteTemplateId) {
            RoutineItemTemplate::destroy($this->deleteTemplateId);
            $this->loadData();
        }
        $this->showDeleteConfirm = false;
        $this->deleteTemplateId = null;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTemplateId = null;
    }

    public function reorderTemplates(array $templateIds): void
    {
        foreach ($templateIds as $index => $id) {
            RoutineItemTemplate::where('id', $id)->update(['display_order' => $index]);
        }
        $this->loadData();
    }

    public function addTemplateToChild(int $templateId, int $childId): void
    {
        $template = RoutineItemTemplate::find($templateId);
        $child = Child::find($childId);

        if (!$template || !$child) {
            return;
        }

        // Check if this item already exists for the child
        $exists = RoutineItem::where('child_id', $childId)
            ->where('name', $template->name)
            ->exists();

        if ($exists) {
            $this->dispatch('notify', message: "{$child->name} already has '{$template->name}' in their routine.");
            return;
        }

        $maxOrder = RoutineItem::where('child_id', $childId)->max('display_order') ?? 0;

        RoutineItem::create([
            'child_id' => $childId,
            'name' => $template->name,
            'display_order' => $maxOrder + 1,
        ]);

        $this->loadData();
    }

    public function reorderChildRoutines(int $childId, array $itemIds): void
    {
        foreach ($itemIds as $index => $id) {
            RoutineItem::where('id', $id)
                ->where('child_id', $childId)
                ->update(['display_order' => $index]);
        }
        $this->loadData();
    }

    public function removeRoutineItem(int $itemId): void
    {
        RoutineItem::destroy($itemId);
        $this->loadData();
    }
}; ?>

<div x-data="{
    draggedTemplateId: null,
    dragOverChildId: null,

    startDrag(event, templateId) {
        this.draggedTemplateId = templateId;
        event.dataTransfer.effectAllowed = 'copy';
        event.dataTransfer.setData('text/plain', templateId);
    },

    endDrag() {
        this.draggedTemplateId = null;
        this.dragOverChildId = null;
    },

    dragOver(event, childId) {
        event.preventDefault();
        this.dragOverChildId = childId;
    },

    dragLeave() {
        this.dragOverChildId = null;
    },

    drop(event, childId) {
        event.preventDefault();
        const templateId = parseInt(event.dataTransfer.getData('text/plain'));
        if (templateId) {
            $wire.addTemplateToChild(templateId, childId);
        }
        this.draggedTemplateId = null;
        this.dragOverChildId = null;
    }
}"
@notify.window="
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 bg-amber-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
    toast.textContent = $event.detail.message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">Routine Templates</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Drag templates to add them to children's routines
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Template List -->
            <div class="lg:col-span-1">
                <div class="bg-slate-50 dark:bg-slate-700 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-lg">Available Items</h3>
                        @if(!$showAddForm && !$editingTemplateId)
                            <button wire:click="showAddTemplateForm"
                                    class="px-3 py-1 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                + Add
                            </button>
                        @endif
                    </div>

                    <!-- Add Template Form -->
                    @if($showAddForm)
                        <form wire:submit="addTemplate" class="mb-4 p-3 bg-white dark:bg-slate-600 rounded-lg">
                            <label class="block text-sm font-medium mb-1">New Template Name</label>
                            <input type="text"
                                   wire:model="newTemplateName"
                                   placeholder="e.g., Brush teeth"
                                   class="w-full rounded-lg border-slate-300 dark:border-slate-500 dark:bg-slate-700 text-sm mb-2"
                                   autofocus>
                            @error('newTemplateName')
                                <p class="text-red-500 text-xs mb-2">{{ $message }}</p>
                            @enderror
                            <div class="flex gap-2">
                                <button type="submit"
                                        class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                                    Save
                                </button>
                                <button type="button"
                                        wire:click="cancelEdit"
                                        class="px-3 py-1 text-sm bg-slate-300 dark:bg-slate-500 rounded hover:bg-slate-400">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    @endif

                    <!-- Templates List -->
                    @if($templates->isNotEmpty())
                        <ul wire:sort="reorderTemplates" class="space-y-2">
                            @foreach($templates as $template)
                                <li wire:sort:item="{{ $template->id }}"
                                    wire:key="template-{{ $template->id }}"
                                    class="group flex items-center gap-2 p-3 bg-white dark:bg-slate-600 rounded-lg shadow-sm
                                           transition-all duration-200"
                                    :class="draggedTemplateId === {{ $template->id }} ? 'opacity-50 scale-95' : 'hover:shadow-md'"
                                    draggable="true"
                                    @dragstart="startDrag($event, {{ $template->id }})"
                                    @dragend="endDrag()">

                                    @if($editingTemplateId === $template->id)
                                        <!-- Edit Mode -->
                                        <form wire:submit="saveTemplateEdit" class="flex-1 flex items-center gap-2">
                                            <input type="text"
                                                   wire:model="editingTemplateName"
                                                   class="flex-1 rounded border-slate-300 dark:border-slate-500 dark:bg-slate-700 text-sm py-1"
                                                   autofocus>
                                            <button type="submit" class="text-green-600 hover:text-green-700">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                            <button type="button" wire:click="cancelEdit" class="text-slate-400 hover:text-slate-600">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                        @error('editingTemplateName')
                                            <p class="text-red-500 text-xs">{{ $message }}</p>
                                        @enderror
                                    @else
                                        <!-- Display Mode -->
                                        <svg wire:sort:handle class="w-4 h-4 text-slate-400 cursor-grab active:cursor-grabbing shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                        </svg>
                                        <span class="flex-1 font-medium text-sm cursor-grab">{{ $template->name }}</span>
                                        <div class="opacity-0 group-hover:opacity-100 flex items-center gap-1 transition-opacity">
                                            <button wire:click="startEditTemplate({{ $template->id }})"
                                                    class="p-1 text-slate-400 hover:text-blue-600">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <button wire:click="confirmDeleteTemplate({{ $template->id }})"
                                                    class="p-1 text-slate-400 hover:text-red-600">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-center text-slate-500 dark:text-slate-400 py-4 text-sm">
                            No templates yet. Add your first one above.
                        </p>
                    @endif
                </div>
            </div>

            <!-- Right Column: Children's Routines -->
            <div class="lg:col-span-2">
                <div class="space-y-4">
                    @forelse($children as $child)
                        <div class="bg-slate-50 dark:bg-slate-700 rounded-lg p-4 border-l-4 transition-all duration-200"
                             style="border-color: {{ $child->avatar_color }}"
                             :class="dragOverChildId === {{ $child->id }} ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''"
                             @dragover="dragOver($event, {{ $child->id }})"
                             @dragleave="dragLeave()"
                             @drop="drop($event, {{ $child->id }})">

                            <div class="flex items-center gap-3 mb-3">
                                <span class="w-4 h-4 rounded-full shrink-0" style="background: {{ $child->avatar_color }}"></span>
                                <h3 class="font-semibold">{{ $child->name }}'s Routine</h3>
                                <span class="text-sm text-slate-500 dark:text-slate-400">
                                    ({{ $child->routineItems->count() }} items)
                                </span>
                            </div>

                            @if($child->routineItems->isNotEmpty())
                                <ul wire:sort="reorderChildRoutines({{ $child->id }}, $event.detail)"
                                    class="space-y-2 min-h-[60px]">
                                    @foreach($child->routineItems as $item)
                                        <li wire:sort:item="{{ $item->id }}"
                                            wire:key="child-{{ $child->id }}-item-{{ $item->id }}"
                                            class="group flex items-center gap-2 p-2 bg-white dark:bg-slate-600 rounded shadow-sm
                                                   cursor-grab active:cursor-grabbing hover:shadow-md transition-all">
                                            <svg wire:sort:handle class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                            </svg>
                                            <span class="flex-1 text-sm">{{ $item->name }}</span>
                                            <button wire:click="removeRoutineItem({{ $item->id }})"
                                                    class="opacity-0 group-hover:opacity-100 p-1 text-slate-400 hover:text-red-600 transition-opacity">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="min-h-[60px] flex items-center justify-center border-2 border-dashed border-slate-300 dark:border-slate-500 rounded-lg"
                                     :class="dragOverChildId === {{ $child->id }} ? 'border-blue-500 bg-blue-100/50 dark:bg-blue-900/30' : ''">
                                    <p class="text-sm text-slate-400 dark:text-slate-500">
                                        Drop items here
                                    </p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8 text-slate-500 dark:text-slate-400">
                            <p>No children added yet.</p>
                            <a href="{{ route('admin.children.index') }}" class="text-blue-600 hover:underline">
                                Add children first
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if($showDeleteConfirm)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl p-6 max-w-md mx-4">
                <h3 class="text-lg font-semibold mb-4">Delete Template</h3>
                <p class="text-slate-600 dark:text-slate-300 mb-6">
                    Are you sure you want to delete this template? This will not remove it from children's existing routines.
                </p>
                <div class="flex gap-3 justify-end">
                    <button wire:click="cancelDelete"
                            class="px-4 py-2 bg-slate-200 dark:bg-slate-600 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-500 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="deleteTemplate"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
