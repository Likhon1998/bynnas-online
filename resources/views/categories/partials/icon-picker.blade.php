{{-- Category icon picker (admin). Expects optional $category. --}}
@php
    $pickerIcons = \App\Support\CategoryIcons::forPicker();
    $selectedIcon = old(
        'icon',
        isset($category) && $category
            ? ($category->icon ?? \App\Support\CategoryIcons::suggest($category->name))
            : \App\Support\CategoryIcons::suggest(old('name'))
    );
    $selectedIcon = \App\Support\CategoryIcons::resolve($selectedIcon);
@endphp

<div class="mt-4"
     x-data="categoryIconPicker({
         icons: @js($pickerIcons),
         initialIcon: @js($selectedIcon),
         initialName: @js(old('name', isset($category) && $category ? $category->name : '')),
     })">
    <x-input-label for="icon" :value="__('Category icon')" />
    <p class="mt-1 text-xs text-gray-500">
        Type a name like “battery” or “charger” — matching icons are suggested. Click one to select.
    </p>

    <input type="hidden" name="icon" x-model="icon">

    <div class="mt-3 flex flex-wrap items-center gap-2" x-show="suggested.length" x-cloak>
        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-600">Suggested</span>
        <template x-for="key in suggested" :key="'s-'+key">
            <button type="button"
                    @click="pick(key)"
                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold transition"
                    :class="icon === key ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-2 ring-indigo-200' : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-300'">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full"
                      :style="'background:' + meta(key).bg + ';color:' + meta(key).color">
                    <span x-html="svg(key)"></span>
                </span>
                <span x-text="meta(key).label"></span>
            </button>
        </template>
    </div>

    <div class="mt-3 grid grid-cols-4 sm:grid-cols-6 md:grid-cols-7 gap-2">
        <template x-for="item in icons" :key="item.key">
            <button type="button"
                    @click="pick(item.key)"
                    class="group flex flex-col items-center gap-1.5 rounded-xl border p-2.5 transition"
                    :class="icon === item.key
                        ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200'
                        : (suggested.includes(item.key) ? 'border-indigo-200 bg-white' : 'border-slate-200 bg-white hover:border-slate-300')">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl transition group-hover:scale-105"
                      :style="'background:' + item.bg + ';color:' + item.color">
                    <span x-html="svg(item.key)"></span>
                </span>
                <span class="text-[10px] font-semibold text-slate-600 truncate w-full text-center" x-text="item.label"></span>
            </button>
        </template>
    </div>

    <x-input-error class="mt-2" :messages="$errors->get('icon')" />
</div>

@once
<script>
if (typeof window.categoryIconPicker !== 'function') {
window.categoryIconPicker = function({ icons, initialIcon, initialName }) {
    const svgPaths = {
        phone: 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        laptop: 'M3.75 9h16.5m-16.5 0A2.25 2.25 0 011.5 6.75v-.007c0-.372.3-.675.675-.675h19.65c.372 0 .675.3.675.675V6.75A2.25 2.25 0 0120.25 9m-16.5 0v7.5A2.25 2.25 0 006 18.75h12a2.25 2.25 0 002.25-2.25V9M8.25 21h7.5',
        tablet: 'M10.5 19.5h3m-6.75 2.25h10.5a2.25 2.25 0 002.25-2.25v-15a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 4.5v15a2.25 2.25 0 002.25 2.25z',
        headphones: 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375A1.125 1.125 0 012.25 17.625v-1.5c0-4.556 3.694-8.25 8.25-8.25s8.25 3.694 8.25 8.25v1.5c0 .621-.504 1.125-1.125 1.125H15.75m0 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0',
        watch: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        camera: 'M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z',
        battery: 'M21 10.5h.375c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125H21M3.75 18h15A2.25 2.25 0 0021 15.75v-6a2.25 2.25 0 00-2.25-2.25h-15A2.25 2.25 0 001.5 9.75v6A2.25 2.25 0 003.75 18z',
        charger: 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
        cable: 'M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z',
        speaker: 'M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z',
        game: 'M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z',
        mouse: 'M12 3v4.5m0 0a4.5 4.5 0 014.5 4.5v5.25a4.5 4.5 0 01-9 0V12A4.5 4.5 0 0112 7.5z',
        keyboard: 'M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6zM7.5 9h.008v.008H7.5V9zm3 0h.008v.008H10.5V9zm3 0h.008v.008H13.5V9zm3 0h.008v.008H16.5V9zm-9 3h.008v.008H7.5V12zm3 0h.008v.008H10.5V12zm3 0h.008v.008H13.5V12zm3 0h.008v.008H16.5V12zm-9 3h9',
        monitor: 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25A2.25 2.25 0 015.25 3h13.5A2.25 2.25 0 0121 5.25z',
        tv: 'M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125z',
        drone: 'M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z',
        router: 'M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z',
        memory: 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375',
        chip: 'M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z',
        accessory: 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z M6 6h.008v.008H6V6z',
        box: 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
    };

    return {
        icons,
        icon: initialIcon || 'box',
        name: initialName || '',
        manual: false,
        suggested: [],
        init() {
            this.refreshSuggestions(this.name);
            const nameEl = document.getElementById('name');
            if (nameEl) {
                nameEl.addEventListener('input', (e) => {
                    this.name = e.target.value;
                    this.refreshSuggestions(this.name);
                    if (!this.manual) {
                        this.icon = this.bestMatch(this.name);
                    }
                });
            }
        },
        meta(key) {
            return this.icons.find(i => i.key === key) || this.icons[0];
        },
        svg(key) {
            const d = svgPaths[key] || svgPaths.box;
            return `<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${d}"/></svg>`;
        },
        pick(key) {
            this.icon = key;
            this.manual = true;
        },
        bestMatch(name) {
            const hay = (name || '').toLowerCase().trim();
            if (!hay) return 'box';
            let best = 'box', score = 0;
            for (const item of this.icons) {
                for (const kw of item.keywords) {
                    const k = kw.toLowerCase();
                    if (hay === k) return item.key;
                    if (hay.includes(k) && k.length > score) {
                        score = k.length;
                        best = item.key;
                    }
                }
            }
            return best;
        },
        refreshSuggestions(name) {
            const hay = (name || '').toLowerCase().trim();
            if (!hay) {
                this.suggested = [];
                return;
            }
            const scored = [];
            for (const item of this.icons) {
                let s = 0;
                for (const kw of item.keywords) {
                    const k = kw.toLowerCase();
                    if (hay === k) s = Math.max(s, 100 + k.length);
                    else if (hay.includes(k)) s = Math.max(s, 50 + k.length);
                    else if (k.includes(hay)) s = Math.max(s, 20 + hay.length);
                }
                if (s > 0) scored.push({ key: item.key, s });
            }
            scored.sort((a, b) => b.s - a.s);
            this.suggested = scored.slice(0, 6).map(x => x.key);
        },
    };
};
}
</script>
@endonce
