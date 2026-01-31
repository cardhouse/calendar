<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Child;
use App\Models\DepartureTime;
use App\Models\EventRoutineItem;
use App\Models\Setting;
use App\Services\DepartureService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')] #[Title('Family Morning Dashboard')] class extends Component {
    public bool $weatherEnabled = false;
    /** @var Collection<int, Child> */
    public Collection $children;

    public ?int $departureTimestamp = null;
    public ?string $departureName = null;

    /** @var array<int, array{id: int, name: string, timestamp: int, color: string, starts_at_formatted: string}> */
    public array $eventData = [];

    /** @var array<int, EloquentCollection<int, EventRoutineItem>> Event routine items keyed by child_id */
    public array $eventRoutinesByChild = [];

    public ?string $nextEventName = null;

    public function mount(): void
    {
        // Check if weather is enabled
        $this->weatherEnabled = (bool) Setting::get('weather.enabled', false);

        // Load children with their routine items and today's completions in a single query
        $this->children = Child::with(['routineItems.completions' => function ($q) {
            $q->whereDate('completion_date', today());
        }])->orderBy('display_order')->get();

        // Get next departure (from DepartureTime or CalendarEvent with departure_time)
        $departureService = app(DepartureService::class);
        $nextDeparture = $departureService->getNextDeparture();
        if ($nextDeparture) {
            $this->departureTimestamp = $nextDeparture['timestamp'];
            $this->departureName = $nextDeparture['name'];
        }

        // Get upcoming events as array with timestamps for Alpine.js
        $events = CalendarEvent::getUpcoming(3);
        $this->eventData = $events->map(fn ($e) => [
            'id' => $e->id,
            'name' => $e->name,
            'timestamp' => $e->starts_at->timestamp,
            'color' => $e->color,
            'starts_at_formatted' => $e->starts_at->format('M j, g:i A'),
        ])->toArray();

        // Load event routines ONCE for all children (instead of per-child)
        $this->loadEventRoutinesForAllChildren();
    }

    /**
     * Load event routine items for the next event, for all children at once.
     * This replaces the per-child getNextEventWithRoutines() to avoid N+1 queries.
     */
    private function loadEventRoutinesForAllChildren(): void
    {
        $now = now();
        $candidates = [];

        // Get upcoming CalendarEvents with event routine items (single query)
        $calendarEvents = CalendarEvent::where(function ($query) use ($now) {
            $query->where(function ($q) use ($now) {
                $q->whereNotNull('departure_time')
                    ->where('departure_time', '>', $now);
            })->orWhere(function ($q) use ($now) {
                $q->whereNull('departure_time')
                    ->where('starts_at', '>', $now);
            });
        })->get();

        foreach ($calendarEvents as $event) {
            $nextTime = $event->departure_time ?? $event->starts_at;
            $candidates[] = [
                'eventable_type' => CalendarEvent::class,
                'eventable_id' => $event->id,
                'event' => $event,
                'next_time' => $nextTime,
            ];
        }

        // Get active DepartureTimes (single query)
        $departureTimes = DepartureTime::active()->get();

        foreach ($departureTimes as $departure) {
            $nextOccurrence = $departure->getNextOccurrence();
            if ($nextOccurrence) {
                $candidates[] = [
                    'eventable_type' => DepartureTime::class,
                    'eventable_id' => $departure->id,
                    'event' => $departure,
                    'next_time' => $nextOccurrence,
                ];
            }
        }

        if (empty($candidates)) {
            return;
        }

        // Sort by next_time and get the soonest
        usort($candidates, fn ($a, $b) => $a['next_time']->timestamp <=> $b['next_time']->timestamp);
        $nextEvent = $candidates[0];

        // Load ALL event routine items for this event with completions in ONE query
        $eventRoutineItems = EventRoutineItem::where('eventable_type', $nextEvent['eventable_type'])
            ->where('eventable_id', $nextEvent['eventable_id'])
            ->with(['completions' => fn ($q) => $q->whereDate('completion_date', today())])
            ->orderBy('display_order')
            ->get();

        if ($eventRoutineItems->isEmpty()) {
            return;
        }

        $this->nextEventName = $nextEvent['event']->name;

        // Group by child_id for easy lookup in child-checklist components
        $this->eventRoutinesByChild = $eventRoutineItems->groupBy('child_id')->all();
    }
}; ?>

