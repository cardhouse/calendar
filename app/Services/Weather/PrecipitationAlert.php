<?php

declare(strict_types=1);

namespace App\Services\Weather;

/**
 * Value object representing a precipitation alert for a time period.
 */
final readonly class PrecipitationAlert
{
    /**
     * @param  string  $type  'rain', 'snow', or 'mixed'
     * @param  string  $timing  'early', 'mid-day', or 'late'
     * @param  int  $probability  0-100 percent
     * @param  float  $amount  Amount in mm or inches depending on units
     */
    public function __construct(
        public string $type,
        public string $timing,
        public int $probability,
        public float $amount,
    ) {}

    /**
     * Get a human-readable description of the alert.
     */
    public function getDescription(string $units = 'fahrenheit'): string
    {
        $unitLabel = $units === 'celsius' ? 'mm' : '"';
        $amountFormatted = $units === 'celsius'
            ? number_format($this->amount, 1)
            : number_format($this->amount / 25.4, 1); // Convert mm to inches

        $typeEmoji = match ($this->type) {
            'snow' => "\u{2744}\u{FE0F}", // snowflake
            'mixed' => "\u{1F327}\u{FE0F}", // cloud with rain
            default => "\u{1F327}\u{FE0F}", // cloud with rain
        };

        return sprintf(
            '%s %d%% chance %s %s (~%s%s)',
            $typeEmoji,
            $this->probability,
            $this->type,
            $this->timing,
            $amountFormatted,
            $unitLabel
        );
    }

    /**
     * Create from array (for deserialization from cache).
     *
     * @param  array{type: string, timing: string, probability: int, amount: float}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            timing: $data['timing'],
            probability: $data['probability'],
            amount: $data['amount'],
        );
    }

    /**
     * Convert to array (for serialization to cache).
     *
     * @return array{type: string, timing: string, probability: int, amount: float}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'timing' => $this->timing,
            'probability' => $this->probability,
            'amount' => $this->amount,
        ];
    }
}
