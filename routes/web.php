<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Main dashboard
Volt::route('/', 'dashboard')->name('dashboard');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Redirect /admin to /admin/children
    Route::redirect('/', '/admin/children');

    // Children management
    Volt::route('/children', 'admin.children')->name('children.index');
    Volt::route('/children/{child}/routines', 'admin.routines')->name('children.routines');

    // Departure times
    Volt::route('/departures', 'admin.departures')->name('departures.index');

    // Calendar events
    Volt::route('/events', 'admin.events')->name('events.index');

    // Event-specific routines
    Volt::route('/event-routines', 'admin.event-routines')->name('event-routines.index');

    // Weather settings
    Volt::route('/weather', 'admin.weather')->name('weather.index');
});
