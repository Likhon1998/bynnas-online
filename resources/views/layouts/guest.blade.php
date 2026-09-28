<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Maks Gadget') }}</title>
    @include('partials.favicon')

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50 md:flex-row md:items-stretch">
        
        <div class="hidden md:flex md:w-1/2 bg-slate-900 text-white flex-col justify-center items-start p-12 lg:p-24">
            <div class="flex items-center gap-3 mb-6">
                <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h1 class="text-4xl font-bold tracking-tight">{{ config('app.name', 'Maks Gadget') }}</h1>
            </div>
            <p class="text-slate-400 text-lg max-w-md">Absolute transparency and seamless inventory management for modern retail.</p>
        </div>

        <div class="w-full md:w-1/2 flex flex-col justify-center items-center p-6 sm:p-12">
            <div class="md:hidden flex items-center gap-2 mb-8">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h1 class="text-2xl font-bold text-slate-900">{{ config('app.name', 'Maks Gadget') }}</h1>
            </div>

            <div class="w-full sm:max-w-md bg-white px-6 py-8 shadow-xl sm:rounded-lg border border-gray-100">
                {{ $slot }}
            </div>
            <div class="mt-6">
                @include('partials.powered-by')
            </div>
        </div>

    </div>
</body>
</html>