@props([
    'title',
    'subtitle' => null,
    'actionUrl' => null,
    'actionLabel' => '+ Add New',
])
<x-app-layout>
    <div class="pb-8 sm:pb-12 min-w-0">
        <div class="mb-5 sm:mb-8 mt-1 sm:mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-2xl sm:text-3xl font-black text-gray-950 tracking-tight">{{ $title }}</h2>
                @if($subtitle)
                    <p class="mt-1 text-sm text-gray-500 font-medium">{{ $subtitle }}</p>
                @endif
            </div>
            @if($actionUrl)
                <a href="{{ $actionUrl }}" class="inline-flex items-center justify-center gap-2 self-start bg-indigo-600 text-white px-4 sm:px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-indigo-700 transition-all shrink-0">
                    {{ $actionLabel }}
                </a>
            @endif
        </div>
        @include('supply.partials.alerts')
        <div class="min-w-0">
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
