<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In · {{ data_get($settings ?? null, 'store_name') ?: config('app.name', 'Bynnas Social') }}</title>
    @include('partials.favicon', ['settings' => $settings ?? null])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- CSS only: app.js CSRF refresh races with form POST on php artisan serve (single-threaded) and causes 419 Page Expired. --}}
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --bl-cream: #fffaf5;
            --bl-peach: #fcebdd;
            --bl-coral: #ec8560;
            --bl-coral-deep: #b84f2c;
            --bl-coral-edge: #8a3a20;
            --bl-purple: #8b6fd6;
            --bl-purple-deep: #6a4bb8;
            --bl-lavender: #efe9fc;
            --bl-sun: #f4b740;
            --bl-ink: #3a2a24;
            --bl-muted: #654f45;
            --bl-line: #f2e6dc;
            --bl-display: 'Fredoka', 'Nunito', system-ui, sans-serif;
        }
        .bl-body {
            min-height: 100vh;
            margin: 0;
            font-family: 'Nunito', ui-sans-serif, system-ui, sans-serif;
            color: var(--bl-ink);
            background-color: var(--bl-cream);
            background-image:
                radial-gradient(circle at 12px 12px, rgba(236, 133, 96, .12) 2.5px, transparent 3px),
                radial-gradient(circle at 46px 40px, rgba(139, 111, 214, .12) 2.5px, transparent 3px),
                radial-gradient(circle at 70px 10px, rgba(143, 163, 126, .14) 2px, transparent 2.5px);
            background-size: 84px 64px;
            -webkit-font-smoothing: antialiased;
        }
        .bl-body * { box-sizing: border-box; }

        .bl-stage {
            position: relative;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 28px 16px;
            overflow: hidden;
        }
        .bl-blob { position: absolute; border-radius: 50%; filter: blur(2px); pointer-events: none; }
        .bl-blob--a { width: 340px; height: 340px; left: -120px; top: -110px; background: radial-gradient(circle, #fde1d3 0%, rgba(253, 225, 211, 0) 70%); }
        .bl-blob--b { width: 380px; height: 380px; right: -140px; bottom: -150px; background: radial-gradient(circle, #e7ddfb 0%, rgba(231, 221, 251, 0) 70%); }

        .bl-card {
            position: relative;
            z-index: 1;
            width: min(100%, 820px);
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-radius: 28px;
            overflow: hidden;
            background: #fff;
            border: 2px solid #f6e7dc;
            box-shadow: 0 7px 0 #f3dccd, 0 28px 60px rgba(214, 150, 118, .18);
            animation: bl-in .55s cubic-bezier(.22, 1, .36, 1) both;
        }

        /* Brand side */
        .bl-side {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 28px;
            padding: 30px 30px 26px;
            background:
                radial-gradient(circle at 18px 18px, rgba(255, 255, 255, .55) 3px, transparent 3.5px) 0 0 / 36px 36px,
                linear-gradient(150deg, #fde7da 0%, #fbdccd 45%, #efe4fb 100%);
        }
        .bl-brand { display: flex; align-items: center; gap: 11px; }
        .bl-brand__mark {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 4px 0 #f1cdb9;
            overflow: hidden;
            font-family: var(--bl-display);
            font-size: 20px;
            font-weight: 700;
            color: var(--bl-coral-deep);
        }
        .bl-brand__mark img { width: 30px; height: 30px; object-fit: contain; display: block; }
        .bl-brand__name {
            margin: 0;
            font-family: var(--bl-display);
            font-size: 17px;
            font-weight: 600;
            line-height: 1.15;
            color: var(--bl-ink);
        }
        .bl-brand__sub { margin: 2px 0 0; font-size: 12px; font-weight: 600; color: #7a5a4d; }

        .bl-hello__title {
            margin: 0 0 10px;
            font-family: var(--bl-display);
            font-size: 30px;
            font-weight: 700;
            line-height: 1.1;
            color: var(--bl-ink);
        }
        .bl-hello__text { margin: 0; max-width: 30ch; font-size: 14px; font-weight: 600; line-height: 1.55; color: #7a5f53; }
        .bl-balls { display: flex; align-items: flex-end; gap: 8px; height: 34px; margin-bottom: 14px; }
        .bl-balls i {
            display: block;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: var(--bl-coral);
            box-shadow: inset -2px -2px 0 rgba(0, 0, 0, .08);
            transform-origin: 50% 100%;
            animation: bl-hop .9s cubic-bezier(.45, 0, .55, 1) infinite;
        }
        .bl-balls i:nth-child(2) { background: var(--bl-purple); animation-delay: .14s; }
        .bl-balls i:nth-child(3) { background: var(--bl-sun); animation-delay: .28s; }

        .bl-perks { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .bl-perks li {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 12.5px;
            font-weight: 700;
            color: #6f5a50;
        }
        .bl-perks span {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: rgba(255, 255, 255, .8);
            color: var(--bl-coral-deep);
        }
        .bl-perks svg { width: 13px; height: 13px; }

        /* Form side */
        .bl-main { padding: 34px 32px 26px; }
        .bl-title {
            margin: 0 0 4px;
            font-family: var(--bl-display);
            font-size: 24px;
            font-weight: 600;
            color: var(--bl-ink);
        }
        .bl-lead { margin: 0 0 20px; font-size: 13px; font-weight: 500; line-height: 1.5; color: var(--bl-muted); }

        .bl-alert,
        .bl-status {
            margin-bottom: 14px;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 12.5px;
            line-height: 1.45;
        }
        .bl-alert { border: 1px solid #f6c9c0; background: #fff1ee; color: #a2412c; }
        .bl-alert strong { display: block; margin-bottom: 1px; font-size: 13px; color: #8a2f1c; }
        .bl-status { border: 1px solid #d8ccf6; background: var(--bl-lavender); color: var(--bl-purple-deep); text-align: center; }

        .bl-form { display: grid; gap: 14px; }
        .bl-label-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
        .bl-label { display: block; margin-bottom: 6px; font-size: 12.5px; font-weight: 800; color: #5a463d; }
        .bl-label-row .bl-label { margin-bottom: 0; }
        .bl-forgot { font-size: 12px; font-weight: 700; color: #6a4bc4; text-decoration: none; }
        .bl-forgot:hover { color: var(--bl-purple-deep); text-decoration: underline; }

        .bl-field { position: relative; }
        .bl-field input {
            width: 100%;
            height: 46px;
            padding: 0 40px 0 14px;
            border: 2px solid var(--bl-line);
            border-radius: 14px;
            background: var(--bl-cream);
            color: var(--bl-ink);
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            outline: none;
            box-shadow: none;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .bl-field input::placeholder { color: #8a7468; font-weight: 500; }
        .bl-field input:focus {
            border-color: var(--bl-coral);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(236, 133, 96, .14);
        }
        .bl-field__icon {
            position: absolute;
            right: 14px;
            top: 50%;
            width: 17px;
            height: 17px;
            transform: translateY(-50%);
            color: #c6b3a8;
            pointer-events: none;
        }
        .bl-field input:focus + .bl-field__icon { color: var(--bl-coral); }

        .bl-check {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--bl-muted);
            cursor: pointer;
            user-select: none;
        }
        .bl-check input { width: 16px; height: 16px; border-radius: 5px; border-color: #e3cfc3; color: var(--bl-coral); accent-color: var(--bl-coral); cursor: pointer; }
        .bl-check input:focus { box-shadow: 0 0 0 3px rgba(236, 133, 96, .18); }

        .bl-submit {
            width: 100%;
            height: 48px;
            margin-top: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 999px;
            background: #bf5230;
            color: #fff;
            font-family: var(--bl-display);
            font-size: 16px;
            font-weight: 600;
            letter-spacing: .01em;
            cursor: pointer;
            box-shadow: 0 5px 0 var(--bl-coral-edge), 0 12px 22px rgba(236, 133, 96, .28);
            transition: transform .18s cubic-bezier(.34, 1.56, .64, 1), box-shadow .18s ease, background .18s ease;
        }
        .bl-submit:hover { background: #9e4225; transform: translateY(-2px); }
        .bl-submit:active { transform: translateY(3px); box-shadow: 0 2px 0 var(--bl-coral-edge); }
        .bl-submit:focus-visible { outline: 3px solid rgba(139, 111, 214, .45); outline-offset: 3px; }
        .bl-submit svg { width: 16px; height: 16px; }

        .bl-foot { margin: 18px 0 0; text-align: center; font-size: 11.5px; color: #6f5b52; }
        .bl-foot a { color: inherit; text-decoration: none; }
        .bl-foot strong { color: var(--bl-muted); font-weight: 800; }
        .bl-foot a:hover strong { color: #6a4bc4; }

        @keyframes bl-hop {
            0%, 100% { transform: translateY(0) scale(1.25, .75); }
            18% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-18px) scale(.92, 1.08); }
            82% { transform: translateY(0) scale(1); }
        }
        @keyframes bl-in {
            from { opacity: 0; transform: translateY(14px) scale(.98); }
            to { opacity: 1; transform: none; }
        }

        @media (max-width: 760px) {
            .bl-card { grid-template-columns: 1fr; width: min(100%, 420px); border-radius: 24px; }
            .bl-side { gap: 14px; padding: 20px 22px 18px; }
            .bl-hello__title { font-size: 22px; margin-bottom: 4px; }
            .bl-hello__text { font-size: 13px; }
            .bl-balls { display: none; }
            .bl-perks { display: none; }
            .bl-main { padding: 24px 22px 20px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .bl-card, .bl-balls i { animation: none !important; }
        }
    </style>
</head>
<body class="bl-body">
    {{ $slot }}
</body>
</html>
