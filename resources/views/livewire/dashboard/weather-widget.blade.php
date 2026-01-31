<?php

declare(strict_types=1);

use App\Services\Weather\WeatherData;
use App\Services\Weather\WeatherService;
use Livewire\Component;

new class extends Component
{
    public ?array $weatherData = null;
    public string $size = 'medium';
    public bool $showFeelsLike = true;
    public bool $showHighLow = true;
    public bool $showPrecipitation = true;
    public string $units = 'fahrenheit';

    public function mount(): void
    {
        $this->loadWeather();
    }

    public function loadWeather(): void
    {
        $service = app(WeatherService::class);

        $this->size = $service->getWidgetSize();
        $this->showFeelsLike = $service->shouldShowFeelsLike();
        $this->showHighLow = $service->shouldShowHighLow();
        $this->showPrecipitation = $service->shouldShowPrecipitation();
        $this->units = $service->getUnits();

        $weather = $service->getWeather();

        if ($weather !== null) {
            $this->weatherData = $weather->toArray();
        }
    }

    public function refreshWeather(): void
    {
        $this->loadWeather();
    }

    public function getWeatherProperty(): ?WeatherData
    {
        if ($this->weatherData === null) {
            return null;
        }

        return WeatherData::fromArray($this->weatherData);
    }
}; ?>

<div x-data="{
        init() {
            // Refresh weather every 15 minutes via Livewire
            setInterval(() => {
                $wire.refreshWeather();
            }, 15 * 60 * 1000);
        }
    }"
    class="text-center">

    @if($this->weather)
        @php $weather = $this->weather; @endphp

        {{-- Compact Size --}}
        @if($size === 'compact')
            <div class="flex items-center justify-center gap-2">
                <span class="text-4xl">{{ $weather->conditionEmoji }}</span>
                <span class="text-4xl font-bold text-white tabular-nums">
                    {{ $weather->getFormattedTemperature() }}
                </span>
            </div>
        @endif

        {{-- Medium Size --}}
        @if($size === 'medium')
            <div class="space-y-1">
                <div class="flex items-center justify-center gap-3">
                    <span class="text-4xl">{{ $weather->conditionEmoji }}</span>
                    <span class="text-4xl font-bold text-white tabular-nums">
                        {{ $weather->getFormattedTemperature() }}
                    </span>
                    <span class="text-xl text-slate-300">{{ $weather->conditionText }}</span>
                </div>
                <div class="text-lg text-slate-400 flex items-center justify-center gap-3">
                    @if($showFeelsLike)
                        <span>{{ $weather->getFormattedFeelsLike() }}</span>
                        <span class="text-slate-600">&middot;</span>
                    @endif
                    @if($showHighLow)
                        <span>{{ $weather->getFormattedHighLow() }}</span>
                    @endif
                </div>
            </div>
        @endif

        {{-- Large Size --}}
        @if($size === 'large')
            <div class="space-y-2">
                <div class="flex items-center justify-center gap-3">
                    <span class="text-5xl">{{ $weather->conditionEmoji }}</span>
                    <span class="text-5xl font-bold text-white tabular-nums">
                        {{ $weather->getFormattedTemperature() }}
                    </span>
                    <span class="text-2xl text-slate-300">{{ $weather->conditionText }}</span>
                </div>
                <div class="text-lg text-slate-400 flex items-center justify-center gap-3">
                    @if($showFeelsLike)
                        <span>{{ $weather->getFormattedFeelsLike() }}</span>
                        <span class="text-slate-600">&middot;</span>
                    @endif
                    @if($showHighLow)
                        <span>{{ $weather->getFormattedHighLow() }}</span>
                    @endif
                </div>

                {{-- Precipitation Alerts --}}
                @if($showPrecipitation && $weather->hasPrecipitationAlerts())
                    <div class="mt-2 space-y-1">
                        @foreach($weather->precipitationAlerts as $index => $alert)
                            <div wire:key="precip-alert-{{ $index }}"
                                 class="text-base px-3 py-1 rounded-lg inline-block
                                {{ $alert->type === 'snow' ? 'bg-blue-900/50 text-blue-200' : 'bg-cyan-900/50 text-cyan-200' }}">
                                {{ $alert->getDescription($units) }}
                            </div>
                        @endforeach
                    </div>
                @elseif($showPrecipitation && $weather->precipitationChance >= 30)
                    <div class="mt-2">
                        <span class="text-base px-3 py-1 rounded-lg bg-cyan-900/50 text-cyan-200">
                            {{ $weather->precipitationChance }}% chance of precipitation today
                        </span>
                    </div>
                @endif
            </div>
        @endif

    @else
        {{-- No weather data available --}}
        <div class="text-slate-500 text-lg">
            <span class="text-2xl">🌡️</span>
            <span class="ml-2">Weather unavailable</span>
        </div>
    @endif
</div>
