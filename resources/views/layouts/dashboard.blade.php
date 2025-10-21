<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="darkMode" :class="{ 'dark': dark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Corporate LMS') }} - @yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="{ sidebarOpen: true }">
        <!-- Sidebar -->
        @include('layouts.sidebar')

        <!-- Main Content Area -->
        <div :class="sidebarOpen ? 'lg:ml-64' : 'lg:ml-20'" class="transition-all duration-300">
            <!-- Top Navigation -->
            @include('layouts.header')

            <!-- Page Content -->
            <main class="p-6 mt-16">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed top-20 right-6 z-50 space-y-3 max-w-md">
        @if (session('success'))
            <x-toast type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-toast type="error" :message="session('error')" />
        @endif

        @if (session('warning'))
            <x-toast type="warning" :message="session('warning')" />
        @endif

        @if (session('info'))
            <x-toast type="info" :message="session('info')" />
        @endif
    </div>

    <!-- AI Chatbot Widget -->
    <x-chatbot-widget />

    @livewireScripts
    @stack('scripts')
</body>
</html>
