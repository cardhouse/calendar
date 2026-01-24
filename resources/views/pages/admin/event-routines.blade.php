<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Child;
use App\Models\DepartureTime;
use App\Models\EventRoutineItem;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] #[Title('Manage Event Routines')] class extends Component {
    public string $activeTab = 'departures';

    /** @var Collection<int, DepartureTime> */
    public Collection $departures;

    /** @var Collection<int, CalendarEvent> */
    public Collection $events;

    /** @var Collection<int, Child> */
    public Collection $children;

    public ?int $selectedEventableId = null;
    public ?string $selectedEventableType = null;

    // Form fields
    public bool $showForm = false;
    public ?int $editingId = null;
    public int $childId = 0;
    public string $name = '';
    public int $displayOrder = 0;

    // Delete confirmation
    public bool $showDeleteConfirm = false;
    public ?int $deleteId = null;

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->departures = DepartureTime::with(['eventRoutineItems.child'])
            ->orderBy('departure_time')
            ->get();

        $this->events = CalendarEvent::upcoming()
            ->with(['eventRoutineItems.child'])
            ->get();

        $this->children = Child::orderBy('display_order')->get();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->selectedEventableId = null;
        $this->selectedEventableType = null;
        $this->showForm = false;
    }

    public function selectEventable(string $type, int $id): void
    {
        $this->selectedEventableType = $type;
        $this->selectedEventableId = $id;
        $this->showForm = false;
    }

    public function getSelectedEventableProperty(): DepartureTime|CalendarEvent|null
    {
        if (! $this->selectedEventableType || ! $this->selectedEventableId) {
            return null;
        }

        if ($this->selectedEventableType === 'departure') {
            return DepartureTime::with(['eventRoutineItems.child'])->find($this->selectedEventableId);
        }

        return CalendarEvent::with(['eventRoutineItems.child'])->find($this->selectedEventableId);
    }

    public function create(): void
    {
        if (! $this->selectedEventable) {
            return;
        }

        $this->reset(['editingId', 'childId', 'name', 'displayOrder']);
        $this->childId = $this->children->first()?->id ?? 0;
        $maxOrder = $this->selectedEventable->eventRoutineItems->max('display_order') ?? 0;
        $this->displayOrder = $maxOrder + 1;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $item = EventRoutineItem::findOrFail($id);
        $this->editingId = $item->id;
        $this->childId = $item->child_id;
        $this->name = $item->name;
        $this->displayOrder = $item->display_order;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'childId' => 'required|exists:children,id',
            'name' => 'required|string|max:255',
            'displayOrder' => 'required|integer|min:0',
        ]);

        $eventableType = $this->selectedEventableType === 'departure'
            ? DepartureTime::class
            : CalendarEvent::class;

        $data = [
            'eventable_type' => $eventableType,
            'eventable_id' => $this->selectedEventableId,
            'child_id' => $validated['childId'],
            'name' => $validated['name'],
            'display_order' => $validated['displayOrder'],
        ];

        if ($this->editingId) {
            EventRoutineItem::findOrFail($this->editingId)->update($data);
        } else {
            EventRoutineItem::create($data);
        }

        $this->reset(['editingId', 'childId', 'name', 'displayOrder']);
        $this->showForm = false;
        $this->loadData();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        if ($this->deleteId) {
            EventRoutineItem::destroy($this->deleteId);
            $this->loadData();
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
        $this->reset(['editingId', 'childId', 'name', 'displayOrder']);
        $this->showForm = false;
    }
}; ?>