<div class="min-h-screen bg-slate-900 p-6 relative"
     x-data="{ showCompleted: $persist(true).as('dashboard-show-completed') }">
        <!-- Controls -->
        <div class="fixed bottom-4 right-4 flex items-center gap-2 z-50">
            <!-- Toggle completed items visibility -->
            <button @click="showCompleted = !showCompleted; $dispatch('toggle-completed', showCompleted)"
                    class="p-2 rounded-lg transition-colors"
                    :class="showCompleted ? 'text-slate-500 hover:text-slate-300 hover:bg-slate-800' : 'text-emerald-400 hover:text-emerald-300 hover:bg-slate-800'"
                    :title="showCompleted ? 'Hide completed items' : 'Show completed items'">
                <!-- Eye icon (visible) -->
                <svg x-show="showCompleted" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <!-- Eye-off icon (hidden) -->
                <svg x-show="!showCompleted" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
            </button>

            <!-- Admin link -->
            <a href="{{ route('admin.children.index') }}"
               class="p-2 rounded-lg text-slate-500 hover:text-slate-300 hover:bg-slate-800 transition-colors"
               title="Admin Settings">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </a>
        </div>

        <!-- Header: Clock (left) + Weather (center) + Departure Timer (right) -->
        <header class="flex flex-col lg:flex-row items-center justify-between gap-6 mb-8">
            <!-- Current Time (Alpine.js - NO server polling) -->
            <div x-data="clockDisplay()"
                 x-init="startClock()"
                 class="text-center lg:text-left">
                <time class="text-6xl md:text-7xl font-bold text-white tabular-nums">
                    <span x-text="timeDisplay"></span>
                    <span class="text-2xl md:text-3xl text-slate-400" x-text="amPm"></span>
                </time>
                <p class="text-lg md:text-xl text-slate-400 mt-1" x-text="dateDisplay"></p>
            </div>

            <!-- Weather Widget (center) -->
            @if($weatherEnabled)
                <livewire:dashboard.weather-widget />
            @endif

            <!-- Departure Timer -->
            @if($departureTimestamp)
                <div x-data="departureTimer({{ $departureTimestamp }})"
                     x-init="startTimer()"
                     class="text-center lg:text-right">
                    <div class="text-6xl md:text-7xl font-bold tabular-nums transition-colors duration-500"
                         :class="textColorClass"
                         x-text="display"></div>
                    <p class="text-lg md:text-xl mt-1 transition-colors duration-500"
                       :class="labelColorClass">
                        <span x-text="label"></span> · {{ $departureName }}
                    </p>
                </div>
            @else
                <div class="text-center lg:text-right">
                    <div class="text-6xl md:text-7xl font-bold text-slate-600 tabular-nums">--:--</div>
                    <p class="text-lg md:text-xl text-slate-500 mt-1">No departure scheduled</p>
                </div>
            @endif
        </header>

        <!-- Routines grid: 3 columns on large screens -->
        <main>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($children as $child)
                    <livewire:dashboard.child-checklist
                        :child="$child"
                        :event-routine-items="$eventRoutinesByChild[$child->id] ?? null"
                        :event-name="$nextEventName"
                        :key="'child-' . $child->id" />
                @endforeach
                @if($children->isEmpty())
                    <div class="col-span-full bg-slate-800 rounded-2xl p-8 text-center">
                        <p class="text-xl text-slate-400">No children configured yet.</p>
                        <a href="{{ route('admin.children.index') }}" class="mt-4 inline-block text-blue-400 hover:text-blue-300 transition-colors">
                            Add children in admin →
                        </a>
                    </div>
                @endif
            </div>
        </main>

        <!-- Upcoming events footer -->
        <footer class="mt-8">
            <h2 class="text-2xl font-semibold text-slate-300 mb-4">Upcoming Events</h2>
            @if(count($eventData) > 0)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($eventData as $event)
                        <div wire:key="event-{{ $event['id'] }}"
                             x-data="eventCountdown({{ $event['timestamp'] }})"
                             x-init="startTimer()"
                             class="bg-slate-800 rounded-xl p-5 border-l-4"
                             style="border-color: {{ $event['color'] }}">
                            <h3 class="text-xl font-semibold text-white mb-2">
                                {{ $event['name'] }}
                            </h3>
                            <p class="text-3xl font-bold text-slate-200 mb-1" x-text="countdown"></p>
                            <p class="text-sm text-slate-400">
                                {{ $event['starts_at_formatted'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-slate-800 rounded-xl p-6 text-center">
                    <p class="text-xl text-slate-400">No upcoming events</p>
                </div>
            @endif
        </footer>
</div>

