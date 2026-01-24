<?php

declare(strict_types=1);

use App\Models\Child;
use App\Models\EventRoutineItem;
use App\Models\RoutineItem;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Volt\Component;

new class extends Component {
    public Child $child;

    /** @var Collection<int, EventRoutineItem>|null */
    public ?Collection $eventRoutineItems = null;

    public ?string $eventName = null;

    /**
     * Mount receives pre-loaded data from dashboard to avoid N+1 queries.
     *
     * @param Collection<int, EventRoutineItem>|null $eventRoutineItems Pre-loaded from dashboard
     */
    public function mount(Child $child, ?Collection $eventRoutineItems = null, ?string $eventName = null): void
    {
        $this->child = $child;
        $this->eventRoutineItems = $eventRoutineItems;
        $this->eventName = $eventName;
    }

    public function toggleItem(int $itemId): void
    {
        $item = RoutineItem::find($itemId);

        if ($item && $item->child_id === $this->child->id) {
            $item->toggleCompletion();
            // Refresh the child with updated completions
            $this->child->refresh();
            $this->child->load(['routineItems.completions' => function ($q) {
                $q->whereDate('completion_date', today());
            }]);
        }
    }

    public function toggleEventItem(int $itemId): void
    {
        $item = EventRoutineItem::find($itemId);

        if ($item && $item->child_id === $this->child->id) {
            $item->toggleCompletion();
            // Refresh just this item's completions
            $item->load(['completions' => fn ($q) => $q->whereDate('completion_date', today())]);

            // Update the item in our collection
            if ($this->eventRoutineItems) {
                $this->eventRoutineItems = $this->eventRoutineItems->map(function ($existingItem) use ($item) {
                    return $existingItem->id === $item->id ? $item : $existingItem;
                });
            }
        }
    }

    public function getCompletedCountProperty(): int
    {
        $dailyCompleted = $this->child->routineItems->filter(fn ($item) => $item->isCompletedFor())->count();
        $eventCompleted = $this->eventRoutineItems?->filter(fn ($item) => $item->isCompletedFor())->count() ?? 0;

        return $dailyCompleted + $eventCompleted;
    }

    public function getTotalCountProperty(): int
    {
        $dailyTotal = $this->child->routineItems->count();
        $eventTotal = $this->eventRoutineItems?->count() ?? 0;

        return $dailyTotal + $eventTotal;
    }

    public function getProgressPercentProperty(): int
    {
        if ($this->totalCount === 0) {
            return 100;
        }

        return (int) round(($this->completedCount / $this->totalCount) * 100);
    }

    public function getAllCompleteProperty(): bool
    {
        return $this->totalCount > 0 && $this->completedCount === $this->totalCount;
    }
}; ?>

