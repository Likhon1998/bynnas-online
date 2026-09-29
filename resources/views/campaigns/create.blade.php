<x-app-layout>
    <div class="w-full min-w-0 pb-6 text-slate-700">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <div class="mr-auto">
                <h1 class="text-[17px] font-bold tracking-tight text-slate-900">New campaign</h1>
                <p class="text-[12px] text-slate-500">Create a trackable link for a post, ad, story or influencer.</p>
            </div>
            <a href="{{ route('campaigns.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50">Back to campaigns</a>
        </div>

        <form method="POST" action="{{ route('campaigns.store') }}">
            @csrf
            @include('campaigns.partials.form')
        </form>
    </div>
</x-app-layout>
