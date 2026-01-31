<?php

declare(strict_types=1);

use App\Models\Child;
use App\Models\RoutineItem;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin')] #[Title('Manage Routines')] class extends Component {
    public Child $child;

    /** @var Collection<int, RoutineItem> */
    public Collection $items;

    public ?int $editingId = null;
    public string $name = '';
    public int $displayOrder = 0;

    public bool $showForm = false;
    public bool $showDeleteConfirm = false;
    public ?int $deleteId = null;

    public function mount(Child $child): void
    {
        $this->child = $child;
        $this->loadItems();
    }

    public function loadItems(): void
    {
        $this->items = $this->child->routineItems()->orderBy('display_order')->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'displayOrder']);
        $this->displayOrder = ($this->items->max('display_order') ?? 0) + 1;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $item = RoutineItem::findOrFail($id);
        $this->editingId = $item->id;
        $this->name = $item->name;
        $this->displayOrder = $item->display_order;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'displayOrder' => 'required|integer|min:0',
        ]);

        if ($this->editingId) {
            $item = RoutineItem::findOrFail($this->editingId);
            $item->update([
                'name' => $validated['name'],
                'display_order' => $validated['displayOrder'],
            ]);
        } else {
            RoutineItem::create([
                'child_id' => $this->child->id,
                'name' => $validated['name'],
                'display_order' => $validated['displayOrder'],
            ]);
        }

        $this->reset(['editingId', 'name', 'displayOrder']);
        $this->showForm = false;
        $this->loadItems();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        if ($this->deleteId) {
            RoutineItem::destroy($this->deleteId);
            $this->loadItems();
        }
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'name', 'displayOrder']);
        $this->showForm = false;
    }

    public function moveUp(int $id): void
    {
        $item = RoutineItem::find($id);
        if ($item && $item->display_order > 0) {
            $item->decrement('display_order');
            $this->loadItems();
        }
    }

    public function moveDown(int $id): void
    {
        $item = RoutineItem::find($id);
        if ($item) {
            $item->increment('display_order');
            $this->loadItems();
        }
    }
}; ?>

<div>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.children.index') }}"
                   class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    ← Back to Children
                </a>
                <span class="w-4 h-4 rounded-full" style="background: {{ $child->avatar_color }}"></span>
                <h2 class="text-2xl font-bold">{{ $child->name }}'s Routines</h2>
            </div>
            <button wire:click="create"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Add Item
            </button>
        </div>

        <!-- Items list -->
        @if($items->isNotEmpty())
            <ul class="space-y-3 mb-6">
                @foreach($items as $item)
                    <li wire:key="item-{{ $item->id }}"
                        class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-700 rounded-lg">
                        <div class="flex flex-col gap-1">
                            <button wire:click="moveUp({{ $item->id }})"
                                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                </svg>
                            </button>
                            <button wire:click="moveDown({{ $item->id }})"
                                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>
                        <span class="text-sm text-slate-400 dark:text-slate-500 w-8">
                            #{{ $item->display_order }}
                        </span>
                        <span class="flex-1 font-medium">{{ $item->name }}</span>
                        <div class="flex items-center gap-2">
                            <button wire:click="edit({{ $item->id }})"
                                    class="px-3 py-1 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                Edit
                            </button>
                            <button wire:click="confirmDelete({{ $item->id }})"
                                    class="px-3 py-1 text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                Delete
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-center text-slate-500 dark:text-slate-400 py-8">
                No routine items yet. Click "Add Item" to create the first one.
            </p>
        @endif

        <!-- Edit/Create Form -->
        @if($showForm)
            <div class="border-t dark:border-slate-600 pt-6">
                <h3 class="text-lg font-semibold mb-4">
                    {{ $editingId ? 'Edit Item' : 'Add New Item' }}
                </h3>
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Task Name
                        </label>
                        <input type="text"
                               id="name"
                               wire:model="name"
                               placeholder="e.g., Brush teeth"
                               class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="displayOrder" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Display Order
                        </label>
                        <input type="number"
                               id="displayOrder"
                               wire:model="displayOrder"
                               min="0"
                               class="w-24 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('displayOrder')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3">
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            {{ $editingId ? 'Update' : 'Create' }}
                        </button>
                        <button type="button"
                                wire:click="cancel"
                                class="px-4 py-2 bg-slate-200 dark:bg-slate-600 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-500 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    @if($showDeleteConfirm)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl p-6 max-w-md mx-4">
                <h3 class="text-lg font-semibold mb-4">Confirm Delete</h3>
                <p class="text-slate-600 dark:text-slate-300 mb-6">
                    Are you sure you want to delete this routine item?
                </p>
                <div class="flex gap-3 justify-end">
                    <button wire:click="cancelDelete"
                            class="px-4 py-2 bg-slate-200 dark:bg-slate-600 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-500 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="delete"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

