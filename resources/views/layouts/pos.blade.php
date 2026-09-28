<!DOCTYPE html>
<html lang="en" class="h-full overflow-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base" content="{{ rtrim(request()->getBasePath(), '/') }}">
    <title>POS Terminal — {{ config('app.name', 'Maks Gadget') }}</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html, body { height: 100%; width: 100%; margin: 0; overflow: hidden; }
        .powered-by-fixed{
            position:fixed;
            right:10px;
            bottom:8px;
            z-index:40;
            pointer-events:auto;
            font-size:10px;
            font-weight:600;
            letter-spacing:.04em;
            color:#94a3b8;
            background:rgba(255,255,255,.82);
            border:1px solid #e2e8f0;
            border-radius:999px;
            padding:4px 10px;
            box-shadow:0 4px 12px rgba(15,23,42,.06);
        }
        .powered-by-fixed strong{color:#0f172a;font-weight:800}
        .powered-by-fixed a,.powered-by-fixed .powered-by-link{color:inherit;text-decoration:none}
        .powered-by-fixed a:hover{color:#2563eb;text-decoration:underline}
        @media print { .powered-by-fixed { display:none !important; } }
    </style>
</head>
<body class="h-full overflow-hidden font-sans antialiased text-gray-900 bg-slate-100">
    @php
        $adminFlash = array_filter([
            'success' => session('success'),
            'error' => session('error'),
            'warning' => session('warning'),
            'info' => session('info'),
        ], fn ($v) => filled($v));
    @endphp
    <script>window.__ADMIN_FLASH__ = @json($adminFlash);</script>
    {{ $slot }}
    @include('partials.powered-by', ['variant' => 'fixed'])
    <script>
        setInterval(function () {
            fetch(@json(url('/refresh-session')), { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(function () {});
        }, 15 * 60 * 1000);

        // Keep counter window as large as the OS allows
        (function expandPosWindow() {
            try {
                if (window.name === 'nexa_pos_terminal') {
                    window.moveTo(0, 0);
                    window.resizeTo(screen.availWidth || screen.width, screen.availHeight || screen.height);
                }
            } catch (e) {}
        })();
    </script>
</body>
</html>