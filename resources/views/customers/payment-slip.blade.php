<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $slipTitle }} — {{ $slipRef }}</title>
    @php
        $tz = config('app.display_timezone', config('app.timezone', 'Asia/Dhaka'));
        $paidAt = $entry->created_at?->copy()->timezone($tz);
        $printedAt = now()->timezone($tz);
        $shop = $entry->shop ?? Auth::user()->shop ?? null;
        $shopName = $shop->name ?? config('app.name', 'Maks Gadget');
        $paidAmount = abs((float) $entry->amount);
        $method = strtoupper(str_replace('_', ' ', (string) ($entry->method ?? 'cash')));
        $cashier = $entry->user->name ?? '—';
        $customer = $entry->customer;
    @endphp
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #cbd5e1;
            --soft: #f8fafc;
            --green: #15803d;
        }
        * { box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: var(--ink);
            margin: 0;
            padding: 0;
            background: {{ request()->boolean('embed') ? '#fff' : '#e2e8f0' }};
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .sheet {
            position: relative;
            width: 80mm;
            max-width: 80mm;
            margin: {{ request()->boolean('embed') ? '0 auto' : '12px auto' }};
            padding: 14px 12px 16px;
            background: #fff;
            border: {{ request()->boolean('embed') ? '0' : '1px solid var(--line)' }};
            box-shadow: {{ request()->boolean('embed') ? 'none' : '0 8px 24px rgba(15, 23, 42, .08)' }};
            overflow: hidden;
        }
        .brand {
            text-align: center;
            border-bottom: 2px solid var(--ink);
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .brand .doc-type {
            display: inline-block;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .brand h1 {
            margin: 0;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: .02em;
            line-height: 1.2;
        }
        .brand .tagline {
            margin: 4px 0 0;
            font-size: 10px;
            color: var(--muted);
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 11px;
        }
        .meta td { padding: 2px 0; vertical-align: top; }
        .meta .lbl { color: var(--muted); width: 40%; font-weight: 600; }
        .meta .val { font-weight: 700; text-align: right; word-break: break-word; }
        .party {
            background: var(--soft);
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 8px 9px;
            margin-bottom: 10px;
        }
        .party .eyebrow {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .party .name { font-weight: 800; font-size: 12px; margin: 0 0 2px; }
        .party p { margin: 1px 0; line-height: 1.35; }
        .amount-box {
            text-align: center;
            border: 2px solid var(--green);
            border-radius: 10px;
            padding: 12px 10px;
            margin: 12px 0;
            background: #f0fdf4;
        }
        .amount-box .lbl {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--green);
        }
        .amount-box .val {
            margin-top: 4px;
            font-size: 26px;
            font-weight: 900;
            color: var(--green);
            line-height: 1.1;
        }
        .rows {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .rows td {
            padding: 4px 0;
            font-size: 11px;
            border-bottom: 1px dotted #e2e8f0;
        }
        .rows tr:last-child td { border-bottom: 0; }
        .rows .lbl { color: var(--muted); }
        .rows .val { text-align: right; font-weight: 700; }
        .paid-seal {
            position: absolute;
            top: 48%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-18deg);
            border: 5px solid #16a34a;
            color: #16a34a;
            font-size: 46px;
            font-weight: 900;
            letter-spacing: .14em;
            padding: 6px 16px;
            border-radius: 14px;
            opacity: .22;
            pointer-events: none;
            z-index: 5;
            text-transform: uppercase;
            white-space: nowrap;
            box-shadow: inset 0 0 0 2px rgba(22, 163, 74, .35);
        }
        .badge-paid {
            display: block;
            text-align: center;
            font-weight: 900;
            font-size: 14px;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: #15803d;
            border: 3px solid #16a34a;
            border-radius: 8px;
            padding: 8px 6px;
            margin: 8px 0 12px;
            background: #f0fdf4;
        }
        .footer {
            text-align: center;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 2px solid var(--ink);
        }
        .footer .thanks { font-weight: 800; font-size: 12px; margin: 0 0 4px; }
        .footer .note { margin: 0; font-size: 10px; color: var(--muted); line-height: 1.4; }
        .footer .stamp { margin-top: 8px; font-size: 9px; color: var(--muted); }
        .no-print {
            text-align: center;
            padding: 12px;
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .no-print a, .no-print button {
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
        }
        .no-print button.primary {
            background: #16a34a;
            border-color: #16a34a;
            color: #fff;
        }
        @media print {
            body { background: #fff; }
            .sheet {
                width: 80mm;
                max-width: 100%;
                margin: 0;
                padding: 0;
                border: 0;
                box-shadow: none;
            }
            .no-print { display: none !important; }
            @page { margin: 4mm; size: auto; }
        }
    </style>
</head>
<body @if(request()->boolean('print') && ! request()->boolean('embed')) onload="setTimeout(function(){ window.focus(); window.print(); }, 200)" @endif>
    @unless(request()->boolean('embed'))
    <div class="no-print">
        <button type="button" class="primary" onclick="window.print()">Print slip</button>
        @if(!empty($backUrl))
            <a href="{{ $backUrl }}">Back</a>
        @endif
    </div>
    @endunless

    <div class="sheet">
        <div class="paid-seal" aria-hidden="true">PAID</div>

        <header class="brand">
            <div class="doc-type">{{ $slipTitle }}</div>
            <h1>{{ $shopName }}</h1>
            <p class="tagline">Payment receipt / acknowledgment slip</p>
        </header>

        <div class="badge-paid">*** PAID ***</div>

        <table class="meta">
            <tr>
                <td class="lbl">Slip No</td>
                <td class="val">{{ $slipRef }}</td>
            </tr>
            <tr>
                <td class="lbl">Date</td>
                <td class="val">{{ $paidAt?->format('d M Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Time</td>
                <td class="val">{{ $paidAt?->format('h:i A') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Received by</td>
                <td class="val">{{ $cashier }}</td>
            </tr>
        </table>

        <section class="party">
            <div class="eyebrow">Customer</div>
            <p class="name">{{ $customer->name ?? 'Customer' }}</p>
            @if($customer?->phone)
                <p>Phone: {{ $customer->phone }}</p>
            @endif
        </section>

        <div class="amount-box">
            <div class="lbl">Amount received</div>
            <div class="val">{{ format_taka($paidAmount) }}</div>
        </div>

        <table class="rows">
            <tr>
                <td class="lbl">Payment method</td>
                <td class="val">{{ $method }}</td>
            </tr>
            <tr>
                <td class="lbl">Type</td>
                <td class="val">{{ $slipTypeLabel }}</td>
            </tr>
            @if(!empty($invoiceNo))
                <tr>
                    <td class="lbl">Invoice</td>
                    <td class="val">{{ $invoiceNo }}</td>
                </tr>
            @endif
            @if(!empty($extraRows) && is_array($extraRows))
                @foreach($extraRows as $row)
                    <tr>
                        <td class="lbl">{{ $row['label'] }}</td>
                        <td class="val">{{ $row['value'] }}</td>
                    </tr>
                @endforeach
            @endif
            <tr>
                <td class="lbl">Outstanding after</td>
                <td class="val">{{ format_taka((float) $remainingAfter) }}</td>
            </tr>
            @if($entry->note)
                <tr>
                    <td class="lbl">Note</td>
                    <td class="val" style="font-weight:600">{{ $entry->note }}</td>
                </tr>
            @endif
        </table>

        <footer class="footer">
            <p class="thanks">Payment received — thank you</p>
            <p class="note">Please keep this slip with your invoice records.</p>
            <p class="stamp">Printed: {{ $printedAt->format('d M Y, h:i A') }}</p>
            @include('partials.powered-by', ['variant' => 'print'])
        </footer>
    </div>
</body>
</html>
