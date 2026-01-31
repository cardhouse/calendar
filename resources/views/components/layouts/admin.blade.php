<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin - Family Morning Dashboard' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-white antialiased">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-slate-800 shadow">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold">Dashboard Admin</h1>
                <a href="{{ route('dashboard') }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors">
                    View Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar + Content -->
    <div class="max-w-7xl mx-auto px-4 py-8 flex flex-col md:flex-row gap-8">
        <aside class="w-full md:w-64 shrink-0">
            <nav class="bg-white dark:bg-slate-800 rounded-lg shadow p-4 space-y-2">
                <a href="{{ route('admin.children.index') }}"
                   class="block px-4 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.children.*') ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    Children
                </a>
                <a href="{{ route('admin.routine-templates.index') }}"
                   class="block px-4 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.routine-templates.*') ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    Routine Templates
                </a>
                <a href="{{ route('admin.departures.index') }}" 
                   class="block px-4 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.departures.*') ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    Departure Times
                </a>
                <a href="{{ route('admin.events.index') }}" 
                   class="block px-4 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.events.*') ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    Calendar Events
                </a>
                <a href="{{ route('admin.event-routines.index') }}"
                   class="block px-4 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.event-routines.*') ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    Event Routines
                </a>
                <a href="{{ route('admin.weather.index') }}"
                   class="block px-4 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.weather.*') ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    Weather
                </a>
            </nav>
        </aside>
        <main class="flex-1">
            {{ $slot }}
        </main>
    </div>
</body>
</html>

