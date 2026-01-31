<?php

declare(strict_types=1);

use App\Models\DepartureTime;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin')] #[Title('Manage Departure Times')] class extends Component {
    /** @var Collection<int, DepartureTime> */
    public Collection $departures;

    public ?int $editingId = null;
    public string $name = '';
    public string $departureTime = '07:30';
    public array $applicableDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    public bool $isActive = true;
    public int $displayOrder = 0;

    public bool $showForm = false;
    public bool $showDeleteConfirm = false;
    public ?int $deleteId = null;

    /** @var array<string, string> */
    public array $dayOptions = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];

    public function mount(): void
    {
        $this->loadDepartures();
    }

    public function loadDepartures(): void
    {
        $this->departures = DepartureTime::orderBy('departure_time')->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'departureTime', 'applicableDays', 'isActive', 'displayOrder']);
        $this->departureTime = '07:30';
        $this->applicableDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $this->isActive = true;
        $this->displayOrder = ($this->departures->max('display_order') ?? 0) + 1;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $departure = DepartureTime::findOrFail($id);
        $this->editingId = $departure->id;
        $this->name = $departure->name;
        $this->departureTime = $departure->departure_time->format('H:i');
        $this->applicableDays = $departure->applicable_days;
        $this->isActive = $departure->is_active;
        $this->displayOrder = $departure->display_order;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'departureTime' => 'required|date_format:H:i',
            'applicableDays' => 'required|array|min:1',
            'applicableDays.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'isActive' => 'boolean',
            'displayOrder' => 'required|integer|min:0',
        ]);

        $data = [
            'name' => $validated['name'],
            'departure_time' => $validated['departureTime'] . ':00',
            'applicable_days' => $validated['applicableDays'],
            'is_active' => $validated['isActive'],
            'display_order' => $validated['displayOrder'],
        ];

        if ($this->editingId) {
            DepartureTime::findOrFail($this->editingId)->update($data);
        } else {
            DepartureTime::create($data);
        }

        $this->reset(['editingId', 'name', 'departureTime', 'applicableDays', 'isActive', 'displayOrder']);
        $this->showForm = false;
        $this->loadDepartures();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        if ($this->deleteId) {
            DepartureTime::destroy($this->deleteId);
            $this->loadDepartures();
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
        $this->reset(['editingId', 'name', 'departureTime', 'applicableDays', 'isActive', 'displayOrder']);
        $this->showForm = false;
    }

    public function toggleActive(int $id): void
    {
        $departure = DepartureTime::find($id);
        if ($departure) {
            $departure->update(['is_active' => !$departure->is_active]);
            $this->loadDepartures();
        }
    }
}; ?>

<div>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">Departure Times</h2>
            <button wire:click="create"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Add Departure
            </button>
        </div>

        <!-- Departures list -->
        @if($departures->isNotEmpty())
            <ul class="space-y-3 mb-6">
                @foreach($departures as $departure)
                    <li wire:key="departure-{{ $departure->id }}"
                        class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-700 rounded-lg {{ !$departure->is_active ? 'opacity-50' : '' }}">
                        <button wire:click="toggleActive({{ $departure->id }})"
                                class="w-10 h-6 rounded-full relative transition-colors {{ $departure->is_active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}">
                            <span class="absolute top-1 w-4 h-4 rounded-full bg-white transition-transform {{ $departure->is_active ? 'left-5' : 'left-1' }}"></span>
                        </button>
                        <span class="font-mono text-lg font-medium">
                            {{ $departure->departure_time->format('g:i A') }}
                        </span>
                        <span class="flex-1 font-medium">{{ $departure->name }}</span>
                        <span class="text-sm text-slate-500 dark:text-slate-400">
                            {{ implode(', ', array_map(fn($d) => ucfirst(substr($d, 0, 3)), $departure->applicable_days)) }}
                        </span>
                        <div class="flex items-center gap-2">
                            <button wire:click="edit({{ $departure->id }})"
                                    class="px-3 py-1 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                Edit
                            </button>
                            <button wire:click="confirmDelete({{ $departure->id }})"
                                    class="px-3 py-1 text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                Delete
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-center text-slate-500 dark:text-slate-400 py-8">
                No departure times configured. Click "Add Departure" to create one.
            </p>
        @endif

        <!-- Edit/Create Form -->
        @if($showForm)
            <div class="border-t dark:border-slate-600 pt-6">
                <h3 class="text-lg font-semibold mb-4">
                    {{ $editingId ? 'Edit Departure Time' : 'Add New Departure Time' }}
                </h3>
                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Name
                            </label>
                            <input type="text"
                                   id="name"
                                   wire:model="name"
                                   placeholder="e.g., School bus"
                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="departureTime" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Time
                            </label>
                            <input type="time"
                                   id="departureTime"
                                   wire:model="departureTime"
                                   class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('departureTime')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Applicable Days
                        </label>
                        <div class="flex flex-wrap gap-3">
                            @foreach($dayOptions as $value => $label)
                                <label wire:key="day-option-{{ $value }}" class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                           wire:model="applicableDays"
                                           value="{{ $value }}"
                                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('applicableDays')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   wire:model="isActive"
                                   class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Active</span>
                        </label>

                        <div>
                            <label for="displayOrder" class="text-sm font-medium text-slate-700 dark:text-slate-300 mr-2">
                                Order:
                            </label>
                            <input type="number"
                                   id="displayOrder"
                                   wire:model="displayOrder"
                                   min="0"
                                   class="w-20 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                    Are you sure you want to delete this departure time?
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

