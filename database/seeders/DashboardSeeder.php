<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\Child;
use App\Models\DepartureTime;
use App\Models\RoutineItem;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        // Create children with routines
        $routines = [
            'Emma' => ['Brush teeth', 'Wash face', 'Get dressed', 'Make bed', 'Eat breakfast', 'Pack backpack'],
            'Jack' => ['Brush teeth', 'Get dressed', 'Eat breakfast', 'Feed the dog', 'Pack backpack', 'Put on shoes'],
        ];

        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
        $colorIndex = 0;

        foreach ($routines as $childName => $items) {
            $child = Child::create([
                'name' => $childName,
                'avatar_color' => $colors[$colorIndex % count($colors)],
                'display_order' => $colorIndex,
            ]);

            foreach ($items as $order => $itemName) {
                RoutineItem::create([
                    'child_id' => $child->id,
                    'name' => $itemName,
                    'display_order' => $order,
                ]);
            }

            $colorIndex++;
        }

        // Create departure times
        DepartureTime::create([
            'name' => 'Bus arrives',
            'departure_time' => '07:45:00',
            'applicable_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true,
            'display_order' => 0,
        ]);

        // Create upcoming events
        CalendarEvent::create([
            'name' => "Emma's Birthday Party",
            'starts_at' => now()->addDays(3)->setTime(14, 0),
            'category' => 'birthday',
            'color' => '#EC4899',
        ]);

        CalendarEvent::create([
            'name' => 'Soccer Tournament',
            'starts_at' => now()->addDays(7)->setTime(9, 0),
            'category' => 'sports',
            'color' => '#10B981',
        ]);

        CalendarEvent::create([
            'name' => 'Parent-Teacher Conference',
            'starts_at' => now()->addDays(14)->setTime(16, 30),
            'category' => 'school',
            'color' => '#3B82F6',
        ]);
    }
}

