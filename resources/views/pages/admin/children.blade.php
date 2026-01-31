<?php

declare(strict_types=1);

use App\Models\Child;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin')] #[Title('Manage Children')] class extends Component {
    /** @var Collection<int, Child> */
    public Collection $children;

    public ?int $editingId = null;
    public string $name = '';
    public string $avatarColor = '#3B82F6';
    public int $displayOrder = 0;

    public bool $showForm = false;
    public bool $showDeleteConfirm = false;
    public ?int $deleteId = null;

    public function mount(): void
    {
        $this->loadChildren();
    }

    public function loadChildren(): void
    {
        $this->children = Child::orderBy('display_order')->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'avatarColor', 'displayOrder']);
        $this->displayOrder = ($this->children->max('display_order') ?? 0) + 1;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $child = Child::findOrFail($id);
        $this->editingId = $child->id;
        $this->name = $child->name;
        $this->avatarColor = $child->avatar_color;
        $this->displayOrder = $child->display_order;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'avatarColor' => 'required|string|max:7',
            'displayOrder' => 'required|integer|min:0',
        ]);

        if ($this->editingId) {
            $child = Child::findOrFail($this->editingId);
            $child->update([
                'name' => $validated['name'],
                'avatar_color' => $validated['avatarColor'],
                'display_order' => $validated['displayOrder'],
            ]);
        } else {
            Child::create([
                'name' => $validated['name'],
                'avatar_color' => $validated['avatarColor'],
                'display_order' => $validated['displayOrder'],
            ]);
        }

        $this->reset(['editingId', 'name', 'avatarColor', 'displayOrder']);
        $this->showForm = false;
        $this->loadChildren();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        if ($this->deleteId) {
            Child::destroy($this->deleteId);
            $this->loadChildren();
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
        $this->reset(['editingId', 'name', 'avatarColor', 'displayOrder']);
        $this->showForm = false;
    }
}; ?>

<div>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">Children</h2>
            <button wire:click="create"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Add Child
            </button>
        </div>

        <!-- Children list -->
        @if($children->isNotEmpty())
            <ul class="space-y-3 mb-6">
                @foreach($children as $child)
                    <li wire:key="child-{{ $child->id }}"
                        class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-700 rounded-lg">
                        <span class="w-6 h-6 rounded-full shrink-0"
                              style="background: {{ $child->avatar_color }}"></span>
                        <span class="flex-1 font-medium">{{ $child->name }}</span>
                        <span class="text-sm text-slate-500 dark:text-slate-400">
                            {{ $child->routineItems()->count() }} routine items
                        </span>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.children.routines', $child) }}"
                               class="px-3 py-1 text-sm bg-slate-200 dark:bg-slate-600 rounded hover:bg-slate-300 dark:hover:bg-slate-500 transition-colors">
                                Routines
                            </a>
                            <button wire:click="edit({{ $child->id }})"
                                    class="px-3 py-1 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                Edit
                            </button>
                            <button wire:click="confirmDelete({{ $child->id }})"
                                    class="px-3 py-1 text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                Delete
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-center text-slate-500 dark:text-slate-400 py-8">
                No children added yet. Click "Add Child" to get started.
            </p>
        @endif

        <!-- Edit/Create Form -->
        @if($showForm)
            <div class="border-t dark:border-slate-600 pt-6">
                <h3 class="text-lg font-semibold mb-4">
                    {{ $editingId ? 'Edit Child' : 'Add New Child' }}
                </h3>
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Name
                        </label>
                        <input type="text"
                               id="name"
                               wire:model="name"
                               class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-4">
                        <div>
                            <label for="avatarColor" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Avatar Color
                            </label>
                            <input type="color"
                                   id="avatarColor"
                                   wire:model="avatarColor"
                                   class="h-10 w-20 rounded cursor-pointer">
                            @error('avatarColor')
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
                    Are you sure you want to delete this child? This will also delete all their routine items.
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

