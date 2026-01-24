# UI Components & Design Guidelines

## Overview

This document defines the UI/UX guidelines, component specifications, and design system for the Family Morning Dashboard. The design prioritizes **glanceability**, **accessibility**, and **child-friendliness**.

---

## Design Principles

### 1. Glanceability

Information should be readable from 10+ feet away on a TV/monitor display.

- **Large typography** for critical information
- **High contrast** between text and background
- **Clear visual hierarchy** with distinct sections
- **Minimal text** - use icons and colors to convey meaning

### 2. Child-Friendly Interaction

Children should be able to interact without adult assistance.

- **Large touch targets** (minimum 48px, prefer 64px+)
- **Clear visual feedback** on interactions
- **Satisfying completion animations**
- **Forgiving gestures** - easy to tap, hard to make mistakes

### 3. Calm Design

The morning is stressful enough - the UI should be calming.

- **Soft color palette** with purposeful accent colors
- **Smooth animations** (not jarring or distracting)
- **Progressive urgency** - calm by default, urgent only when needed
- **No unnecessary movement** - static unless conveying information

---

## Color System

### Base Palette

```css
/* Background colors */
--color-bg-primary: #0F172A;     /* Dark slate - main background */
--color-bg-secondary: #1E293B;   /* Lighter slate - card backgrounds */
--color-bg-tertiary: #334155;    /* Even lighter - hover states */

/* Text colors */
--color-text-primary: #F8FAFC;   /* Near white - primary text */
--color-text-secondary: #CBD5E1; /* Light gray - secondary text */
--color-text-muted: #64748B;     /* Muted - less important info */

/* Accent colors */
--color-accent-blue: #3B82F6;    /* Primary accent */
--color-accent-green: #10B981;   /* Success/complete */
--color-accent-yellow: #F59E0B;  /* Warning/approaching */
--color-accent-orange: #F97316;  /* Urgent */
--color-accent-red: #EF4444;     /* Critical */
--color-accent-purple: #8B5CF6;  /* Events */
--color-accent-pink: #EC4899;    /* Birthdays */
```

### Timer State Colors

| State | Remaining | Background | Text |
|-------|-----------|------------|------|
| Normal | > 30 min | `bg-slate-800` | `text-white` |
| Approaching | 15-30 min | `bg-yellow-900/50` | `text-yellow-200` |
| Urgent | 5-15 min | `bg-orange-900/50` | `text-orange-200` |
| Critical | < 5 min | `bg-red-900/50` | `text-red-200` |

### Child Avatar Colors

Provide distinct, easily distinguishable colors:

```php
$avatarColors = [
    '#3B82F6', // Blue
    '#10B981', // Emerald
    '#F59E0B', // Amber
    '#EF4444', // Red
    '#8B5CF6', // Violet
    '#EC4899', // Pink
    '#06B6D4', // Cyan
    '#84CC16', // Lime
];
```

---

## Typography

### Font Stack

Use system fonts for performance and native feel:

```css
font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont,
             "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
```

### Size Scale

| Element | Size | Tailwind Class |
|---------|------|----------------|
| Current Time | 6rem+ | `text-7xl` or `text-8xl` |
| Timer Countdown | 4rem+ | `text-5xl` or `text-6xl` |
| Section Headers | 2rem | `text-3xl` |
| Child Name | 1.5rem | `text-2xl` |
| Checklist Item | 1.25rem | `text-xl` |
| Event Name | 1.25rem | `text-xl` |
| Secondary Info | 1rem | `text-base` |

### Font Weights

- **Time displays**: `font-bold` (700)
- **Names/Headers**: `font-semibold` (600)
- **Body text**: `font-medium` (500) or `font-normal` (400)

---

## Layout

### Dashboard Grid

The main dashboard uses a responsive grid layout:

```blade
<div class="min-h-screen bg-slate-900 p-6">
    <!-- Header -->
    <header class="mb-8 text-center">
        <!-- Time and date display -->
    </header>

    <!-- Main content grid -->
    <main class="grid grid-cols-12 gap-6">
        <!-- Checklists: span 8 columns -->
        <section class="col-span-8">
            <div class="grid grid-cols-2 gap-6">
                <!-- Child checklist cards -->
            </div>
        </section>

        <!-- Departure timer: span 4 columns -->
        <aside class="col-span-4">
            <!-- Countdown timer -->
        </aside>
    </main>

    <!-- Upcoming events footer -->
    <footer class="mt-8">
        <div class="grid grid-cols-3 gap-6">
            <!-- Event cards -->
        </div>
    </footer>
</div>
```

### Responsive Considerations

