<?php

declare(strict_types=1);

namespace App\Services\Weather;

use Illuminate\Support\Carbon;

/**
 * Data transfer object for weather information.
 */
final readonly class WeatherData
{
    /**
     * @param  list<PrecipitationAlert>  $precipitationAlerts
     */
    public function __construct(
        public float $temperature,
        public float $feelsLike,
        public int $weatherCode,
        public string $conditionText,
        public string $conditionEmoji,
        public float $high,
        public float $low,
        public int $precipitationChance,
        public array $precipitationAlerts,
        public Carbon $fetchedAt,
        public string $units,
    ) {}

    /**
     * Get the temperature unit symbol.
     */
    public function getUnitSymbol(): string
    {
        return $this->units === 'celsius' ? "\u{00B0}C" : "\u{00B0}F";
    }

    /**
     * Get formatted temperature string.
     */
    public function getFormattedTemperature(): string
    {
        return round($this->temperature) . "\u{00B0}";
    }

    /**
     * Get formatted feels like string.
     */
    public function getFormattedFeelsLike(): string
    {
        return 'Feels like ' . round($this->feelsLike) . "\u{00B0}";
    }

    /**
     * Get formatted high/low string.
     */
    public function getFormattedHighLow(): string
    {
        return sprintf('H: %d° L: %d°', round($this->high), round($this->low));
    }

    /**
     * Check if weather data is stale (older than threshold).
     */
    public function isStale(int $minutes = 30): bool
    {
        return $this->fetchedAt->diffInMinutes(now()) > $minutes;
    }

    /**
     * Check if there are any precipitation alerts.
     */
    public function hasPrecipitationAlerts(): bool
    {
        return count($this->precipitationAlerts) > 0;
    }

    /**
     * Get the primary (highest probability) precipitation alert.
     */
    public function getPrimaryAlert(): ?PrecipitationAlert
    {
        if (empty($this->precipitationAlerts)) {
            return null;
        }

        return collect($this->precipitationAlerts)
            ->sortByDesc('probability')
            ->first();
    }

    /**
     * Create from array (for deserialization from cache).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            temperature: (float) $data['temperature'],
            feelsLike: (float) $data['feelsLike'],
            weatherCode: (int) $data['weatherCode'],
            conditionText: (string) $data['conditionText'],
            conditionEmoji: (string) $data['conditionEmoji'],
            high: (float) $data['high'],
            low: (float) $data['low'],
            precipitationChance: (int) $data['precipitationChance'],
            precipitationAlerts: array_map(
                fn (array $alert) => PrecipitationAlert::fromArray($alert),
                $data['precipitationAlerts'] ?? []
            ),
            fetchedAt: Carbon::parse($data['fetchedAt']),
            units: (string) $data['units'],
        );
    }

    /**
     * Convert to array (for serialization to cache).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'temperature' => $this->temperature,
            'feelsLike' => $this->feelsLike,
            'weatherCode' => $this->weatherCode,
            'conditionText' => $this->conditionText,
            'conditionEmoji' => $this->conditionEmoji,
            'high' => $this->high,
            'low' => $this->low,
            'precipitationChance' => $this->precipitationChance,
            'precipitationAlerts' => array_map(
                fn (PrecipitationAlert $alert) => $alert->toArray(),
                $this->precipitationAlerts
            ),
            'fetchedAt' => $this->fetchedAt->toIso8601String(),
            'units' => $this->units,
        ];
    }

    /**
     * Map WMO weather code to condition text.
     */
    public static function getConditionText(int $code): string
    {
        return match ($code) {
            0 => 'Clear',
            1 => 'Mostly Clear',
            2 => 'Partly Cloudy',
            3 => 'Overcast',
            45, 48 => 'Foggy',
            51, 53, 55 => 'Drizzle',
            56, 57 => 'Freezing Drizzle',
            61, 63, 65 => 'Rain',
            66, 67 => 'Freezing Rain',
            71, 73, 75 => 'Snow',
            77 => 'Snow Grains',
            80, 81, 82 => 'Rain Showers',
            85, 86 => 'Snow Showers',
            95 => 'Thunderstorm',
            96, 99 => 'Thunderstorm with Hail',
            default => 'Unknown',
        };
    }

    /**
     * Map WMO weather code to emoji.
     */
    public static function getConditionEmoji(int $code): string
    {
        return match ($code) {
            0 => "\u{2600}\u{FE0F}", // sun
            1 => "\u{1F324}\u{FE0F}", // sun behind small cloud
            2 => "\u{26C5}", // sun behind cloud
            3 => "\u{2601}\u{FE0F}", // cloud
            45, 48 => "\u{1F32B}\u{FE0F}", // fog
            51, 53, 55, 56, 57 => "\u{1F326}\u{FE0F}", // sun behind rain cloud
            61, 63, 65, 66, 67 => "\u{1F327}\u{FE0F}", // cloud with rain
            71, 73, 75, 77 => "\u{1F328}\u{FE0F}", // cloud with snow
            80, 81, 82 => "\u{1F327}\u{FE0F}", // cloud with rain
            85, 86 => "\u{1F328}\u{FE0F}", // cloud with snow
            95, 96, 99 => "\u{26C8}\u{FE0F}", // cloud with lightning and rain
            default => "\u{2601}\u{FE0F}", // cloud
        };
    }
}
