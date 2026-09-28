<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In · {{ data_get($settings ?? null, 'store_name') ?: config('app.name', 'Maks Gadget') }}</title>
    @include('partials.favicon', ['settings' => $settings ?? null])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />
    {{-- CSS only: app.js CSRF refresh races with form POST on php artisan serve (single-threaded) and causes 419 Page Expired. --}}
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --neon: #22d3ee;
            --neon-dim: rgba(34, 211, 238, 0.35);
            --ink: #e8eef7;
            --muted: #8b9bb4;
            --panel: #0a101c;
        }
        .neon-login {
            min-height: 100vh;
            margin: 0;
            font-family: "Manrope", ui-sans-serif, system-ui, sans-serif;
            color: var(--ink);
            background: #020617;
            -webkit-font-smoothing: antialiased;
        }
        .neon-login * { box-sizing: border-box; }

        .neon-stage {
            position: relative;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px 14px;
        }
        .neon-bg {
            position: absolute;
            inset: 0;
            overflow: hidden;
            background:
                radial-gradient(700px 380px at 20% 15%, rgba(34, 211, 238, 0.12), transparent 60%),
                radial-gradient(640px 360px at 80% 85%, rgba(59, 130, 246, 0.1), transparent 55%),
                #020617;
        }
        .neon-bg__grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: radial-gradient(ellipse 70% 60% at 50% 45%, #000 20%, transparent 75%);
            animation: neon-grid 20s linear infinite;
        }

        .neon-panel-wrap {
            position: relative;
            z-index: 1;
            width: min(100%, 340px);
            animation: neon-in 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        /* Compact embossed neon card */
        .neon-panel {
            border-radius: 18px;
            padding: 1px;
            background: linear-gradient(145deg, rgba(34, 211, 238, 0.7), rgba(30, 41, 59, 0.9) 42%, rgba(34, 211, 238, 0.35));
            box-shadow:
                0 18px 50px rgba(0, 0, 0, 0.45),
                0 0 28px rgba(34, 211, 238, 0.12);
        }
        .neon-panel__inner {
            border-radius: 17px;
            padding: 22px 20px 18px;
            background:
                linear-gradient(180deg, #101827 0%, #0a101c 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.07),
                inset 0 -12px 28px rgba(0, 0, 0, 0.28),
                inset 0 10px 22px rgba(34, 211, 238, 0.03);
        }

        .neon-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .neon-brand__mark {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            background: linear-gradient(160deg, rgba(34, 211, 238, 0.16), rgba(15, 23, 42, 0.95));
            border: 1px solid rgba(34, 211, 238, 0.4);
            box-shadow:
                0 0 16px rgba(34, 211, 238, 0.22),
                inset 0 1px 0 rgba(255, 255, 255, 0.12),
                inset 0 -6px 12px rgba(0, 0, 0, 0.4);
        }
        .neon-brand__mark img {
            width: 24px;
            height: 24px;
            object-fit: contain;
            display: block;
        }
        .neon-brand__mark svg {
            width: 18px;
            height: 18px;
            color: var(--neon);
            filter: drop-shadow(0 0 6px rgba(34, 211, 238, 0.7));
        }
        .neon-brand__meta { min-width: 0; }
        .neon-brand__name {
            margin: 0;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.01em;
            color: #f8fafc;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .neon-brand__sub {
            margin: 2px 0 0;
            font-size: 11.5px;
            font-weight: 500;
            color: var(--muted);
        }

        .neon-title {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #f8fafc;
        }
        .neon-lead {
            margin: 0 0 16px;
            font-size: 12.5px;
            font-weight: 500;
            line-height: 1.45;
            color: var(--muted);
        }

        .neon-alert {
            margin-bottom: 12px;
            border-radius: 10px;
            padding: 9px 11px;
            border: 1px solid rgba(244, 63, 94, 0.4);
            background: rgba(127, 29, 29, 0.28);
            color: #fecdd3;
            font-size: 12px;
            line-height: 1.4;
        }
        .neon-alert strong {
            display: block;
            color: #fff;
            margin-bottom: 1px;
            font-size: 12.5px;
        }
        .neon-status {
            margin-bottom: 12px;
            border-radius: 10px;
            padding: 9px 11px;
            border: 1px solid rgba(34, 211, 238, 0.3);
            background: rgba(8, 47, 73, 0.4);
            color: #a5f3fc;
            text-align: center;
            font-size: 12px;
        }

        .neon-form { display: grid; gap: 12px; }
        .neon-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 5px;
        }
        .neon-label {
            display: block;
            margin-bottom: 5px;
            font-size: 11.5px;
            font-weight: 700;
            color: #cbd5e1;
        }
        .neon-label-row .neon-label { margin-bottom: 0; }
        .neon-forgot {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--neon);
            text-decoration: none;
        }
        .neon-forgot:hover { color: #67e8f9; }

        .neon-field { position: relative; }
        .neon-field input {
            width: 100%;
            height: 42px;
            border-radius: 10px;
            border: 1px solid rgba(34, 211, 238, 0.18);
            background: linear-gradient(180deg, #070b14, #0c1320);
            color: #f8fafc;
            padding: 0 38px 0 12px;
            font-size: 13px;
            font-family: inherit;
            font-weight: 500;
            outline: none;
            box-shadow:
                inset 0 2px 6px rgba(0, 0, 0, 0.5),
                inset 0 -1px 0 rgba(255, 255, 255, 0.04);
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .neon-field input::placeholder { color: #64748b; }
        .neon-field input:focus {
            border-color: rgba(34, 211, 238, 0.65);
            box-shadow:
                inset 0 2px 6px rgba(0, 0, 0, 0.5),
                0 0 0 3px rgba(34, 211, 238, 0.12),
                0 0 16px rgba(34, 211, 238, 0.12);
        }
        .neon-field__icon {
            position: absolute;
            right: 11px;
            top: 50%;
            transform: translateY(-50%);
            width: 15px;
            height: 15px;
            color: #64748b;
            pointer-events: none;
        }
        .neon-field input:focus + .neon-field__icon {
            color: var(--neon);
        }

        .neon-check {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 12.5px;
            font-weight: 500;
            user-select: none;
        }
        .neon-check input {
            width: 14px;
            height: 14px;
            accent-color: var(--neon);
            cursor: pointer;
        }

        .neon-submit {
            width: 100%;
            height: 42px;
            border: 0;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            color: #041016;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            background: linear-gradient(135deg, #67e8f9, #22d3ee 55%, #38bdf8);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.28),
                0 8px 20px rgba(34, 211, 238, 0.28);
            transition: transform .15s ease, filter .15s ease;
        }
        .neon-submit:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }
        .neon-submit:active { transform: none; }
        .neon-submit svg { width: 15px; height: 15px; }

        .neon-foot {
            margin: 14px 0 0;
            text-align: center;
            color: #64748b;
            font-size: 11px;
            line-height: 1.4;
        }
        .neon-foot strong { color: #94a3b8; font-weight: 700; }
        .neon-foot__link { color: inherit; text-decoration: none; }
        .neon-foot__link:hover strong { color: #22d3ee; }

        @keyframes neon-grid {
            from { background-position: 0 0, 0 0; }
            to { background-position: 0 40px, 40px 0; }
        }
        @keyframes neon-in {
            from { opacity: 0; transform: translateY(14px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @media (prefers-reduced-motion: reduce) {
            .neon-bg__grid, .neon-panel-wrap { animation: none !important; }
        }
    </style>
</head>
<body class="neon-login antialiased">
    {{ $slot }}
</body>
</html>
