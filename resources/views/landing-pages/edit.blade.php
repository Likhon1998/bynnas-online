<x-app-layout>
    <div class="w-full min-w-0 pb-6 text-slate-700">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <div class="mr-auto min-w-0">
                <div class="flex items-center gap-2">
                    <h1 class="text-[17px] font-bold tracking-tight text-slate-900">{{ $page->title }}</h1>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 {{ $page->isPublished() ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">{{ \App\Models\LandingPage::STATUSES[$page->status] ?? $page->status }}</span>
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-[12px]" x-data="{ copied: false }">
                    <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener" class="font-mono text-indigo-600 hover:underline">{{ $page->publicUrl() }}</a>
                    <button type="button" class="rounded-md border border-slate-200 px-2 py-0.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50"
                            @click="navigator.clipboard.writeText(@js($page->publicUrl())); copied = true; setTimeout(() => copied = false, 1500)">
                        <span x-text="copied ? 'Copied' : 'Copy'"></span>
                    </button>
                    @if($page->campaign)
                        <span class="text-slate-500">· Campaign: <a href="{{ route('campaigns.show', $page->campaign) }}" class="font-semibold text-slate-700 hover:underline">{{ $page->campaign->name }}</a></span>
                    @endif
                </div>
            </div>
            <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50">{{ $page->isPublished() ? 'View page' : 'Preview' }}</a>
            <a href="{{ route('landing-pages.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50">All pages</a>
            <form method="POST" action="{{ route('landing-pages.destroy', $page) }}" onsubmit="return confirm('Delete this landing page? Orders placed from it are kept.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-[12px] font-medium text-rose-600 hover:bg-rose-50">Delete</button>
            </form>
        </div>

        <form method="POST" action="{{ route('landing-pages.update', $page) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('landing-pages.partials.form')
        </form>
    </div>
</x-app-layout>
