@extends('website.layout')
@php
    $settings = $settings ?? (object) [];
    $storeName = data_get($settings, 'store_name', config('app.name', 'Maks Gadget'));
    $heroKicker = data_get($settings, 'blog_hero_kicker') ?: 'OUR BLOG';
    $heroTitle = data_get($settings, 'blog_hero_title') ?: 'News & Articles';
    $heroSub = data_get($settings, 'blog_hero_subtitle')
        ?: 'Stay updated with the latest tech news, product reviews, and buying guides from '.$storeName.'.';
    $heroImage = data_get($settings, 'blog_hero_image')
        ? public_storage_url(data_get($settings, 'blog_hero_image'))
        : 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=1600&q=80';
    $articlesTitle = data_get($settings, 'blog_articles_title') ?: 'Latest Articles';
    $newsletterTitle = data_get($settings, 'blog_newsletter_title') ?: 'Subscribe to Our Newsletter';
    $newsletterText = data_get($settings, 'blog_newsletter_text') ?: 'Get the latest deals and tech news delivered to your inbox.';
    $features = [
        [
            'title' => data_get($settings, 'blog_feature_1_title') ?: 'Expert Reviews',
            'text' => data_get($settings, 'blog_feature_1_text') ?: 'In-depth & honest',
            'icon' => 'review',
        ],
        [
            'title' => data_get($settings, 'blog_feature_2_title') ?: 'Buying Guides',
            'text' => data_get($settings, 'blog_feature_2_text') ?: 'Smart picks for you',
            'icon' => 'guide',
        ],
        [
            'title' => data_get($settings, 'blog_feature_3_title') ?: 'Latest Updates',
            'text' => data_get($settings, 'blog_feature_3_text') ?: 'Tech news, trends & more',
            'icon' => 'updates',
        ],
    ];
    $blogCategories = $blogCategories ?? collect();
    $popularPosts = $popularPosts ?? collect();
    $blogSearch = $blogSearch ?? null;
    $activeBlogCategory = $activeBlogCategory ?? null;
    $badgeMap = [
        'blue' => 'bg-blue-600 text-white',
        'emerald' => 'bg-emerald-600 text-white',
        'amber' => 'bg-amber-500 text-white',
        'rose' => 'bg-rose-600 text-white',
        'violet' => 'bg-violet-600 text-white',
        'slate' => 'bg-slate-700 text-white',
    ];
    $catIconMap = [
        'blue' => 'bg-blue-50 text-blue-600',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'rose' => 'bg-rose-50 text-rose-700',
        'violet' => 'bg-violet-50 text-violet-700',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
@endphp
@section('title', 'Blog — '.$storeName)
@section('content')

{{-- Hero --}}
<section class="relative overflow-hidden border-b border-slate-100">
    <div class="absolute inset-0">
        <img src="{{ $heroImage }}" alt="" class="h-full w-full object-cover object-center opacity-35">
        <div class="absolute inset-0 bg-gradient-to-r from-slate-100 via-slate-50/92 to-transparent"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 py-7 sm:py-8">
        <p class="text-[10px] font-bold tracking-[0.18em] uppercase text-blue-500">{{ $heroKicker }}</p>
        <h1 class="mt-1 text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ $heroTitle }}</h1>
        <p class="mt-1.5 max-w-lg text-xs sm:text-[13px] text-slate-600 leading-relaxed">{{ $heroSub }}</p>

        <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2.5">
            @foreach($features as $feature)
                <div class="flex items-center gap-2">
                    <span class="shrink-0 w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        @if($feature['icon'] === 'review')
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @elseif($feature['icon'] === 'guide')
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        @else
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        @endif
                    </span>
                    <div>
                        <p class="text-xs font-semibold text-slate-800 leading-none">{{ $feature['title'] }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-500 leading-none">{{ $feature['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 py-6 sm:py-7">
    <div class="grid lg:grid-cols-12 gap-5 lg:gap-6">
        {{-- Main column --}}
        <div class="lg:col-span-9 min-w-0">
            @if($blogSearch || $activeBlogCategory)
                <div class="mb-3.5 flex items-center justify-between gap-3">
                    <p class="text-xs text-slate-600">
                        @if($blogSearch) Results for “<strong>{{ $blogSearch }}</strong>” @endif
                        @if($activeBlogCategory)
                            @php $cat = $blogCategories->firstWhere('slug', $activeBlogCategory); @endphp
                            Category: <strong>{{ $cat->name ?? $activeBlogCategory }}</strong>
                        @endif
                    </p>
                    <a href="{{ route('website.blogs') }}" class="text-[11px] font-semibold text-blue-600 hover:text-blue-700">Clear filters</a>
                </div>
            @else
                <h2 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight mb-3.5">{{ $articlesTitle }}</h2>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @forelse($blogs as $post)
                    @php
                        $catColor = $post->category->color ?? 'blue';
                        $badgeClass = $badgeMap[$catColor] ?? $badgeMap['blue'];
                    @endphp
                    <article class="group flex flex-col rounded-xl border border-slate-100 bg-white overflow-hidden hover:border-slate-200 transition">
                        <a href="{{ route('website.blog', $post->slug) }}" class="relative block overflow-hidden bg-slate-100 aspect-[16/10]">
                            <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}"
                                 class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                            @if($post->category)
                                <span class="absolute bottom-2 left-2 inline-flex text-[9px] font-bold uppercase tracking-wider {{ $badgeClass }} px-1.5 py-0.5 rounded">
                                    {{ $post->category->name }}
                                </span>
                            @endif
                        </a>
                        <div class="p-3 flex flex-col flex-1">
                            <h3 class="text-[13px] font-semibold text-slate-900 leading-snug line-clamp-2">
                                <a href="{{ route('website.blog', $post->slug) }}" class="hover:text-blue-600 transition">{{ $post->title }}</a>
                            </h3>
                            @if($post->excerpt)
                                <p class="mt-1.5 text-[11px] text-slate-500 leading-relaxed line-clamp-2">{{ $post->excerpt }}</p>
                            @endif
                            <div class="mt-2.5 pt-2 border-t border-slate-50 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[10px] text-slate-400">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ optional($post->published_at)->format('M d, Y') }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $post->readingTimeLabel() }}
                                </span>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="sm:col-span-2 xl:col-span-3 text-center text-slate-400 py-10 text-xs">No blog posts yet. Add posts from Admin → CMS → Blogs.</p>
                @endforelse
            </div>

            @if($blogs->hasPages())
                <div class="mt-5">{{ $blogs->links() }}</div>
            @endif
        </div>

        {{-- Sidebar --}}
        <aside class="lg:col-span-3 space-y-3.5">
            {{-- Search --}}
            <div class="rounded-xl border border-slate-100 bg-white p-3">
                <form action="{{ route('website.blogs') }}" method="GET" class="flex gap-1.5">
                    @if($activeBlogCategory)<input type="hidden" name="category" value="{{ $activeBlogCategory }}">@endif
                    <input type="search" name="q" value="{{ $blogSearch }}" placeholder="Search articles..."
                           class="flex-1 rounded-lg border-slate-200 text-xs h-9 focus:border-blue-500 focus:ring-blue-500">
                    <button type="submit" class="shrink-0 w-9 h-9 rounded-lg bg-slate-900 text-white flex items-center justify-center hover:bg-slate-800 transition" title="Search">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </form>
            </div>

            {{-- Categories --}}
            @if($blogCategories->count())
            <div class="rounded-xl border border-slate-100 bg-white p-3.5">
                <h3 class="text-xs font-bold text-slate-900 mb-2.5">Categories</h3>
                <ul class="space-y-2">
                    @foreach($blogCategories as $cat)
                        @php $iconClass = $catIconMap[$cat->color ?? 'blue'] ?? $catIconMap['blue']; @endphp
                        <li>
                            <a href="{{ route('website.blogs', ['category' => $cat->slug]) }}"
                               class="flex items-center justify-between text-xs {{ ($activeBlogCategory ?? '') === $cat->slug ? 'text-blue-600 font-semibold' : 'text-slate-600 hover:text-blue-600' }} transition">
                                <span class="inline-flex items-center gap-2 min-w-0">
                                    <span class="w-6 h-6 rounded-md {{ $iconClass }} flex items-center justify-center text-[10px] font-bold shrink-0">{{ strtoupper(substr($cat->name, 0, 1)) }}</span>
                                    <span class="truncate">{{ $cat->name }}</span>
                                </span>
                                <span class="text-[10px] text-slate-400 shrink-0 ml-1.5">({{ $cat->blogs_count }})</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('website.blogs') }}" class="mt-2.5 inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 hover:text-blue-700">
                    View All Categories
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            @endif

            {{-- Popular Posts --}}
            @if($popularPosts->count())
            <div class="rounded-xl border border-slate-100 bg-white p-3.5">
                <h3 class="text-xs font-bold text-slate-900 mb-2.5">Popular Posts</h3>
                <ul class="space-y-2.5">
                    @foreach($popularPosts as $pop)
                        <li>
                            <a href="{{ route('website.blog', $pop->slug) }}" class="flex gap-2.5 group">
                                <img src="{{ $pop->coverUrl() }}" alt="" class="w-12 h-12 rounded-lg object-cover bg-slate-100 shrink-0">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold text-slate-800 group-hover:text-blue-600 line-clamp-2 leading-snug transition">{{ $pop->title }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ optional($pop->published_at)->format('M d, Y') }}</p>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Newsletter --}}
            <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-3.5">
                <h3 class="text-xs font-bold text-slate-900">{{ $newsletterTitle }}</h3>
                <p class="mt-1 text-[11px] text-slate-500 leading-relaxed">{{ $newsletterText }}</p>
                @if(session('newsletter_success'))
                    <p class="mt-2 text-[11px] font-semibold text-emerald-600">{{ session('newsletter_success') }}</p>
                @endif
                <form action="{{ route('website.newsletter') }}" method="POST" class="mt-2.5 space-y-2">
                    @csrf
                    <input type="email" name="email" required placeholder="Your email address"
                           class="w-full rounded-lg border-slate-200 bg-white text-xs h-9 focus:border-blue-500 focus:ring-blue-500">
                    <button type="submit" class="w-full h-9 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 inline-flex items-center justify-center gap-1.5 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Subscribe
                    </button>
                </form>
            </div>
        </aside>
    </div>
</div>
@endsection
