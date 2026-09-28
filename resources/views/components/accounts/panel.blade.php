@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden']) }}>
    <div class="px-6 py-5 border-b border-gray-100 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-gray-900">{{ $title }}</h3>
            @if($subtitle)
                <p class="text-xs text-gray-500 mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
    {{ $slot }}
</div>
