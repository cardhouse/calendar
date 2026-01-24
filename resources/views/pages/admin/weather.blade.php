<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\Weather\WeatherService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] #[Title('Weather Settings')] class extends Component
{
    public bool $enabled = false;
    public ?float $latitude = null;
    public ?float $longitude = null;
    public ?string $locationName = null;
    public string $units = 'fahrenheit';
    public string $widgetSize = 'medium';
    public bool $showFeelsLike = true;
    public bool $showHighLow = true;
    public bool $showPrecipitation = true;

    public string $searchQuery = '';

    /** @var list<array{name: string, latitude: float, longitude: float, display: string}> */
    public array $searchResults = [];

    public bool $showSearchResults = false;
    public bool $saved = false;

    public function mount(): void
    {
        $this->enabled = (bool) Setting::get('weather.enabled', false);

        $location = Setting::get('weather.location', ['lat' => null, 'lon' => null, 'name' => null]);
        $this->latitude = $location['lat'] ?? null;
        $this->longitude = $location['lon'] ?? null;
        $this->locationName = $location['name'] ?? null;

        $this->units = Setting::get('weather.units', 'fahrenheit');
        $this->widgetSize = Setting::get('weather.widget_size', 'medium');
        $this->showFeelsLike = (bool) Setting::get('weather.show_feels_like', true);
        $this->showHighLow = (bool) Setting::get('weather.show_high_low', true);
        $this->showPrecipitation = (bool) Setting::get('weather.show_precipitation', true);
    }

    public function updatedSearchQuery(): void
    {
        if (strlen($this->searchQuery) < 2) {
            $this->searchResults = [];
            $this->showSearchResults = false;

            return;
        }

        $service = app(WeatherService::class);
        $this->searchResults = $service->searchLocations($this->searchQuery);
        $this->showSearchResults = count($this->searchResults) > 0;
    }

    public function selectLocation(int $index): void
    {
        if (! isset($this->searchResults[$index])) {
            return;
        }

        $location = $this->searchResults[$index];
        $this->latitude = $location['latitude'];
        $this->longitude = $location['longitude'];
        $this->locationName = $location['display'];
        $this->searchQuery = '';
        $this->searchResults = [];
        $this->showSearchResults = false;
    }

    public function clearLocation(): void
    {
        $this->latitude = null;
        $this->longitude = null;
        $this->locationName = null;
    }

    public function save(): void
    {
        Setting::set('weather.enabled', $this->enabled);
        Setting::set('weather.location', [
            'lat' => $this->latitude,
            'lon' => $this->longitude,
            'name' => $this->locationName,
        ]);
        Setting::set('weather.units', $this->units);
        Setting::set('weather.widget_size', $this->widgetSize);
        Setting::set('weather.show_feels_like', $this->showFeelsLike);
        Setting::set('weather.show_high_low', $this->showHighLow);
        Setting::set('weather.show_precipitation', $this->showPrecipitation);

        // Refresh weather cache if enabled and location is set
        if ($this->enabled && $this->latitude !== null) {
            $service = app(WeatherService::class);
            $service->refreshWeather();
        }

        $this->saved = true;
    }
}; ?>

<div>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">Weather Settings</h2>
            @if($saved)
                <span class="text-emerald-600 dark:text-emerald-400 text-sm" x-data x-init="setTimeout(() => $el.remove(), 3000)">
                    Settings saved!
                </span>
            @endif
        </div>

        <form wire:submit="save" class="space-y-6">
            <!-- Enable/Disable Toggle -->
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700 rounded-lg">
                <div>
                    <h3 class="font-medium">Enable Weather Widget</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Show weather information on the dashboard</p>
                </div>
                <button type="button"
                        wire:click="$toggle('enabled')"
                        class="w-12 h-7 rounded-full relative transition-colors {{ $enabled ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}">
                    <span class="absolute top-1 w-5 h-5 rounded-full bg-white transition-transform shadow {{ $enabled ? 'left-6' : 'left-1' }}"></span>
                </button>
            </div>

            <!-- Location Search -->
            <div class="relative">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Location
                </label>

                @if($locationName)
                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700 rounded-lg">
                        <div>
                            <p class="font-medium">{{ $locationName }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ $latitude }}, {{ $longitude }}
                            </p>
                        </div>
                        <button type="button"
                                wire:click="clearLocation"
                                class="px-3 py-1 text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                            Change
                        </button>
                    </div>
                @else
                    <div class="relative">
                        <input type="text"
                               wire:model.live.debounce.300ms="searchQuery"
                               placeholder="Search for a city..."
                               class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">

                        @if($showSearchResults)
                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-slate-700 rounded-lg shadow-lg border border-slate-200 dark:border-slate-600 max-h-60 overflow-y-auto">
                                @foreach($searchResults as $index => $result)
                                    <button type="button"
                                            wire:click="selectLocation({{ $index }})"
                                            class="w-full px-4 py-3 text-left hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors {{ $loop->first ? 'rounded-t-lg' : '' }} {{ $loop->last ? 'rounded-b-lg' : '' }}">
                                        <span class="font-medium">{{ $result['name'] }}</span>
                                        <span class="text-sm text-slate-500 dark:text-slate-400 ml-2">
                                            {{ $result['display'] }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Start typing to search for your city
                    </p>
                @endif
            </div>

            <!-- Temperature Units -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Temperature Units
                </label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio"
                               wire:model="units"
                               value="fahrenheit"
                               class="text-blue-600 focus:ring-blue-500">
                        <span>Fahrenheit (°F)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio"
                               wire:model="units"
                               value="celsius"
                               class="text-blue-600 focus:ring-blue-500">
                        <span>Celsius (°C)</span>
                    </label>
                </div>
            </div>

            <!-- Widget Size -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Widget Size
                </label>
                <div class="grid grid-cols-3 gap-4">
                    <label class="relative cursor-pointer">
                        <input type="radio"
                               wire:model="widgetSize"
                               value="compact"
                               class="peer sr-only">
                        <div class="p-4 rounded-lg border-2 transition-colors peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-slate-200 dark:border-slate-600">
                            <p class="font-medium text-center">Compact</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 text-center mt-1">72° ☀️</p>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio"
                               wire:model="widgetSize"
                               value="medium"
                               class="peer sr-only">
                        <div class="p-4 rounded-lg border-2 transition-colors peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-slate-200 dark:border-slate-600">
                            <p class="font-medium text-center">Medium</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 text-center mt-1">Temp + Condition</p>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio"
                               wire:model="widgetSize"
                               value="large"
                               class="peer sr-only">
                        <div class="p-4 rounded-lg border-2 transition-colors peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-slate-200 dark:border-slate-600">
                            <p class="font-medium text-center">Large</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 text-center mt-1">Full + Alerts</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Display Options -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Display Options
                </label>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox"
                               wire:model="showFeelsLike"
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Show "Feels Like" temperature</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox"
                               wire:model="showHighLow"
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Show High/Low temperatures</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox"
                               wire:model="showPrecipitation"
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Show precipitation alerts</span>
                    </label>
                </div>
            </div>

            <!-- Save Button -->
            <div class="pt-4 border-t dark:border-slate-600">
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Preview Section -->
    @if($enabled && $locationName)
        <div class="mt-6 bg-slate-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Widget Preview</h3>
            <div class="bg-slate-900 rounded-lg p-6">
                <livewire:dashboard.weather-widget />
            </div>
        </div>
    @endif
</div>
