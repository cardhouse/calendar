<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin')] #[Title('Manage Calendar Events')] class extends Component {
    /** @var Collection<int, CalendarEvent> */
    public Collection $events;

    public string $filter = 'upcoming';

    public ?int $editingId = null;
    public string $name = '';
    public string $startsAt = '';
    public string $departureTime = '';
    public string $category = '';
    public string $color = '#3B82F6';

    public bool $showForm = false;
    public bool $showDeleteConfirm = false;
    public ?int $deleteId = null;

    /** @var array<string, string> */
    public array $categoryOptions = [
        '' => 'None',
        'birthday' => 'Birthday',
        'sports' => 'Sports',
        'school' => 'School',
        'appointment' => 'Appointment',
        'family' => 'Family',
        'holiday' => 'Holiday',
        'other' => 'Other',
    ];

    public function mount(): void
    {
        $this->loadEvents();
    }

    public function loadEvents(): void
    {
        $query = CalendarEvent::query();

        if ($this->filter === 'upcoming') {
            $query->upcoming();
        } elseif ($this->filter === 'past') {
            $query->past();
        } else {
            $query->orderBy('starts_at');
        }

        $this->events = $query->get();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->loadEvents();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'startsAt', 'departureTime', 'category', 'color']);
        $this->startsAt = now()->addDay()->format('Y-m-d\TH:i');
        $this->color = '#3B82F6';
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $event = CalendarEvent::findOrFail($id);
        $this->editingId = $event->id;
        $this->name = $event->name;
        $this->startsAt = $event->starts_at->format('Y-m-d\TH:i');
        $this->departureTime = $event->departure_time?->format('Y-m-d\TH:i') ?? '';
        $this->category = $event->category ?? '';
        $this->color = $event->color;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'startsAt' => 'required|date',
            'departureTime' => 'nullable|date|before:startsAt',
            'category' => 'nullable|string|max:50',
            'color' => 'required|string|max:7',
        ]);

        $data = [
            'name' => $validated['name'],
            'starts_at' => $validated['startsAt'],
            'departure_time' => $validated['departureTime'] ?: null,
            'category' => $validated['category'] ?: null,
            'color' => $validated['color'],
        ];

        if ($this->editingId) {
            CalendarEvent::findOrFail($this->editingId)->update($data);
        } else {
            CalendarEvent::create($data);
        }

        $this->reset(['editingId', 'name', 'startsAt', 'departureTime', 'category', 'color']);
        $this->showForm = false;
        $this->loadEvents();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        if ($this->deleteId) {
            CalendarEvent::destroy($this->deleteId);
            $this->loadEvents();
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
        $this->reset(['editingId', 'name', 'startsAt', 'departureTime', 'category', 'color']);
        $this->showForm = false;
    }
}; ?>

<div>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">Calendar Events</h2>
            <button wire:click="create"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Add Event
            </button>
        </div>

        <!-- Filter tabs -->
        <div class="flex gap-2 mb-6">
            <button wire:click="setFilter('upcoming')"
                    class="px-4 py-2 rounded-lg transition-colors {{ $filter === 'upcoming' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                Upcoming
            </button>
            <button wire:click="setFilter('past')"
                    class="px-4 py-2 rounded-lg transition-colors {{ $filter === 'past' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                Past
            </button>
            <button wire:click="setFilter('all')"
                    class="px-4 py-2 rounded-lg transition-colors {{ $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                All
            </button>
        </div>

        <!-- Events list -->
        @if($events->isNotEmpty())
            <ul class="space-y-3 mb-6">
                @foreach($events as $event)
                    <li wire:key="event-{{ $event->id }}"
                        class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-700 rounded-lg border-l-4"
                        style="border-color: {{ $event->color }}">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-lg">{{ $event->name }}</span>
                                @if($event->category)
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-slate-200 dark:bg-slate-600">
                                        {{ ucfirst($event->category) }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">
                                {{ $event->starts_at->format('l, M j, Y \a\t g:i A') }}
                                @if(!$event->isPast())
                                    <span class="ml-2 text-blue-600 dark:text-blue-400">
                                        ({{ $event->countdown }})
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button wire:click="edit({{ $event->id }})"
                                    class="px-3 py-1 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                Edit
                            </button>
                            <button wire:click="confirmDelete({{ $event->id }})"
                                    class="px-3 py-1 text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                Delete
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-center text-slate-500 dark:text-slate-400 py-8">
                @if($filter === 'upcoming')
                    No upcoming events. Click "Add Event" to create one.
                @elseif($filter === 'past')
                    No past events.
                @else
                    No events found.
                @endif
            </p>
        @endif

        <!-- Edit/Create Form -->
        @if($showForm)
            <div class="border-t dark:border-slate-600 pt-6">
                <h3 class="text-lg font-semibold mb-4">
                    {{ $editingId ? 'Edit Event' : 'Add New Event' }}
                </h3>
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Event Name
                        </label>
                        <input type="text"
                               id="name"
                               wire:model="name"
                               placeholder="e.g., Birthday Party"
                               class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="startsAt" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Event Date & Time
                            </label>
                            <input type="datetime-local"
                                   id="startsAt"
                                   wire:model="startsAt"
                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('startsAt')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="departureTime" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Departure Time <span class="text-slate-400 font-normal">(optional)</span>
                            </label>
                            <input type="datetime-local"
                                   id="departureTime"
                                   wire:model="departureTime"
                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">When you need to leave for this event</p>
                            @error('departureTime')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="category" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Category
                            </label>
                            <select id="category"
                                    wire:model="category"
                                    class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach($categoryOptions as $value => $label)
                                    <option wire:key="category-option-{{ $value }}" value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="color" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Color
                            </label>
                            <input type="color"
                                   id="color"
                                   wire:model="color"
                                   class="h-10 w-20 rounded cursor-pointer">
                            @error('color')
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
                    Are you sure you want to delete this event?
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

