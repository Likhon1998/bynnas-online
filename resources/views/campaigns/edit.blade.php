<x-app-layout>
    <div class="w-full min-w-0 pb-6 text-slate-700">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <div class="mr-auto">
                <h1 class="text-[17px] font-bold tracking-tight text-slate-900">Edit campaign</h1>
                <p class="text-[12px] text-slate-500">{{ $campaign->name }}</p>
            </div>
            <a href="{{ route('campaigns.show', $campaign) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50">Back to campaign</a>
        </div>

        <form method="POST" action="{{ route('campaigns.update', $campaign) }}">
            @csrf
            @method('PUT')
            @include('campaigns.partials.form')
        </form>
    </div>
</x-app-layout>