While primary use is on large displays, support smaller screens:

```blade
<main class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <section class="lg:col-span-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Child checklists -->
        </div>
    </section>
    <aside class="lg:col-span-4">
        <!-- Timer -->
    </aside>
</main>
```

---

## Component Specifications

### 1. Header / Clock Display (Alpine.js - NO server polling)

**IMPORTANT**: Use Alpine.js for time display. NEVER use `wire:poll` for clocks.

```blade
<header class="text-center mb-8"
        x-data="clockDisplay()"
        x-init="startClock()">
    <time class="text-8xl font-bold text-white tabular-nums">
        <span x-text="timeDisplay"></span>
        <span class="text-4xl text-slate-400" x-text="amPm"></span>
    </time>
    <p class="text-2xl text-slate-400 mt-2" x-text="dateDisplay"></p>
</header>
```

**Alpine.js Component:**

```javascript
// resources/js/alpine/clock-display.js
Alpine.data('clockDisplay', () => ({
    timeDisplay: '',
    amPm: '',
    dateDisplay: '',
    interval: null,

    startClock() {
        this.updateClock();
        this.interval = setInterval(() => this.updateClock(), 1000);
    },

    updateClock() {
        const now = new Date();
        this.timeDisplay = now.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        }).replace(/\s?(AM|PM)/, '');
        this.amPm = now.toLocaleTimeString('en-US', { hour12: true }).slice(-2);
        this.dateDisplay = now.toLocaleDateString('en-US', {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
    },

    destroy() {
        if (this.interval) clearInterval(this.interval);
    }
}));
```

**Key Points:**
- Use `tabular-nums` for consistent width as time changes
- AM/PM smaller than hour:minute
- Date in secondary color
- **Alpine.js handles all updates - zero server requests**

### 2. Child Checklist Card

```blade
<div class="bg-slate-800 rounded-2xl p-6 border-l-4"
     style="border-color: {{ $child->avatar_color }}">
    <!-- Header with name and progress -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-semibold text-white flex items-center gap-3">
            <span class="w-4 h-4 rounded-full"
                  style="background: {{ $child->avatar_color }}"></span>
            {{ $child->name }}
        </h2>
        <span class="text-lg text-slate-400">
            {{ $completedCount }}/{{ $totalCount }}
        </span>
    </div>

    <!-- Progress bar -->
    <div class="h-2 bg-slate-700 rounded-full mb-4 overflow-hidden">
        <div class="h-full bg-emerald-500 transition-all duration-300"
             style="width: {{ $progressPercent }}%"></div>
    </div>

    <!-- Checklist items -->
    <ul class="space-y-3">
        @foreach($child->routineItems as $item)
            <li wire:key="item-{{ $item->id }}">
                <button wire:click="toggleItem({{ $item->id }})"
                        class="w-full flex items-center gap-4 p-3 rounded-xl
                               transition-all duration-200
                               {{ $item->isCompletedFor()
                                  ? 'bg-emerald-900/30 text-emerald-300'
                                  : 'bg-slate-700/50 text-white hover:bg-slate-700' }}">
                    <!-- Checkbox indicator -->
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center
                                 {{ $item->isCompletedFor()
                                    ? 'bg-emerald-500'
                                    : 'bg-slate-600 border-2 border-slate-500' }}">
                        @if($item->isCompletedFor())
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        @endif
                    </span>
                    <!-- Item name -->
                    <span class="text-xl {{ $item->isCompletedFor() ? 'line-through opacity-75' : '' }}">
                        {{ $item->name }}
                    </span>
                </button>
            </li>
        @endforeach
    </ul>
</div>
```

**Key Points:**
- Left border color indicates child
- Large touch targets (entire row is clickable)
- Clear visual distinction between complete/incomplete
- Smooth transitions on state change

### 3. Departure Countdown Timer (Alpine.js - NO server polling)

**IMPORTANT**: Use Alpine.js for countdown. NEVER use `wire:poll` for timers.

Livewire passes the departure timestamp once on mount. Alpine.js handles all countdown logic.

```blade
{{-- Livewire passes $departureTimestamp (Unix timestamp) and $departureName --}}
@if($departureTimestamp)
    <div x-data="departureTimer({{ $departureTimestamp }})"
         x-init="startTimer()"
         class="rounded-2xl p-8 transition-colors duration-500"
         :class="urgencyClass">
        <h2 class="text-xl text-center opacity-75 mb-4">
            {{ $departureName }}
        </h2>

        <div class="text-center">
            <div class="text-6xl font-bold tabular-nums" x-text="display"></div>
            <p class="text-lg opacity-75 mt-2" x-text="label"></p>
        </div>
    </div>
@else
    <div class="rounded-2xl p-8 bg-slate-800">
        <p class="text-center text-xl text-slate-400">
            No departure scheduled
        </p>
    </div>
@endif
```

