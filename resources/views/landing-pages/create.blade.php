<x-app-layout>
    <div class="w-full min-w-0 pb-6 text-slate-700">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <div class="mr-auto">
                <h1 class="text-[17px] font-bold tracking-tight text-slate-900">New landing page</h1>
                <p class="text-[12px] text-slate-500">A focused, mobile-first offer page with a one-step guest order form.</p>
            </div>
            <a href="{{ route('landing-pages.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50">Back to landing pages</a>
        </div>

        <form method="POST" action="{{ route('landing-pages.store') }}" enctype="multipart/form-data">
            @csrf
            @include('landing-pages.partials.form')
        </form>
    </div>
</x-app-layout>
