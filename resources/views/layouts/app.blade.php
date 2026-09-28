<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Maks Gadget') }}</title>
    @include('partials.favicon')

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        .powered-by-admin{
            flex-shrink:0;
            padding:10px 16px 14px;
            text-align:center;
            font-size:11px;
            font-weight:500;
            letter-spacing:.03em;
            color:#64748b;
            border-top:1px solid #e2e8f0;
            background:rgba(255,255,255,.85);
        }
        .powered-by-admin strong{color:#0f172a;font-weight:800}
        .powered-by-admin a,.powered-by-link{color:inherit;text-decoration:none}
        .powered-by-admin a:hover,.powered-by-admin .powered-by-link:hover{color:#2563eb;text-decoration:underline}

        /* ── Admin sidebar ── */
        :root{
            --sb-bg:#0b1017;
            --sb-line:rgba(255,255,255,.055);
            --sb-text:#9aa8bc;
            --sb-text-strong:#f1f5f9;
            --sb-muted:#64748b;
            --sb-width:240px;
            --sb-rail:68px;
            --sb-top:3.25rem
        }
        .admin-sidebar{
            position:relative;z-index:50;
            display:flex;flex-direction:column;
            width:var(--sb-width);min-width:var(--sb-width);max-width:var(--sb-width);
            flex:0 0 var(--sb-width);height:100vh;
            background:
                radial-gradient(120% 60% at 0% 0%, rgba(59,130,246,.08), transparent 55%),
                var(--sb-bg);
            border-right:1px solid var(--sb-line);
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
            width:26px;height:26px;border-radius:7px;border:1px solid var(--sb-line);
            color:var(--sb-muted);background:rgba(255,255,255,.02);
            transition:color .15s ease,background .15s ease,border-color .15s ease,transform .2s ease
        }
        .sidebar-collapse-btn:hover{
            color:var(--sb-text-strong);background:rgba(255,255,255,.06);
            border-color:rgba(255,255,255,.12);transform:scale(1.05)
        }
        .admin-topbar{
            position:fixed;top:0;right:0;left:var(--sb-width);height:var(--sb-top);z-index:30;
            display:flex;align-items:center;gap:.75rem;padding:0 1.15rem;
            background:rgba(255,255,255,.94);backdrop-filter:blur(12px);
            border-bottom:1px solid #e8eef5;transition:left .22s ease
        }
        .admin-topbar.is-rail{left:var(--sb-rail)}

        @media (max-width:767px){
            .admin-sidebar{
                position:fixed;top:0;left:0;bottom:0;height:auto;
                width:min(288px,88vw);min-width:0;max-width:min(288px,88vw);flex:none;
                transform:translateX(-105%);transition:transform .26s ease;
                box-shadow:16px 0 48px rgba(0,0,0,.4)
            }
            .admin-sidebar.is-collapsed{width:min(288px,88vw);min-width:0;max-width:min(288px,88vw);flex:none}
            .admin-sidebar.is-mobile-open{transform:translateX(0)}
            .admin-sidebar-backdrop{
                display:none;position:fixed;inset:0;z-index:40;
                background:rgba(8,12,18,.55);backdrop-filter:blur(2px)
            }
            .admin-sidebar-backdrop.is-visible{display:block}
            .admin-mobile-menu-btn{display:inline-flex}
            .admin-sidebar-close{display:inline-flex}
            .sidebar-collapse-btn{display:none !important}
            .admin-topbar{left:0;padding:0 1rem}
            .admin-topbar.is-rail{left:0}
        }

        .admin-sidebar .sidebar-head{
            height:var(--sb-top);padding:0 .75rem 0 .85rem;border-bottom:1px solid var(--sb-line)
        }
        .sidebar-brand-link{color:var(--sb-text-strong) !important;text-decoration:none}
        .sidebar-brand-text{font-size:13px;font-weight:600;letter-spacing:-.02em;line-height:1.2}
        .sidebar-brand-mark{border-radius:8px;box-shadow:0 0 0 1px rgba(255,255,255,.08)}
        .admin-sidebar-nav{
            padding:.45rem .55rem 1.25rem;
            scrollbar-width:thin;scrollbar-color:rgba(148,163,184,.2) transparent
        }
        .nav-divider{height:1px;margin:.55rem .4rem .5rem;background:var(--sb-line)}

        .nav-link,.nav-group{
            display:flex;align-items:center;width:100%;gap:9px;
            padding:5px 7px;margin:1px 0;border-radius:9px;
            font-size:12.5px;font-weight:500;letter-spacing:-.01em;
            color:var(--sb-text);text-align:left;background:transparent;border:0;
            cursor:pointer;text-decoration:none;line-height:1.2;
            transition:background .18s ease,color .18s ease,transform .18s ease,box-shadow .18s ease
        }
        .nav-group{justify-content:space-between}
        .nav-group-main{display:flex;align-items:center;gap:9px;min-width:0}
        .nav-label{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .nav-link:hover,.nav-group:hover{
            background:rgba(255,255,255,.045);color:var(--sb-text-strong)
        }
        .nav-link--badge{justify-content:flex-start}
        .nav-link--badge .nav-label{flex:1;min-width:0}

        .nav-ico-wrap{
            width:28px;height:28px;flex-shrink:0;border-radius:8px;
            display:inline-flex;align-items:center;justify-content:center;
            background:rgba(255,255,255,.04);color:inherit;
            transition:transform .22s cubic-bezier(.34,1.56,.64,1),background .18s ease,box-shadow .18s ease,color .18s ease
        }
        .nav-ico{width:15px;height:15px;opacity:.9;stroke-width:1.7}
        .nav-link:hover .nav-ico-wrap,.nav-group:hover .nav-ico-wrap{
            transform:translateY(-1px) scale(1.06)
        }
        .nav-link.is-active .nav-ico-wrap,.nav-group.is-open .nav-ico-wrap{
            animation:navIcoPop .42s cubic-bezier(.34,1.45,.64,1) both;
            box-shadow:0 0 0 1px rgba(255,255,255,.08),0 4px 12px rgba(0,0,0,.2)
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

        /* Active / open — strong color highlight per feature */
        .nav-link.is-active,.nav-group.is-open{font-weight:600;color:var(--sb-text-strong)}
        .nav-tone-dash .nav-ico-wrap{color:#38bdf8;background:rgba(56,189,248,.14)}
        .nav-tone-dash.is-active{background:linear-gradient(90deg,rgba(56,189,248,.2),rgba(56,189,248,.05));box-shadow:inset 2px 0 0 #38bdf8;color:#e0f2fe}
        .nav-tone-sales .nav-ico-wrap{color:#34d399;background:rgba(52,211,153,.14)}
        .nav-tone-sales.is-active{background:linear-gradient(90deg,rgba(52,211,153,.2),rgba(52,211,153,.05));box-shadow:inset 2px 0 0 #34d399;color:#d1fae5}
        .nav-tone-orders .nav-ico-wrap{color:#a78bfa;background:rgba(167,139,250,.14)}
        .nav-tone-orders.is-active{background:linear-gradient(90deg,rgba(167,139,250,.22),rgba(167,139,250,.05));box-shadow:inset 2px 0 0 #a78bfa;color:#ede9fe}
        .nav-tone-products .nav-ico-wrap{color:#818cf8;background:rgba(129,140,248,.14)}
        .nav-tone-products.is-active{background:linear-gradient(90deg,rgba(129,140,248,.22),rgba(129,140,248,.05));box-shadow:inset 2px 0 0 #818cf8;color:#e0e7ff}
        .nav-tone-customers .nav-ico-wrap{color:#22d3ee;background:rgba(34,211,238,.14)}
        .nav-tone-customers.is-active{background:linear-gradient(90deg,rgba(34,211,238,.2),rgba(34,211,238,.05));box-shadow:inset 2px 0 0 #22d3ee;color:#cffafe}
        .nav-tone-accounts .nav-ico-wrap{color:#2dd4bf;background:rgba(45,212,191,.14)}
        .nav-tone-accounts.is-active{background:linear-gradient(90deg,rgba(45,212,191,.2),rgba(45,212,191,.05));box-shadow:inset 2px 0 0 #2dd4bf;color:#ccfbf1}
        .nav-tone-alert .nav-ico-wrap{color:#fb7185;background:rgba(251,113,133,.14)}
        .nav-tone-alert.is-active{background:linear-gradient(90deg,rgba(251,113,133,.22),rgba(251,113,133,.05));box-shadow:inset 2px 0 0 #fb7185;color:#ffe4e6}
        .nav-tone-alert.is-active .nav-ico-wrap{animation:navIcoPulse 1.6s ease-in-out infinite}
        @keyframes navIcoPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.08)}}
        .nav-tone-cash .nav-ico-wrap{color:#fbbf24;background:rgba(251,191,36,.14)}
        .nav-tone-cash.is-active{background:linear-gradient(90deg,rgba(251,191,36,.2),rgba(251,191,36,.05));box-shadow:inset 2px 0 0 #fbbf24;color:#fef3c7}
        .nav-tone-catalog .nav-ico-wrap{color:#c084fc;background:rgba(192,132,252,.14)}
        .nav-tone-catalog.is-open{background:linear-gradient(90deg,rgba(192,132,252,.16),transparent);box-shadow:inset 2px 0 0 #c084fc}
        .nav-tone-inventory .nav-ico-wrap{color:#fb923c;background:rgba(251,146,60,.14)}
        .nav-tone-inventory.is-open{background:linear-gradient(90deg,rgba(251,146,60,.16),transparent);box-shadow:inset 2px 0 0 #fb923c}
        .nav-tone-credit .nav-ico-wrap{color:#a3e635;background:rgba(163,230,53,.12)}
        .nav-tone-credit.is-open{background:linear-gradient(90deg,rgba(163,230,53,.14),transparent);box-shadow:inset 2px 0 0 #a3e635}
        .nav-tone-reports .nav-ico-wrap{color:#60a5fa;background:rgba(96,165,250,.14)}
        .nav-tone-reports.is-open{background:linear-gradient(90deg,rgba(96,165,250,.16),transparent);box-shadow:inset 2px 0 0 #60a5fa}
        .nav-tone-web .nav-ico-wrap{color:#f472b6;background:rgba(244,114,182,.14)}
        .nav-tone-web.is-open{background:linear-gradient(90deg,rgba(244,114,182,.16),transparent);box-shadow:inset 2px 0 0 #f472b6}
        .nav-tone-team .nav-ico-wrap{color:#94a3b8;background:rgba(148,163,184,.12)}
        .nav-tone-team.is-open{background:linear-gradient(90deg,rgba(148,163,184,.14),transparent);box-shadow:inset 2px 0 0 #94a3b8}

        .nav-chevron{
            width:11px;height:11px;flex-shrink:0;opacity:.32;
            transition:transform .18s ease,opacity .12s ease
        }
        .nav-group:hover .nav-chevron,.nav-group.is-open .nav-chevron{opacity:.65}

        .nav-submenu{
            margin:0 0 5px 12px;padding:2px 0 2px 8px;
            border-left:1px solid var(--sb-line);
            display:flex;flex-direction:column;gap:1px
        }
        .nav-sub{
            display:flex;align-items:center;gap:7px;
            padding:5px 8px;border-radius:7px;
            font-size:12px;font-weight:450;color:#7c8a9e;
            transition:background .14s ease,color .14s ease,transform .14s ease;
            text-decoration:none;letter-spacing:-.01em;line-height:1.2
        }
        .nav-sub:hover{background:rgba(255,255,255,.04);color:var(--sb-text-strong);transform:translateX(2px)}
        .nav-sub.is-active{
            background:rgba(255,255,255,.06);color:#e2e8f0;font-weight:600
        }
        .nav-sub-ico{
            width:14px;height:14px;flex-shrink:0;opacity:.55;stroke-width:1.7
        }
        .nav-sub:hover .nav-sub-ico{opacity:.9}
        .nav-sub.is-active .nav-sub-ico{opacity:1;color:#93c5fd}
        .nav-badge{
            display:inline-flex;align-items:center;justify-content:center;
            min-width:16px;height:16px;padding:0 4px;margin-left:auto;
            border-radius:999px;background:#e11d48;color:#fff;
            font-size:9px;font-weight:700;line-height:1;
            animation:navBadgePop .5s ease both
        }
        @keyframes navBadgePop{0%{transform:scale(0)}70%{transform:scale(1.15)}100%{transform:scale(1)}}

        /* Collapsed rail */
        .admin-sidebar.is-collapsed .nav-label,
        .admin-sidebar.is-collapsed .nav-chevron,
        .admin-sidebar.is-collapsed .nav-badge,
        .admin-sidebar.is-collapsed .nav-divider{display:none !important}
        .admin-sidebar.is-collapsed .sidebar-brand-link{justify-content:center;flex:0 0 auto !important;width:100%}
        .admin-sidebar.is-collapsed .sidebar-brand-mark{width:1.75rem !important;height:1.75rem !important}
        .admin-sidebar.is-collapsed .nav-link,
        .admin-sidebar.is-collapsed .nav-group{
            justify-content:center !important;padding:6px 0 !important;gap:0 !important;background:transparent !important;box-shadow:none !important
        }
        .admin-sidebar.is-collapsed .nav-link.is-active,
        .admin-sidebar.is-collapsed .nav-group.is-open{background:rgba(255,255,255,.04) !important}
        .admin-sidebar.is-collapsed .nav-group-main{justify-content:center;width:100%;gap:0}
        .admin-sidebar.is-collapsed .nav-ico-wrap{width:32px;height:32px}
        .admin-sidebar.is-collapsed .sidebar-head{
            flex-direction:column;align-items:center;justify-content:center;
            height:auto !important;min-height:var(--sb-top);padding:.65rem .3rem .5rem;gap:.35rem
        }
        .admin-sidebar.is-collapsed .admin-sidebar-nav{padding:.35rem .28rem 1rem}
        .admin-sidebar.is-collapsed .nav-submenu{display:none !important}
    </style>
</head>
<body class="admin-panel font-sans antialiased text-slate-900 bg-[#F4F6FB]">
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
