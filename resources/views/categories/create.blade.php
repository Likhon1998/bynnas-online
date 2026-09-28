<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New Category') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div class="md:col-span-2">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg border border-gray-100">
                    <form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div>
                            <x-input-label for="name" :value="__('Category Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus placeholder="e.g. Battery, Smart Phone, Charger" />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="description" :value="__('Cover tagline (homepage)')" />
                            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description')" placeholder="e.g. Power in your pocket." />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="image" :value="__('Cover image')" />
                            <input id="image" name="image" type="file" accept=".png,.jpg,.jpeg,.webp,.gif,image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700">
                        </div>

                        @include('categories.partials.icon-picker')

                        @include('categories.partials.filter-options', ['filterDefaults' => $filterDefaults])

                        <div class="mt-4 p-4 bg-blue-50 rounded-lg flex gap-3">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-xs text-blue-700">
                                Categories help organize your inventory. Sidebar filters appear on the website when customers open this category.
                            </p>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-4">
                            <a href="{{ route('categories.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                            <x-primary-button>{{ __('Save Category') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-slate-50 p-5 rounded-lg border border-slate-200">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-3">Your Active Categories</h3>
                    <div class="flex flex-wrap gap-2">
                        @php
                            // Fetching categories directly in view for quick reference
                            $existingCategories = \App\Models\Category::where('shop_id', Auth::user()->shop_id)->latest()->take(10)->get();
                        @endphp
                        
                        @forelse($existingCategories as $existing)
                            <button type="button" 
                                    onclick="document.getElementById('name').value = '{{ $existing->name }}'"
                                    class="px-3 py-1 bg-white border border-slate-300 rounded-full text-xs text-slate-600 hover:border-indigo-500 hover:text-indigo-600 transition">
                                {{ $existing->name }}
                            </button>
                        @empty
                            <p class="text-xs text-slate-400 italic">No categories created yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-indigo-50 p-5 rounded-lg border border-indigo-100">
                    <h3 class="text-sm font-bold text-indigo-700 uppercase tracking-wider mb-2">Pro Tip</h3>
                    <p class="text-xs text-indigo-600 leading-relaxed">
                        Use broad names like "Beverages" or "Snacks" rather than specific item names like "Coke". This makes your **Stock Ledger** much easier to read later!
                    </p>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>