**Alpine.js Component:**

```javascript
// resources/js/alpine/departure-timer.js
Alpine.data('departureTimer', (targetTimestamp) => ({
    targetTime: new Date(targetTimestamp * 1000),
    secondsRemaining: 0,
    display: '',
    label: '',
    urgencyClass: 'bg-slate-800 text-white',
    interval: null,

    startTimer() {
        this.updateCountdown();
        this.interval = setInterval(() => this.updateCountdown(), 1000);
    },

    updateCountdown() {
        const now = new Date();
        this.secondsRemaining = Math.max(0, Math.floor((this.targetTime - now) / 1000));

        const h = Math.floor(this.secondsRemaining / 3600);
        const m = Math.floor((this.secondsRemaining % 3600) / 60);
        const s = this.secondsRemaining % 60;

        if (this.secondsRemaining <= 0) {
            this.display = 'Time to go!';
            this.label = '';
        } else if (h > 0) {
            this.display = `${h}:${String(m).padStart(2, '0')}`;
            this.label = 'hours remaining';
        } else {
            this.display = `${m}:${String(s).padStart(2, '0')}`;
            this.label = 'minutes remaining';
        }

        this.urgencyClass = this.getUrgencyClass();
    },

    getUrgencyClass() {
        if (this.secondsRemaining <= 0) return 'bg-slate-700 text-slate-400';
        if (this.secondsRemaining < 300) return 'bg-red-900/50 text-red-200 animate-pulse';
        if (this.secondsRemaining < 900) return 'bg-orange-900/50 text-orange-200';
        if (this.secondsRemaining < 1800) return 'bg-yellow-900/50 text-yellow-200';
        return 'bg-slate-800 text-white';
    },

    destroy() {
        if (this.interval) clearInterval(this.interval);
    }
}));
```

**Key Points:**
- Background color changes based on urgency (calculated by Alpine.js)
- Pulse animation for critical state
- Large, readable countdown numbers
- **Zero server requests** - all calculation happens in browser

### 4. Event Countdown Card (Alpine.js - NO server polling)

**IMPORTANT**: Use Alpine.js for event countdowns. Livewire passes event data once.

```blade
{{-- Livewire passes $eventData array with timestamps --}}
@foreach($eventData as $event)
    <div x-data="eventCountdown({{ $event['timestamp'] }})"
         x-init="startTimer()"
         class="bg-slate-800 rounded-xl p-5 border-l-4"
         style="border-color: {{ $event['color'] }}">
        <h3 class="text-xl font-semibold text-white mb-2">
            {{ $event['name'] }}
        </h3>
        <p class="text-3xl font-bold text-slate-200 mb-1" x-text="countdown"></p>
        <p class="text-sm text-slate-400">
            {{ \Carbon\Carbon::createFromTimestamp($event['timestamp'])->format('M j, g:i A') }}
        </p>
    </div>
@endforeach
```

**Alpine.js Component:**

```javascript
// resources/js/alpine/event-countdown.js
Alpine.data('eventCountdown', (targetTimestamp) => ({
    targetTime: new Date(targetTimestamp * 1000),
    countdown: '',
    interval: null,

    startTimer() {
        this.updateCountdown();
        // Events update every minute (not every second - less critical)
        this.interval = setInterval(() => this.updateCountdown(), 60000);
    },

    updateCountdown() {
        const now = new Date();
        const diff = this.targetTime - now;

        if (diff <= 0) {
            this.countdown = 'Now';
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

        if (days > 7) {
            this.countdown = `${days} days`;
        } else if (days >= 2) {
            this.countdown = `${days} days, ${hours} hours`;
        } else if (days === 1) {
            this.countdown = `1 day, ${hours} hours`;
        } else if (hours > 0) {
            this.countdown = `${hours} hours, ${minutes} min`;
        } else {
            this.countdown = `${minutes} minutes`;
        }
    },

    destroy() {
        if (this.interval) clearInterval(this.interval);
    }
}));
```

