<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Bynnas Social') }}</title>
    @include('partials.favicon')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/website-lottie.js'])
    <style>
        [x-cloak]{display:none!important}

        /* ── Brand base ── */
        body.admin-panel{color:#3a2a24}
        body.admin-panel h1,body.admin-panel h2,body.admin-panel .font-display{
            font-family:'Fredoka','Nunito',ui-sans-serif,system-ui,sans-serif;font-weight:600;letter-spacing:0
        }
        body.admin-panel .rounded-xl{border-radius:14px}
        body.admin-panel .rounded-2xl{border-radius:18px}
        body.admin-panel .shadow-sm{box-shadow:0 1px 2px rgba(74,48,38,.04),0 4px 14px -6px rgba(74,48,38,.08)}
        body.admin-panel .shadow{box-shadow:0 2px 4px rgba(74,48,38,.05),0 8px 20px -8px rgba(74,48,38,.12)}
        body.admin-panel input:not([type=checkbox]):not([type=radio]):focus,
        body.admin-panel select:focus,
        body.admin-panel textarea:focus{
            border-color:#b39cf0;--tw-ring-color:rgba(139,111,214,.28);outline:none
        }
        body.admin-panel ::selection{background:#efe9fc;color:#3a2a24}
        .admin-scroll-hide{
            background:
                radial-gradient(900px 380px at 100% -8%, rgba(239,233,252,.75), transparent 62%),
                radial-gradient(720px 360px at -8% 100%, rgba(252,235,221,.55), transparent 60%),
                #fffaf5
        }

        .bb-lottie{position:relative;display:inline-grid;place-items:center;line-height:0;container-type:size}
        .bb-lottie svg{position:absolute;inset:0;width:100%!important;height:100%!important}
        .bb-lottie-fallback{font-size:min(26px,70cqmin);line-height:1}
        .bb-lottie.is-lottie-ready .bb-lottie-fallback{display:none}

        .powered-by-admin{
            flex-shrink:0;
            padding:10px 16px 14px;
            text-align:center;
            font-size:11px;
            font-weight:600;
            letter-spacing:.03em;
            color:#654f45;
            border-top:1px solid #f2e6dc;
            background:transparent;
        }
        .powered-by-admin strong{color:#3a2a24;font-weight:800}
        .powered-by-admin a,.powered-by-link{color:inherit;text-decoration:none}
        .powered-by-admin a:hover,.powered-by-admin .powered-by-link:hover{color:#b84f2c;text-decoration:underline}

        /* ── Admin sidebar (warm light theme) ── */
        :root{
            --sb-bg:#ffffff;
            --sb-line:#f2e6dc;
            --sb-text:#6f5b52;
            --sb-text-strong:#3a2a24;
            --sb-muted:#8a7468;
            --sb-hover:#fdf3ec;
            --sb-width:244px;
            --sb-rail:68px;
            --sb-top:3.5rem
        }
        .admin-sidebar{
            position:relative;z-index:50;
            display:flex;flex-direction:column;
            width:var(--sb-width);min-width:var(--sb-width);max-width:var(--sb-width);
            flex:0 0 var(--sb-width);height:100vh;
            background:linear-gradient(180deg,#fff 0%,#fffdfb 60%,#fff6ee 100%);
            border-right:1px solid var(--sb-line);
            box-shadow:6px 0 24px -12px rgba(74,48,38,.12);
            color:var(--sb-text);overflow:hidden
        }
        .admin-sidebar.is-collapsed{
            width:var(--sb-rail);min-width:var(--sb-rail);max-width:var(--sb-rail);flex:0 0 var(--sb-rail)
        }
        .admin-sidebar-backdrop{display:none}
        .admin-mobile-menu-btn{display:none}
        .admin-sidebar-close{display:none}
        .sidebar-collapse-btn{
            display:inline-flex;align-items:center;justify-content:center;
            width:26px;height:26px;border-radius:999px;border:1px solid var(--sb-line);
            color:var(--sb-muted);background:#fff;
            transition:color .15s ease,background .15s ease,border-color .15s ease,transform .2s ease
        }
        .sidebar-collapse-btn:hover{
            color:#b84f2c;background:var(--sb-hover);border-color:#f6d9c8;transform:scale(1.06)
        }
        .admin-topbar{
            position:fixed;top:0;right:0;left:var(--sb-width);height:var(--sb-top);z-index:30;
            display:flex;align-items:center;gap:.75rem;padding:0 1.15rem;
            background:rgba(255,250,245,.86);backdrop-filter:blur(14px);
            border-bottom:1px solid var(--sb-line);transition:left .22s ease
        }
        .admin-topbar.is-rail{left:var(--sb-rail)}

        @media (max-width:767px){
            .admin-sidebar{
                position:fixed;top:0;left:0;bottom:0;height:auto;
                width:min(288px,88vw);min-width:0;max-width:min(288px,88vw);flex:none;
                transform:translateX(-105%);transition:transform .26s ease;
                box-shadow:16px 0 48px rgba(74,48,38,.22)
            }
            .admin-sidebar.is-collapsed{width:min(288px,88vw);min-width:0;max-width:min(288px,88vw);flex:none}
            .admin-sidebar.is-mobile-open{transform:translateX(0)}
            .admin-sidebar-backdrop{
                display:none;position:fixed;inset:0;z-index:40;
                background:rgba(58,42,36,.35);backdrop-filter:blur(2px)
            }
            .admin-sidebar-backdrop.is-visible{display:block}
            .admin-mobile-menu-btn{display:inline-flex}
            .admin-sidebar-close{display:inline-flex}
            .sidebar-collapse-btn{display:none !important}
            .admin-topbar{left:0;padding:0 1rem}
            .admin-topbar.is-rail{left:0}
        }

        .admin-sidebar .sidebar-head{
            height:var(--sb-top);padding:0 .75rem 0 .9rem;border-bottom:1px dashed var(--sb-line)
        }
        .sidebar-brand-link{color:var(--sb-text-strong) !important;text-decoration:none}
        .sidebar-brand-text{font-family:'Fredoka','Nunito',sans-serif;font-size:16px;font-weight:600;letter-spacing:0;line-height:1.15}
        .sidebar-brand-mark{border-radius:10px}
        .sidebar-brand-lottie{background:#fcebdd;box-shadow:0 3px 0 #f6d9c8}
        .sidebar-brand-lottie .bb-lottie{width:24px;height:24px}
        .admin-sidebar-nav{
            padding:.6rem .6rem 1.25rem;
            scrollbar-width:thin;scrollbar-color:#f0e4da transparent
        }
        .nav-divider{height:0;margin:.6rem .4rem .55rem;border-top:1px dashed var(--sb-line)}

        .nav-link,.nav-group{
            display:flex;align-items:center;width:100%;gap:10px;
            padding:5px 8px;margin:2px 0;border-radius:12px;
            font-size:13px;font-weight:600;letter-spacing:0;
            color:var(--sb-text);text-align:left;background:transparent;border:0;
            cursor:pointer;text-decoration:none;line-height:1.2;
            transition:background .18s ease,color .18s ease,transform .18s ease,box-shadow .18s ease
        }
        .nav-group{justify-content:space-between}
        .nav-group-main{display:flex;align-items:center;gap:10px;min-width:0}
        .nav-label{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .nav-link:hover,.nav-group:hover{background:var(--sb-hover);color:var(--sb-text-strong)}
        .nav-link--badge{justify-content:flex-start}
        .nav-link--badge .nav-label{flex:1;min-width:0}

        .nav-ico-wrap{
            width:30px;height:30px;flex-shrink:0;border-radius:10px;
            display:inline-flex;align-items:center;justify-content:center;
            color:var(--tone,#8c776d);background:var(--tone-bg,#f8f1eb);
            transition:transform .22s cubic-bezier(.34,1.56,.64,1),background .18s ease,box-shadow .18s ease,color .18s ease
        }
        .nav-ico{width:15px;height:15px;stroke-width:1.9}
        .nav-link:hover .nav-ico-wrap,.nav-group:hover .nav-ico-wrap{transform:translateY(-1px) rotate(-4deg) scale(1.06)}

        /* Active / open: tinted row + candy-filled icon in the feature's tone */
        .nav-link.is-active,.nav-group.is-open{font-weight:800;color:var(--sb-text-strong)}
        .nav-link.is-active{background:linear-gradient(90deg,var(--tone-bg,#fdf3ec),rgba(255,255,255,0) 92%)}
        .nav-group.is-open{background:linear-gradient(90deg,var(--tone-bg,#fdf3ec),rgba(255,255,255,0) 80%)}
        .nav-link.is-active .nav-ico-wrap,.nav-group.is-open .nav-ico-wrap{
            color:#fff;background:var(--tone,#ec8560);
            box-shadow:0 3px 0 var(--tone-edge,rgba(0,0,0,.12)),0 6px 14px -4px var(--tone-glow,rgba(0,0,0,.18));
            animation:navIcoPop .42s cubic-bezier(.34,1.45,.64,1) both
        }
        @keyframes navIcoPop{
            0%{transform:scale(.86) rotate(-6deg)}
            55%{transform:scale(1.14) rotate(3deg)}
            100%{transform:scale(1) rotate(0)}
        }
        @media (prefers-reduced-motion:reduce){
            .nav-link.is-active .nav-ico-wrap,.nav-group.is-open .nav-ico-wrap{animation:none}
            .nav-link:hover .nav-ico-wrap,.nav-group:hover .nav-ico-wrap{transform:none}
        }

        .nav-tone-dash{--tone:#c95a34;--tone-bg:#fde8dd;--tone-edge:#9e4225;--tone-glow:rgba(201,90,52,.4)}
        .nav-tone-sales,.nav-tone-credit{--tone:#557a41;--tone-bg:#e9f1e1;--tone-edge:#3f5c2f;--tone-glow:rgba(85,122,65,.4)}
        .nav-tone-orders,.nav-tone-catalog{--tone:#7657c9;--tone-bg:#efe9fc;--tone-edge:#553a9e;--tone-glow:rgba(118,87,201,.4)}
        .nav-tone-products,.nav-tone-cash{--tone:#a8620c;--tone-bg:#fdf1d6;--tone-edge:#7f4a08;--tone-glow:rgba(168,98,12,.38)}
        .nav-tone-customers,.nav-tone-reports{--tone:#2a74a4;--tone-bg:#e2f1fa;--tone-edge:#1d5a82;--tone-glow:rgba(42,116,164,.38)}
        .nav-tone-accounts{--tone:#1d7c6f;--tone-bg:#dcf3ef;--tone-edge:#145c52;--tone-glow:rgba(29,124,111,.38)}
        .nav-tone-alert{--tone:#c43d63;--tone-bg:#fde4ea;--tone-edge:#9c2e4e;--tone-glow:rgba(196,61,99,.38)}
        .nav-tone-inventory{--tone:#b84f2c;--tone-bg:#fde8dd;--tone-edge:#8a3a20;--tone-glow:rgba(184,79,44,.38)}
        .nav-tone-web{--tone:#b0407f;--tone-bg:#fbe4f1;--tone-edge:#8a2f62;--tone-glow:rgba(176,64,127,.38)}
        .nav-tone-team{--tone:#6f5b52;--tone-bg:#f3ebe4;--tone-edge:#54423a;--tone-glow:rgba(111,91,82,.32)}
        .nav-tone-alert.is-active .nav-ico-wrap{animation:navIcoPulse 1.6s ease-in-out infinite}
        @keyframes navIcoPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.08)}}

        .nav-chevron{
            width:11px;height:11px;flex-shrink:0;opacity:.4;
            transition:transform .18s ease,opacity .12s ease
        }
        .nav-group:hover .nav-chevron,.nav-group.is-open .nav-chevron{opacity:.75}

        .nav-submenu{
            margin:2px 0 6px 22px;padding:2px 0 2px 10px;
            border-left:2px solid var(--sb-line);
            display:flex;flex-direction:column;gap:1px
        }
        .nav-sub{
            display:flex;align-items:center;gap:8px;
            padding:5px 9px;border-radius:10px;
            font-size:12.5px;font-weight:600;color:#654f45;
            transition:background .14s ease,color .14s ease,transform .14s ease,box-shadow .14s ease;
            text-decoration:none;letter-spacing:0;line-height:1.2
        }
        .nav-sub:hover{background:var(--sb-hover);color:var(--sb-text-strong);transform:translateX(2px)}
        .nav-sub.is-active{
            background:#fff;color:var(--sb-text-strong);font-weight:800;
            box-shadow:0 0 0 1px var(--sb-line),0 3px 10px -4px rgba(74,48,38,.14)
        }
        .nav-sub-ico{width:14px;height:14px;flex-shrink:0;opacity:.6;stroke-width:1.8}
        .nav-sub:hover .nav-sub-ico{opacity:.95}
        .nav-sub.is-active .nav-sub-ico{opacity:1;color:#b84f2c}
        .nav-badge{
            display:inline-flex;align-items:center;justify-content:center;
            min-width:18px;height:18px;padding:0 5px;margin-left:auto;
            border-radius:999px;background:#c43d63;color:#fff;
            font-size:9.5px;font-weight:800;line-height:1;
            box-shadow:0 2px 0 #9c2e4e;
            animation:navBadgePop .5s ease both
        }
        @keyframes navBadgePop{0%{transform:scale(0)}70%{transform:scale(1.15)}100%{transform:scale(1)}}

        /* Sidebar store card */
        .sidebar-foot{flex-shrink:0;padding:.6rem .7rem .8rem}
        .sidebar-store-card{
            position:relative;display:flex;align-items:center;gap:10px;
            padding:10px 12px;border-radius:16px;text-decoration:none;
            background:linear-gradient(135deg,#fcebdd 0%,#efe9fc 100%);
            border:1px solid #f2e6dc;color:#3a2a24;
            transition:transform .18s ease,box-shadow .18s ease
        }
        .sidebar-store-card:hover{transform:translateY(-2px);box-shadow:0 10px 22px -10px rgba(139,111,214,.45)}
        .sidebar-store-card .bb-lottie{width:34px;height:34px;flex-shrink:0}
        .sidebar-store-card strong{display:block;font-family:'Fredoka','Nunito',sans-serif;font-size:13.5px;font-weight:600;line-height:1.15}
        .sidebar-store-card small{display:block;font-size:11px;font-weight:700;color:#5a4740}

        /* Collapsed rail */
        .admin-sidebar.is-collapsed .nav-label,
        .admin-sidebar.is-collapsed .nav-chevron,
        .admin-sidebar.is-collapsed .nav-badge,
        .admin-sidebar.is-collapsed .nav-divider,
        .admin-sidebar.is-collapsed .sidebar-store-copy{display:none !important}
        .admin-sidebar.is-collapsed .sidebar-store-card{justify-content:center;padding:8px}
        .admin-sidebar.is-collapsed .sidebar-foot{padding:.5rem .4rem .7rem}
        .admin-sidebar.is-collapsed .sidebar-brand-link{justify-content:center;flex:0 0 auto !important;width:100%}
        .admin-sidebar.is-collapsed .sidebar-brand-mark{width:1.9rem !important;height:1.9rem !important}
        .admin-sidebar.is-collapsed .nav-link,
        .admin-sidebar.is-collapsed .nav-group{
            justify-content:center !important;padding:6px 0 !important;gap:0 !important;background:transparent !important;box-shadow:none !important
        }
        .admin-sidebar.is-collapsed .nav-group-main{justify-content:center;width:100%;gap:0}
        .admin-sidebar.is-collapsed .nav-ico-wrap{width:34px;height:34px}
        .admin-sidebar.is-collapsed .sidebar-head{
            flex-direction:column;align-items:center;justify-content:center;
            height:auto !important;min-height:var(--sb-top);padding:.65rem .3rem .5rem;gap:.35rem
        }
        .admin-sidebar.is-collapsed .admin-sidebar-nav{padding:.35rem .28rem 1rem}
        .admin-sidebar.is-collapsed .nav-submenu{display:none !important}

        /* Top bar actions */
        .admin-topbar .topbar-pill{
            display:inline-flex;align-items:center;gap:6px;border-radius:999px;
            padding:7px 14px;font-size:12px;font-weight:800;line-height:1;color:#fff;
            transition:transform .15s ease,box-shadow .15s ease,filter .15s ease
        }
        .admin-topbar .topbar-pill:hover{transform:translateY(-1px);filter:brightness(1.04)}
        .admin-topbar .topbar-pill:active{transform:translateY(1px)}
        .admin-topbar .topbar-pill--pos{background:#6a4bc4;box-shadow:0 3px 0 #4f3597}
        .admin-topbar .topbar-pill--store{background:#bf5230;box-shadow:0 3px 0 #963d20}
        .admin-topbar .topbar-icon-btn{border-radius:999px !important}
        @media (max-width:639px){.admin-topbar .topbar-pill{padding:7px 11px;font-size:11px}}
    </style>
</head>
<body class="admin-panel font-sans antialiased text-slate-900 bg-[#fffaf5]">
    @php
        $adminFlash = array_filter([
            'success' => session('success'),
            'error' => session('error'),
            'warning' => session('warning'),
            'info' => session('info'),
        ], fn ($v) => filled($v));
    @endphp
    <script>
        window.__ADMIN_FLASH__ = @json($adminFlash);
    </script>

    <div id="admin-progress" class="admin-progress" aria-hidden="true">
        <div id="admin-progress-bar" class="admin-progress__bar"></div>
        <div id="admin-progress-peg" class="admin-progress__peg"></div>
    </div>

    <div x-data="{
            sidebarOpen: false,
            sidebarCollapsed: false,
            catalogOpen: false,
            inventoryOpen: false,
            salesOpen: false,
            customersOpen: false,
            insightsOpen: false,
            websiteOpen: false,
            teamOpen: false,
            init() {
                localStorage.removeItem('adminSidebarCollapsed');
                localStorage.setItem('adminSidebarCollapseV', '4');
                this.sidebarCollapsed = false;
                this.sidebarOpen = false;
            },
            toggleSidebarCollapse() {
                if (window.matchMedia('(max-width: 767px)').matches) return;
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('adminSidebarCollapsed', this.sidebarCollapsed ? '1' : '0');
            },
            expandThen(key) {
                if (this.sidebarCollapsed) {
                    this.sidebarCollapsed = false;
                    localStorage.setItem('adminSidebarCollapsed', '0');
                    this[key] = true;
                    return;
                }
                this[key] = !this[key];
            }
         }"
         @keydown.escape.window="sidebarOpen = false"
         @resize.window="if (window.innerWidth < 1024) { sidebarCollapsed = false; }"
         class="flex h-screen overflow-hidden">

        @include('layouts.navigation')

        <div class="admin-scroll-hide relative flex flex-col flex-1 min-w-0 overflow-y-auto overflow-x-hidden">
            @isset($header)
                <header class="bg-white/80 backdrop-blur border-b border-slate-200 mt-14 lg:mt-14">
                    <div class="max-w-[1800px] mx-auto py-3 px-3 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="w-full grow p-3 sm:p-4 lg:p-5 min-w-0 {{ !isset($header) ? 'mt-14 lg:mt-14' : '' }}">
                <div class="w-full max-w-[1800px] mx-auto min-w-0">
                    {{ $slot }}
                </div>
            </main>
            @include('partials.powered-by', ['variant' => 'admin'])
        </div>
    </div>

    <script>
        function staffAlertBell(feedUrl, readAllUrl) {
            const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
            const post = (url) => fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                credentials: 'same-origin',
                keepalive: true,
            });
            return {
                panelOpen: false,
                loading: false,
                unread: 0,
                items: [],
                init() {
                    this.load();
                    setInterval(() => this.load(), 60000);
                },
                async load() {
                    this.loading = true;
                    try {
                        const res = await fetch(feedUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.items = data.items || [];
                        this.unread = Number(data.unread || 0);
                    } catch (e) {
                    } finally {
                        this.loading = false;
                    }
                },
                toggle() {
                    this.panelOpen = !this.panelOpen;
                    if (this.panelOpen) this.load();
                },
                async open(item, event) {
                    event.preventDefault();
                    if (item.is_new) {
                        try { await post(item.read_url); } catch (e) {}
                    }
                    if (item.url) {
                        window.location.href = item.url;
                    } else {
                        this.load();
                    }
                },
                async readAll() {
                    try {
                        const res = await post(readAllUrl);
                        if (!res.ok) return;
                        this.items = this.items.map((item) => ({ ...item, is_new: false }));
                        this.unread = 0;
                    } catch (e) {}
                },
            };
        }

        function onlineOrderBell(listUrl, seenUrl) {
            return {
                panelOpen: false,
                loading: false,
                loaded: false,
                unread: 0,
                items: [],
                error: '',
                init() {
                    this.panelOpen = false;
                    this.fetchBadge();
                    setInterval(() => this.fetchBadge(), 60000);
                },
                togglePanel(event) {
                    if (event) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    this.panelOpen = !this.panelOpen;
                    if (this.panelOpen) {
                        this.fetchList(true);
                    } else {
                        this.markSeen();
                    }
                },
                closePanel() {
                    if (!this.panelOpen) return;
                    this.panelOpen = false;
                    this.markSeen();
                },
                async openItem(item, event) {
                    if (event) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    // Clear badge before navigating so it does not stick after click-through.
                    await this.markSeen(true);
                    window.location.href = item.url;
                },
                async fetchBadge() {
                    try {
                        const res = await fetch(listUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.unread = Number(data.unread || 0);
                        this.error = '';
                    } catch (e) {
                        // Keep last known unread; avoid silently forcing 0 on network blips.
                    }
                },
                async fetchList(force = false) {
                    if (this.loading || (this.loaded && !force)) return;
                    this.loading = true;
                    this.error = '';
                    try {
                        const res = await fetch(listUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) {
                            this.error = 'Could not load notifications.';
                            return;
                        }
                        const data = await res.json();
                        this.items = data.items || [];
                        this.unread = Number(data.unread || 0);
                        this.loaded = true;
                    } catch (e) {
                        this.error = 'Could not load notifications.';
                    } finally {
                        this.loading = false;
                    }
                },
                async markSeen(force = false) {
                    if (!force && this.unread <= 0 && !this.items.some((item) => item.is_new)) {
                        return;
                    }
                    try {
                        const res = await fetch(seenUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            credentials: 'same-origin',
                            keepalive: true,
                        });
                        if (!res.ok) return;
                        this.items = this.items.map((item) => ({ ...item, is_new: false }));
                        this.unread = 0;
                    } catch (e) {}
                },
            };
        }

        setInterval(function () {
            if (typeof window.refreshCsrfToken === 'function') {
                window.refreshCsrfToken();
            } else {
                fetch('/refresh-session', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).catch(function () {});
            }
        }, 10 * 60 * 1000);

        window.addEventListener('pageshow', function () {
            if (typeof window.refreshCsrfToken === 'function') {
                window.refreshCsrfToken();
            }
        });


        /** Open POS in a large counter window (not a tiny popup/tab). */
        window.launchPosTerminal = function (url) {
            url = url || @json(route('pos.index'));
            var w = screen.availWidth || screen.width || 1280;
            var h = screen.availHeight || screen.height || 800;
            var features = 'popup=yes,width=' + w + ',height=' + h + ',left=0,top=0,resizable=yes,scrollbars=yes';
            var win = window.open(url, 'nexa_pos_terminal', features);
            if (!win) {
                window.location.href = url;
                return false;
            }
            try {
                win.focus();
                win.moveTo(0, 0);
                win.resizeTo(w, h);
            } catch (e) {}
            return false;
        };
    </script>
</body>
</html>
