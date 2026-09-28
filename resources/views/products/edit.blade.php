<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ __('Edit Product') }}</h2>
                <p class="text-sm text-slate-500 mt-0.5">Changes update the store product page immediately after save.</p>
            </div>
            <a href="{{ route('products.index') }}" class="text-sm font-medium text-slate-500 hover:text-blue-600">Back to Product List</a>
        </div>
    </x-slot>

    <div class="py-5 max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('products.partials.form-fields', ['product' => $product])

            <div class="mt-5 flex items-center justify-end gap-2 sticky bottom-0 bg-[#F4F6FB]/95 backdrop-blur py-3 border-t border-slate-200/80">
                <a href="{{ route('products.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">Cancel</a>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Update product</button>
            </div>
        </form>
    </div>
</x-app-layout>