**Key Points:**
- Colored left border indicates category
- Prominent countdown display
- Full date/time in secondary text
- **Updates every 60 seconds** (events don't need per-second precision)
- **Zero server requests** - all calculation happens in browser

### 5. Completion Celebration

When all items are complete, show a subtle celebration:

```blade
@if($allComplete)
    <div class="text-center py-4 bg-emerald-900/30 rounded-xl">
        <p class="text-2xl text-emerald-300 font-semibold">
            All done! Great job, {{ $child->name }}!
        </p>
    </div>
@endif
```

---

## Animations

### Transition Utilities

Use Tailwind's built-in transitions:

```blade
<!-- Smooth background color changes -->
<div class="transition-colors duration-300">

<!-- Smooth all property changes -->
<div class="transition-all duration-200">

<!-- Scale on hover (for buttons) -->
<button class="hover:scale-105 active:scale-95 transition-transform">
```

### Completion Animation

CSS for checkmark animation:

```css
@keyframes checkmark {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.animate-checkmark {
    animation: checkmark 0.3s ease-out;
}
```

### Pulse for Urgency

Use Tailwind's `animate-pulse` for critical states:

```blade
<div class="{{ $isCritical ? 'animate-pulse' : '' }}">
```

---

## Admin Interface

### Admin Layout

The admin interface uses a simpler, form-focused design:

```blade
<div class="min-h-screen bg-slate-100 dark:bg-slate-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-slate-800 shadow">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold">Dashboard Admin</h1>
                <a href="/" class="text-blue-600 hover:text-blue-800">
                    View Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar + Content -->
    <div class="max-w-7xl mx-auto px-4 py-8 flex gap-8">
        <aside class="w-64 shrink-0">
            <!-- Admin navigation links -->
        </aside>
        <main class="flex-1">
            <!-- Page content -->
        </main>
    </div>
</div>
```

### Form Components

Use standard form styling with clear labels:

```blade
<!-- Text Input -->
<div class="mb-4">
    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
        Child Name
    </label>
    <input type="text"
           id="name"
           wire:model="name"
           class="w-full rounded-lg border-slate-300 dark:border-slate-600
                  dark:bg-slate-700 shadow-sm
                  focus:border-blue-500 focus:ring-blue-500">
    @error('name')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<!-- Color Picker -->
<div class="mb-4">
    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
        Avatar Color
    </label>
    <input type="color"
           wire:model="avatarColor"
           class="h-10 w-20 rounded cursor-pointer">
</div>

<!-- Time Input -->
<div class="mb-4">
    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
        Departure Time
    </label>
    <input type="time"
           wire:model="departureTime"
           class="rounded-lg border-slate-300 dark:border-slate-600
                  dark:bg-slate-700 shadow-sm">
</div>
```

### List Management

Sortable lists for reordering:

```blade
<ul class="space-y-2">
    @foreach($items as $index => $item)
        <li wire:key="item-{{ $item->id }}"
            class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800
                   rounded-lg shadow-sm">
            <!-- Drag handle -->
            <button class="cursor-move text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 8h16M4 16h16"/>
                </svg>
            </button>

            <!-- Item content -->
            <span class="flex-1">{{ $item->name }}</span>

            <!-- Actions -->
            <button wire:click="edit({{ $item->id }})" class="text-blue-600 hover:text-blue-800">
                Edit
            </button>
            <button wire:click="confirmDelete({{ $item->id }})" class="text-red-600 hover:text-red-800">
                Delete
            </button>
        </li>
    @endforeach
</ul>
```

---

## Accessibility

### Focus States

Ensure visible focus indicators:

```blade
<button class="focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
```

### Color Contrast

- Primary text on dark backgrounds: minimum 7:1 ratio
- Secondary text: minimum 4.5:1 ratio
- Use color + another indicator (icon, text) for status

### Screen Reader Support

```blade
<!-- Announce checklist completion -->
<button wire:click="toggleItem({{ $item->id }})"
        aria-pressed="{{ $item->isCompletedFor() ? 'true' : 'false' }}"
        aria-label="{{ $item->name }} - {{ $item->isCompletedFor() ? 'completed' : 'not completed' }}">
```

---

## Dark Mode

The dashboard uses dark mode by default (better for morning viewing in low light):

```blade
<!-- In app layout -->
<html class="dark">
```

Admin interface supports both modes:

```blade
<!-- Toggle based on system preference or user setting -->
<html class="dark:bg-slate-900">
```

---

## Icons

Use Heroicons (included with Tailwind) for consistency:

```blade
<!-- Checkmark -->
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
</svg>

<!-- Clock -->
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
</svg>

<!-- Calendar -->
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
</svg>
```

---

## Related Documentation

- [PROJECT.md](./PROJECT.md) - Project overview
- [ARCHITECTURE.md](./ARCHITECTURE.md) - Technical architecture
- [FEATURES.md](./FEATURES.md) - Feature specifications
- [DATA-MODELS.md](./DATA-MODELS.md) - Database schema
