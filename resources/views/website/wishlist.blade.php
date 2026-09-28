@extends('website.layout')
@section('title', 'Wishlist — '.($settings->store_name ?? config('app.name', 'Maks Gadget')))
@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-8">
        <div>
            <p class="text-xs font-bold tracking-[0.15em] uppercase text-blue-600">Saved items</p>
            <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">Wishlist</h1>
            <p class="mt-1 text-sm text-slate-500"><span x-text="wishlistCount"></span> item(s) saved for later</p>
        </div>
        <button type="button" x-show="wishlistCount>0" @click="wishlist=[]; saveWishlist()" class="text-xs font-semibold text-rose-600 hover:underline">Clear wishlist</button>
    </div>

    <template x-if="wishlist.length===0">
        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 py-20 text-center">
            <svg class="w-12 h-12 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <p class="mt-4 text-slate-600 font-semibold">Your wishlist is empty</p>
            <p class="mt-1 text-sm text-slate-400">Tap the heart on any product to save it here.</p>
            <a href="{{ route('website.shop') }}" class="mt-6 inline-flex rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Browse products</a>
        </div>
    </template>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5" x-show="wishlist.length>0">
        <template x-for="item in wishlist" :key="item.id">
            <div class="rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden flex flex-col">
                <a :href="item.url" class="block bg-slate-50 aspect-[4/3]">
                    <img :src="item.image" :alt="item.name" class="w-full h-full object-cover">
                </a>
                <div class="p-4 flex flex-col flex-1">
                    <a :href="item.url" class="text-sm font-semibold text-slate-900 hover:text-blue-600 line-clamp-2" x-text="item.name"></a>
                    <p class="mt-2 text-blue-600 font-bold text-sm" x-text="currency + Math.round(Number(item.price) || 0).toLocaleString()"></p>
                    <div class="mt-auto pt-4 flex gap-2">
                        <button type="button" class="flex-1 rounded-lg bg-slate-900 text-white text-xs font-bold py-2.5 hover:bg-blue-600"
                                :data-add-to-cart="JSON.stringify({id:item.id,name:item.name,price:item.price,image:item.image})"
                                data-qty="1"
                                data-open-cart="1">Add to cart</button>
                        <button type="button" class="rounded-lg border border-slate-200 px-3 text-rose-500 hover:bg-rose-50" @click="toggleWishlist(item)" title="Remove">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection
