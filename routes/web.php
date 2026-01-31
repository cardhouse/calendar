<?php

use Illuminate\Support\Facades\Route;

// Main dashboard
Route::livewire('/', 'pages::dashboard')->name('dashboard');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Redirect /admin to /admin/children
    Route::redirect('/', '/admin/children');

    // Children management
    Route::livewire('/children', 'pages::admin.children')->name('children.index');
    Route::livewire('/children/{child}/routines', 'pages::admin.routines')->name('children.routines');

    // Routine templates (drag and drop to children)
    Route::livewire('/routine-templates', 'pages::admin.routine-templates')->name('routine-templates.index');

    // Departure times
    Route::livewire('/departures', 'pages::admin.departures')->name('departures.index');

    // Calendar events
    Route::livewire('/events', 'pages::admin.events')->name('events.index');

    // Event-specific routines
    Route::livewire('/event-routines', 'pages::admin.event-routines')->name('event-routines.index');

    // Weather settings
    Route::livewire('/weather', 'pages::admin.weather')->name('weather.index');
});
