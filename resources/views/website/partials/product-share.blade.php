@php
    // Tracked links: CaptureCampaignAttribution records utm_* on the visit, so orders from shares are attributed.
    $shareBase = route('website.product', $product);
    $shareUrl = fn (string $source) => $shareBase.'?'.http_build_query([
        'utm_source' => $source,
        'utm_medium' => 'social_share',
        'utm_campaign' => \App\Services\CampaignAttributionService::PRODUCT_SHARE,
        'utm_content' => $product->slug,
    ]);
    $shareTitle = $product->storefrontDisplayName();
    $facebookAppId = config('services.facebook.app_id');
    $links = [
        'facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($shareUrl('facebook')),
        'whatsapp' => 'https://wa.me/?text='.urlencode($shareTitle.' '.$shareUrl('whatsapp')),
        'messenger_app' => 'fb-messenger://share/?link='.urlencode($shareUrl('messenger')),
        'messenger_web' => $facebookAppId
            ? 'https://www.facebook.com/dialog/send?'.http_build_query(['app_id' => $facebookAppId, 'link' => $shareUrl('messenger'), 'redirect_uri' => $shareBase])
            : null,
        'copy' => $shareUrl('copy_link'),
        'messenger_copy' => $shareUrl('messenger'),
    ];
@endphp

<div class="mt-3 flex flex-wrap items-center gap-2"
     x-data="{
        links: @js($links),
        copied: '',
        isMobile: /Android|iPhone|iPad|iPod/i.test(navigator.userAgent),
        async copy(url, label) {
            try {
                await navigator.clipboard.writeText(url);
            } catch (e) {
                const t = document.createElement('textarea');
                t.value = url; document.body.appendChild(t); t.select();
                try { document.execCommand('copy'); } catch (_) {}
                t.remove();
            }
            this.copied = label;
            setTimeout(() => this.copied = '', 2500);
        },
        messenger() {
            if (this.isMobile) { window.location.href = this.links.messenger_app; return; }
            if (this.links.messenger_web) { window.open(this.links.messenger_web, '_blank', 'noopener,width=640,height=560'); return; }
            this.copy(this.links.messenger_copy, 'Link copied — paste it in Messenger');
        },
     }">
    <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Share</span>
    <a href="{{ $links['facebook'] }}" target="_blank" rel="noopener noreferrer"
       class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[12px] font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12a10 10 0 10-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0022 12z"/></svg>
        Facebook
    </a>
    <button type="button" @click="messenger()"
            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[12px] font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.19 5.44 3.14 7.17V22l2.87-1.58c.8.22 1.66.34 2.54.34h-.55c5.64 0 10-4.13 10-9.7S17.64 2 12 2zm1.04 13.06l-2.55-2.72-4.97 2.72 5.47-5.8 2.61 2.72 4.91-2.72-5.47 5.8z"/></svg>
        Messenger
    </button>
    <a href="{{ $links['whatsapp'] }}" target="_blank" rel="noopener noreferrer"
       class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[12px] font-semibold text-slate-700 hover:border-emerald-300 hover:text-emerald-700">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35zM12.04 21.5h-.01a9.5 9.5 0 01-4.84-1.33l-.35-.21-3.6.94.96-3.5-.23-.36a9.46 9.46 0 01-1.45-5.04c0-5.24 4.27-9.5 9.52-9.5 2.54 0 4.93.99 6.72 2.79a9.43 9.43 0 012.79 6.72c0 5.24-4.27 9.5-9.51 9.5z"/></svg>
        WhatsApp
    </a>
    <button type="button" @click="copy(links.copy, 'Link copied')"
            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[12px] font-semibold text-slate-700 hover:border-slate-400">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        Copy link
    </button>
    <span x-show="copied" x-cloak x-text="copied" class="text-[11px] font-semibold text-emerald-600"></span>
</div>
