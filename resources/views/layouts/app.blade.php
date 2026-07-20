<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'HayagSync Staff Portal')</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <div x-data="{ open: true }" class="flex">
            {{-- Sidebar --}}
            <aside x-show="open"
                class="fixed top-0 left-0 w-64 bg-gray-800 text-white h-screen flex flex-col">
                @include('layouts.sidebar')
            </aside>

            {{-- Main Content --}}
            <main class="flex-1 p-6 ml-64">
                {{-- Mobile toggle button --}}
                <button @click="open = !open" class="p-2 bg-gray-200 rounded mb-4 lg:hidden">
                    Toggle Menu
                </button>

                @yield('content')
            </main>
        </div>
    </body>

</html>
