<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-canvas font-sans">
        <div x-data="{ sidebar: false }" class="min-h-screen lg:flex">
            {{-- Mobile backdrop --}}
            <div x-show="sidebar" x-cloak @click="sidebar = false"
                 class="fixed inset-0 z-30 bg-ink/20 lg:hidden"></div>

            <x-app.sidebar />

            <div class="flex min-w-0 flex-1 flex-col">
                <x-app.topbar />

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-app.toasts />

        @livewireScripts
    </body>
</html>
