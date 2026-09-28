@php
    $dialCodes = \App\Models\Supplier::DIAL_CODES;
    $current = $value ?: '+880';
    if (! isset($dialCodes[$current])) {
        $current = '+880';
    }
@endphp

<div class="sup-dial"
     x-data="{
        open: false,
        code: @js($current),
        options: @js($dialCodes),
        get current() { return this.options[this.code] || this.options['+880']; },
        flagUrl(iso) {
            return 'https://flagcdn.com/w40/' + String(iso || 'bd').toLowerCase() + '.png';
        }
     }"
     @keydown.escape.window="open = false"
     @click.outside="open = false">
    <input type="hidden" name="{{ $name }}" :value="code">
    <button type="button" class="sup-dial-btn" @click="open = !open" :aria-expanded="open.toString()">
        <span class="inline-flex items-center gap-1.5 min-w-0">
            <img class="sup-dial-flag" :src="flagUrl(current.iso)" :alt="current.label" width="20" height="14" loading="lazy">
            <span class="sup-dial-code" x-text="code"></span>
        </span>
        <svg class="sup-dial-caret" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div class="sup-dial-menu" x-show="open" x-cloak x-transition.opacity.duration.100ms>
        <template x-for="(meta, dial) in options" :key="dial">
            <button type="button"
                    class="sup-dial-option"
                    :class="dial === code && 'is-active'"
                    @click="code = dial; open = false">
                <img class="sup-dial-flag" :src="flagUrl(meta.iso)" :alt="meta.label" width="20" height="14" loading="lazy">
                <span class="name" x-text="meta.label"></span>
                <span class="code" x-text="dial"></span>
            </button>
        </template>
    </div>
</div>
