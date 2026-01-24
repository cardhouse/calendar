<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\DepartureTime;

class DepartureService
{
    /**
     * Get the next departure from any source (DepartureTime or CalendarEvent).
     *
     * @return array{timestamp: int, name: string, source: string}|null
     */
    public function getNextDeparture(): ?array
    {
        $candidates = [];

        // Get next recurring DepartureTime
        $departureTime = DepartureTime::getNextDeparture();
        if ($departureTime) {
            $nextOccurrence = $departureTime->getNextOccurrence();
            if ($nextOccurrence) {
                $candidates[] = [
                    'timestamp' => (int) $nextOccurrence->timestamp,
                    'name' => $departureTime->name,
                    'source' => 'departure_time',
                ];
            }
        }

        // Get upcoming CalendarEvents with departure_time set
        $eventsWithDeparture = CalendarEvent::query()
            ->whereNotNull('departure_time')
            ->where('departure_time', '>', now())
            ->orderBy('departure_time')
            ->limit(1)
            ->first();

        if ($eventsWithDeparture) {
            $candidates[] = [
                'timestamp' => (int) $eventsWithDeparture->departure_time->timestamp,
                'name' => $eventsWithDeparture->name,
                'source' => 'calendar_event',
            ];
        }

        if (empty($candidates)) {
            return null;
        }

        // Return the soonest departure
        usort($candidates, fn ($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        return $candidates[0];
    }
}
