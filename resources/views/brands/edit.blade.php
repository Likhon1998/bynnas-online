<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Brand') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg border border-gray-100">
                <form action="{{ route('brands.update', $brand) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div>
                        <x-input-label for="name" :value="__('Brand Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $brand->name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="tagline" :value="__('Homepage tagline')" />
                        <x-text-input id="tagline" name="tagline" type="text" class="mt-1 block w-full" :value="old('tagline', $brand->tagline)" placeholder="e.g. Phones · Watches · Audio" />
                        <p class="mt-1 text-xs text-gray-500">Shown under the brand name on the homepage strip.</p>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="logo" :value="__('Brand Logo')" />
                        <x-image-file-preview
                            name="logo"
                            id="logo"
                            :existing="$brand->logo_path ? public_storage_url($brand->logo_path) : null"
                            accept=".png,.jpg,.jpeg,.webp,.gif,.svg,image/png,image/jpeg,image/webp,image/gif,image/svg+xml"
                            input-class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            preview-class="h-16 max-w-full object-contain rounded-lg border border-slate-200 bg-white p-1"
                        />
                        <p class="mt-1 text-xs text-gray-500">PNG, JPG, WEBP, GIF or SVG — max 5MB. Extra whitespace is cropped so it fits cleanly in “Gadget Lovers” on the homepage.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('logo')" />
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', $brand->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                        <label for="is_active" class="text-sm text-gray-700">Show on website storefront</label>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-4">
                        <a href="{{ route('brands.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                        <x-primary-button>{{ __('Update Brand') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