<div>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-6">Event Routines</h2>
        <p class="text-slate-600 dark:text-slate-400 mb-6">
            Add routine items that are specific to certain events or departures. These will appear in each child's checklist when that event is coming up next.
        </p>

        <!-- Tabs -->
        <div class="flex gap-2 mb-6">
            <button wire:click="setTab('departures')"
                    class="px-4 py-2 rounded-lg transition-colors {{ $activeTab === 'departures' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                Departure Times
            </button>
            <button wire:click="setTab('events')"
                    class="px-4 py-2 rounded-lg transition-colors {{ $activeTab === 'events' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                Calendar Events
            </button>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Event/Departure List (Left Column) -->
            <div class="lg:w-1/3">
                @if($activeTab === 'departures')
                    <h3 class="text-lg font-semibold mb-3">Select a Departure</h3>
                    @if($departures->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach($departures as $departure)
                                <li>
                                    <button wire:click="selectEventable('departure', {{ $departure->id }})"
                                            class="w-full text-left p-3 rounded-lg transition-colors {{ $selectedEventableType === 'departure' && $selectedEventableId === $departure->id ? 'bg-blue-100 dark:bg-blue-900/30 border-2 border-blue-500' : 'bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600' }}">
                                        <div class="font-medium">{{ $departure->name }}</div>
                                        <div class="text-sm text-slate-500 dark:text-slate-400">
                                            {{ $departure->departure_time->format('g:i A') }}
                                            · {{ $departure->eventRoutineItems->count() }} routine{{ $departure->eventRoutineItems->count() === 1 ? '' : 's' }}
                                        </div>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-slate-500 dark:text-slate-400 py-4">
                            No departure times configured. <a href="{{ route('admin.departures.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Add one first</a>.
                        </p>
                    @endif
                @else
                    <h3 class="text-lg font-semibold mb-3">Select an Event</h3>
                    @if($events->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach($events as $event)
                                <li>
                                    <button wire:click="selectEventable('event', {{ $event->id }})"
                                            class="w-full text-left p-3 rounded-lg transition-colors {{ $selectedEventableType === 'event' && $selectedEventableId === $event->id ? 'bg-blue-100 dark:bg-blue-900/30 border-2 border-blue-500' : 'bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600' }}">
                                        <div class="font-medium">{{ $event->name }}</div>
                                        <div class="text-sm text-slate-500 dark:text-slate-400">
                                            {{ $event->starts_at->format('M j, Y g:i A') }}
                                            · {{ $event->eventRoutineItems->count() }} routine{{ $event->eventRoutineItems->count() === 1 ? '' : 's' }}
                                        </div>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-slate-500 dark:text-slate-400 py-4">
                            No upcoming events. <a href="{{ route('admin.events.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Add one first</a>.
                        </p>
                    @endif
                @endif
            </div>

            <!-- Routine Items (Right Column) -->
            <div class="lg:w-2/3">
                @if($this->selectedEventable)
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">
                            Routines for "{{ $this->selectedEventable->name }}"
                        </h3>
                        <button wire:click="create"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                            Add Routine Item
                        </button>
                    </div>

                    @if($this->selectedEventable->eventRoutineItems->isNotEmpty())
                        <ul class="space-y-2 mb-6">
                            @foreach($this->selectedEventable->eventRoutineItems as $item)
                                <li wire:key="routine-item-{{ $item->id }}"
                                    class="flex items-center gap-4 p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
                                    <span class="w-3 h-3 rounded-full shrink-0"
                                          style="background: {{ $item->child->avatar_color }}"></span>
                                    <div class="flex-1">
                                        <span class="font-medium">{{ $item->name }}</span>
                                        <span class="text-sm text-slate-500 dark:text-slate-400 ml-2">
                                            ({{ $item->child->name }})
                                        </span>
                                    </div>
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
                        <p class="text-slate-500 dark:text-slate-400 py-8 text-center bg-slate-50 dark:bg-slate-700 rounded-lg mb-6">
                            No routine items yet. Click "Add Routine Item" to create one.
                        </p>
                    @endif

                    <!-- Add/Edit Form -->
                    @if($showForm)
                        <div class="border-t dark:border-slate-600 pt-6">
                            <h4 class="text-md font-semibold mb-4">
                                {{ $editingId ? 'Edit Routine Item' : 'Add New Routine Item' }}
                            </h4>
                            <form wire:submit="save" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="childId" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                            Child
                                        </label>
                                        <select id="childId"
                                                wire:model="childId"
                                                class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            @foreach($children as $child)
                                                <option value="{{ $child->id }}">{{ $child->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('childId')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="displayOrder" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                            Order
                                        </label>
                                        <input type="number"
                                               id="displayOrder"
                                               wire:model="displayOrder"
                                               min="0"
                                               class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        @error('displayOrder')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Routine Item Name
                                    </label>
                                    <input type="text"
                                           id="name"
                                           wire:model="name"
                                           placeholder="e.g., Pack backpack, Get bowling ball"
                                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('name')
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
                @else
                    <div class="flex items-center justify-center h-48 bg-slate-50 dark:bg-slate-700 rounded-lg">
                        <p class="text-slate-500 dark:text-slate-400">
                            Select a {{ $activeTab === 'departures' ? 'departure time' : 'calendar event' }} to manage its routines
                        </p>
                    </div>
                @endif
            </div>
        </div>
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

