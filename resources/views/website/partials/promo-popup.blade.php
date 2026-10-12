@php
    $promoPopup = $promoPopup ?? app(\App\Services\WebsiteService::class)->activePromoPopup();
@endphp
@if($promoPopup && filled($promoPopup->image_path))
@php
    $popupImage = public_storage_url($promoPopup->image_path);
    $popupUrl = trim((string) ($promoPopup->button_url ?? ''));
    $popupLabel = trim((string) ($promoPopup->button_text ?: 'Shop Now'));
    $popupKey = 'bb_promo_popup_'.$promoPopup->id.'_'.optional($promoPopup->updated_at)->timestamp;
@endphp
<div class="bb-promo-popup"
     x-data="{
        open: false,
        key: @js($popupKey),
        init() {
            try {
                if (sessionStorage.getItem(this.key) === '1') {
                    return;
                }
            } catch (e) {}
            this.$nextTick(() => {
                this.open = true;
                document.documentElement.classList.add('bb-promo-popup-lock');
            });
        },
        close() {
            this.open = false;
            document.documentElement.classList.remove('bb-promo-popup-lock');
            try { sessionStorage.setItem(this.key, '1'); } catch (e) {}
        }
     }"
     x-cloak
     x-show="open"
     x-transition.opacity.duration.200ms
     role="dialog"
     aria-modal="true"
     aria-label="{{ $promoPopup->title }}"
     @keydown.escape.window="close()">
    <div class="bb-promo-popup__backdrop" @click="close()" aria-hidden="true"></div>

    <div class="bb-promo-popup__dialog" @click.stop>
        <button type="button"
                class="bb-promo-popup__close"
                @click="close()"
                aria-label="Close offer">
            <span aria-hidden="true">×</span>
        </button>

        @if($popupUrl !== '')
            <a href="{{ $popupUrl }}" class="bb-promo-popup__media" @click="close()">
                <img src="{{ $popupImage }}" alt="{{ $promoPopup->title }}" width="900" height="1100" decoding="async">
            </a>
            <a href="{{ $popupUrl }}" class="bb-promo-popup__cta" @click="close()">{{ $popupLabel }}</a>
        @else
            <div class="bb-promo-popup__media">
                <img src="{{ $popupImage }}" alt="{{ $promoPopup->title }}" width="900" height="1100" decoding="async">
            </div>
        @endif
    </div>
</div>
@endif
