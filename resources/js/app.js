// Import Alpine.js components for the dashboard
import clockDisplay from './alpine/clock-display.js';
import departureTimer from './alpine/departure-timer.js';
import eventCountdown from './alpine/event-countdown.js';

// Register Alpine components
// Alpine is included with Livewire 3, so we check if it's already available
// or wait for the alpine:init event
function registerAlpineComponents() {
    if (typeof Alpine !== 'undefined') {
        Alpine.data('clockDisplay', clockDisplay);
        Alpine.data('departureTimer', departureTimer);
        Alpine.data('eventCountdown', eventCountdown);
    }
}

// If Alpine is already loaded (Livewire auto-injection), register immediately
if (typeof Alpine !== 'undefined') {
    registerAlpineComponents();
} else {
    // Otherwise, wait for alpine:init
    document.addEventListener('alpine:init', registerAlpineComponents);
}

