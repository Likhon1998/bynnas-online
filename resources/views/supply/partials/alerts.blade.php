@if (session('success'))
    <div data-admin-flash-banner class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl font-semibold text-sm">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div data-admin-flash-banner class="mb-6 p-4 bg-red-50 border border-red-100 text-red-800 rounded-xl font-semibold text-sm">
        {{ session('error') }}
    </div>
@endif
@if ($errors->any())
    <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-800 rounded-xl text-sm">
        <p class="font-semibold mb-1">Please fix the following:</p>
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
