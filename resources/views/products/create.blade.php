<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ __('Add New Product') }}</h2>
                <p class="text-sm text-slate-500 mt-0.5">Everything you enter here shows on the online store and POS.</p>
            </div>
            <a href="{{ $returnTo['url'] }}" class="text-sm font-medium text-slate-500 hover:text-blue-600 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                {{ $returnTo['label'] }}
            </a>
        </div>
    </x-slot>

    <div class="py-5 max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-medium mb-1">Please fix the following:</p>
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="product-create-form" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-0"
              x-data="{ submitting: false }"
              @submit="if (submitting) { $event.preventDefault(); return; } submitting = true">
            @csrf
            @if($returnTo['key'])
                <input type="hidden" name="return_to" value="{{ $returnTo['key'] }}">
            @endif

            @error('form')
                <div class="mb-4 rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
            @enderror

            @include('products.partials.form-fields')

            <div class="mt-5 flex items-center justify-end gap-2 sticky bottom-0 bg-[#F4F6FB]/95 backdrop-blur py-3 border-t border-slate-200/80">
                <a href="{{ $returnTo['url'] }}" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">
                    Cancel
                </a>
                <button type="submit" :disabled="submitting"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 inline-flex items-center gap-1.5 disabled:opacity-60 disabled:cursor-not-allowed"
                        form="product-create-form">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <span x-text="submitting ? 'Saving…' : 'Save product'"></span>
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
