@props(['title', 'subtitle' => null, 'actionUrl' => null, 'actionLabel' => '+ Add New', 'previewUrl' => null])

<x-app-layout>
    <div class="pb-8 sm:pb-12 min-w-0">
        <div class="mb-5 sm:mb-6 mt-1 sm:mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 mb-1">CMS · Website</p>
                <h2 class="text-2xl sm:text-3xl font-black text-gray-950 tracking-tight">{{ $title }}</h2>
                @if($subtitle)
                    <p class="mt-1 text-sm text-gray-500 font-medium">{{ $subtitle }}</p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if($previewUrl)
                    <a href="{{ $previewUrl }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        View on website
                    </a>
                @endif
                @if($actionUrl)
                    <a href="{{ $actionUrl }}" class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 sm:px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-indigo-700 transition-all">
                        {{ $actionLabel }}
                    </a>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div data-admin-flash-banner class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl font-semibold text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div data-admin-flash-banner class="mb-6 p-4 bg-red-50 border border-red-100 text-red-800 rounded-xl font-semibold text-sm">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-800 rounded-xl text-sm">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="min-w-0">
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