<div wire:key="child-{{ $child->id }}"
     class="bg-slate-800 rounded-2xl p-6 border-l-4"
     style="border-color: {{ $child->avatar_color }}"
     x-data="{
         showCompleted: JSON.parse(localStorage.getItem('dashboard-show-completed') ?? 'true'),
         allComplete: {{ $this->allComplete ? 'true' : 'false' }},
         cardVisible: true,
         hidingItems: {},
         hideDelay: 3000,
         init() {
             // When all complete and hiding completed, start card hide timer
             if (this.allComplete && !this.showCompleted) {
                 setTimeout(() => { this.cardVisible = false }, this.hideDelay);
             }
         },
         shouldShowItem(itemId, isCompleted) {
             if (this.showCompleted) return true;
             if (!isCompleted) return true;
             // Item is completed and we're hiding completed - check if still in grace period
             return this.hidingItems[itemId] === true;
         },
         markItemCompleted(itemId) {
             if (!this.showCompleted) {
                 // Start grace period for this item
                 this.hidingItems[itemId] = true;
                 setTimeout(() => {
                     this.hidingItems[itemId] = false;
                 }, this.hideDelay);
             }
         }
     }"
     @toggle-completed.window="showCompleted = $event.detail; if ($event.detail) { cardVisible = true; hidingItems = {}; }"
     x-show="showCompleted || !allComplete || cardVisible"
     x-transition:leave="transition ease-out duration-500"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95">
    <!-- Header with name and progress -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-semibold text-white flex items-center gap-3">
            <span class="w-4 h-4 rounded-full"
                  style="background: {{ $child->avatar_color }}"></span>
            {{ $child->name }}
        </h2>
        <span class="text-lg text-slate-400">
            {{ $this->completedCount }}/{{ $this->totalCount }}
        </span>
    </div>

    <!-- Progress bar -->
    <div class="h-2 bg-slate-700 rounded-full mb-4 overflow-hidden">
        <div class="h-full bg-emerald-500 transition-all duration-300"
             style="width: {{ $this->progressPercent }}%"></div>
    </div>

    <!-- Daily Routine Items -->
    @if($child->routineItems->isNotEmpty())
        <ul class="space-y-3">
            @foreach($child->routineItems as $item)
                @php $isCompleted = $item->isCompletedFor(); @endphp
                <li wire:key="item-{{ $item->id }}-{{ $isCompleted ? 'done' : 'todo' }}"
                    x-data="{ completed: {{ $isCompleted ? 'true' : 'false' }}, visible: true }"
                    x-init="if (completed && !showCompleted) { setTimeout(() => visible = false, hideDelay) }"
                    x-show="showCompleted || !completed || visible"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0">
                    <button wire:click="toggleItem({{ $item->id }})"
                            class="w-full flex items-center gap-4 p-3 rounded-xl
                                   transition-all duration-200
                                   {{ $isCompleted
                                      ? 'bg-emerald-900/30 text-emerald-300'
                                      : 'bg-slate-700/50 text-white hover:bg-slate-700' }}">
                        <!-- Checkbox indicator -->
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0
                                     {{ $isCompleted
                                        ? 'bg-emerald-500'
                                        : 'bg-slate-600 border-2 border-slate-500' }}">
                            @if($isCompleted)
                                <svg class="w-5 h-5 text-white animate-checkmark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            @endif
                        </span>
                        <!-- Item name -->
                        <span class="text-xl text-left {{ $isCompleted ? 'line-through opacity-75' : '' }}">
                            {{ $item->name }}
                        </span>
                    </button>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-center text-slate-400 py-4">No routine items yet.</p>
    @endif

    <!-- Event-Specific Routine Items -->
    @if($eventRoutineItems && $eventRoutineItems->isNotEmpty())
        <div class="mt-6 pt-4 border-t border-slate-700">
            <h3 class="text-sm font-medium text-slate-400 uppercase tracking-wide mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                For: {{ $eventName }}
            </h3>
            <ul class="space-y-3">
                @foreach($eventRoutineItems as $eventItem)
                    @php $isCompleted = $eventItem->isCompletedFor(); @endphp
                    <li wire:key="event-item-{{ $eventItem->id }}-{{ $isCompleted ? 'done' : 'todo' }}"
                        x-data="{ completed: {{ $isCompleted ? 'true' : 'false' }}, visible: true }"
                        x-init="if (completed && !showCompleted) { setTimeout(() => visible = false, hideDelay) }"
                        x-show="showCompleted || !completed || visible"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0">
                        <button wire:click="toggleEventItem({{ $eventItem->id }})"
                                class="w-full flex items-center gap-4 p-3 rounded-xl
                                       transition-all duration-200
                                       {{ $isCompleted
                                          ? 'bg-blue-900/30 text-blue-300'
                                          : 'bg-slate-700/50 text-white hover:bg-slate-700' }}">
                            <!-- Checkbox indicator with different color for event items -->
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0
                                         {{ $isCompleted
                                            ? 'bg-blue-500'
                                            : 'bg-slate-600 border-2 border-blue-500/50' }}">
                                @if($isCompleted)
                                    <svg class="w-5 h-5 text-white animate-checkmark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </span>
                            <!-- Item name -->
                            <span class="text-xl text-left {{ $isCompleted ? 'line-through opacity-75' : '' }}">
                                {{ $eventItem->name }}
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Celebration message when all complete -->
    @if($this->allComplete)
        <div class="text-center py-4 mt-4 bg-emerald-900/30 rounded-xl">
            <p class="text-2xl text-emerald-300 font-semibold">
                🎉 All done! Great job, {{ $child->name }}!
            </p>
        </div>
    @endif
</div>
