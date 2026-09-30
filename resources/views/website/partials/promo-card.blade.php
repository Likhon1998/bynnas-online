@php
    /** @var \App\Models\PromoBanner $banner */
    $symbol = $settings->currency_symbol ?? '৳';
    $isLight = ($banner->theme ?? 'dark') === 'light';
    $image = $banner->image_path ? public_storage_url($banner->image_path) : null;
    $url = $banner->button_url ?: route('website.shop');
    $subtitle = e((string) $banner->subtitle);
    $highlight = trim((string) $banner->highlight_text);
    $highlightInline = $highlight !== '' && $subtitle !== '' && stripos($subtitle, e($highlight)) !== false;
    if ($highlightInline) {
        $subtitle = preg_replace('/'.preg_quote(e($highlight), '/').'/i', '<mark>$0</mark>', $subtitle, 1);
    }
@endphp
<a href="{{ $url }}" class="bb-promo {{ $isLight ? 'is-light' : 'is-dark' }} {{ $variant ?? '' }}">
    @if($image)
        <img src="{{ $image }}" alt="" class="bb-promo-img" loading="lazy" decoding="async">
    @endif
    <span class="bb-promo-shade" aria-hidden="true"></span>
    @if($banner->discount_badge)
        <span class="bb-promo-corner">{{ $banner->discount_badge }}</span>
    @endif
    <span class="bb-promo-body">
        @if($banner->badge_text || ($highlight !== '' && ! $highlightInline))
            <span class="bb-promo-tags">
                @if($banner->badge_text)<span class="bb-promo-badge">{{ $banner->badge_text }}</span>@endif
                @if($highlight !== '' && ! $highlightInline)<span class="bb-promo-badge is-soft">{{ $highlight }}</span>@endif
            </span>
        @endif
        <span class="bb-promo-title">{{ $banner->title }}</span>
        @if($subtitle !== '')
            <span class="bb-promo-sub">{!! $subtitle !!}</span>
        @endif
        <span class="bb-promo-foot">
            @if((float) $banner->price_from > 0)
                <span class="bb-promo-price">From <b>{{ $symbol }}{{ format_taka_number($banner->price_from) }}</b></span>
            @endif
            <span class="bb-promo-cta">{{ $banner->button_text ?: 'Shop Now' }} @include('website.partials.bb-icon', ['name' => 'arrow-right'])</span>
        </span>
    </span>
</a>
