<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $order->invoice_no }}</title>
    @php
        $tz = config('app.display_timezone', config('app.timezone', 'Asia/Dhaka'));
        $issuedAt = $order->created_at?->copy()->timezone($tz);
        $printedAt = now()->timezone($tz);
        $shop = $order->shop ?? Auth::user()->shop ?? null;
        $shopName = $shop->name ?? config('app.name', 'Maks Gadget');
        $isOnline = $order->isOnlineOrder();
        $paymentState = $order->receiptPaymentState();
        $isVoid = $paymentState['is_void'];
        $isFullyPaid = $paymentState['is_paid'];
        $hasOpenDue = $paymentState['is_due'];
        $dueLabel = $paymentState['due_label'];
        $amountDue = $paymentState['amount_due'];
        $amountPaid = $paymentState['amount_paid'];
        $channel = $isOnline
            ? 'Online Store'
            : ($order->counter->name ?? ($order->user->name ?? 'POS'));
        $cashier = $order->user->name ?? '—';
        $paymentLabel = strtoupper(str_replace('_', ' ', (string) ($order->payment_method ?? 'cash')));
        $itemsSubtotal = (float) $order->items->sum('subtotal');
        if ($itemsSubtotal <= 0) {
            $itemsSubtotal = max(0, (float) $order->total_amount - (float) ($order->delivery_charge ?? 0));
        }
        $creditDue = round((float) ($order->credit_amount ?? 0), 2);
        $hasOpenCredit = $creditDue > 0.009;
    @endphp
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #cbd5e1;
            --soft: #f8fafc;
        }
        * { box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: var(--ink);
            margin: 0;
            padding: 0;
            background: #e2e8f0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .sheet {
            position: relative;
            overflow: hidden;
            width: 80mm;
            max-width: 80mm;
            margin: 12px auto;
            padding: 14px 12px 16px;
            background: #fff;
            border: 1px solid var(--line);
            box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
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
        .meta td {
            padding: 2px 0;
            vertical-align: top;
        }
        .meta .lbl {
            color: var(--muted);
            width: 38%;
            font-weight: 600;
        }
        .meta .val {
            font-weight: 700;
            text-align: right;
            word-break: break-word;
        }
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
        .party p { margin: 1px 0; color: var(--ink); line-height: 1.35; }
        .badge {
            display: block;
            text-align: center;
            font-weight: 800;
            font-size: 12px;
            letter-spacing: .06em;
            text-transform: uppercase;
            border: 2px dashed var(--ink);
            padding: 6px;
            margin: 0 0 10px;
        }
        .badge.sale {
            border-color: #e11d48;
            color: #be123c;
            background: #fff1f2;
        }
        .badge.void { color: #b91c1c; border-color: #b91c1c; }
        .badge.exchange { color: #1d4ed8; border-color: #1d4ed8; }
        .badge.paid {
            color: #15803d;
            border-color: #16a34a;
            border-style: solid;
            border-width: 3px;
            background: #f0fdf4;
            font-size: 16px;
            letter-spacing: .18em;
            padding: 10px 8px;
            border-radius: 8px;
        }
        .badge.due {
            color: #b45309;
            border-color: #f59e0b;
            background: #fffbeb;
        }
        .paid-seal {
            position: absolute;
            top: 42%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-18deg);
            border: 5px solid #16a34a;
            color: #16a34a;
            font-size: 52px;
            font-weight: 900;
            letter-spacing: .14em;
            padding: 8px 18px;
            border-radius: 14px;
            opacity: .2;
            pointer-events: none;
            z-index: 8;
            text-transform: uppercase;
            white-space: nowrap;
            box-shadow: inset 0 0 0 2px rgba(22, 163, 74, .4);
        }
        .due-seal {
            position: absolute;
            top: 42%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-18deg);
            border: 5px solid #d97706;
            color: #d97706;
            font-size: 40px;
            font-weight: 900;
            letter-spacing: .1em;
            padding: 8px 14px;
            border-radius: 14px;
            opacity: .22;
            pointer-events: none;
            z-index: 8;
            text-transform: uppercase;
            white-space: nowrap;
            box-shadow: inset 0 0 0 2px rgba(217, 119, 6, .35);
        }
        .section-label {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
            margin: 8px 0 4px;
            border-bottom: 1px solid var(--line);
            padding-bottom: 3px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        table.items th {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--muted);
            border-bottom: 1.5px solid var(--ink);
            padding: 4px 0;
        }
        table.items td {
            padding: 5px 0;
            border-bottom: 1px dotted #e2e8f0;
            vertical-align: top;
            font-size: 11px;
        }
        table.items tr:last-child td { border-bottom: 0; }
        .item-name {
            font-weight: 700;
            line-height: 1.3;
        }
        .item-specs {
            margin-top: 3px;
            font-size: 9.5px;
            font-weight: 600;
            color: var(--muted);
            line-height: 1.35;
        }
        .item-specs div { margin: 0; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .totals {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            border-top: 1.5px solid var(--ink);
            padding-top: 4px;
        }
        .totals td {
            padding: 3px 0;
            font-size: 11px;
        }
        .totals .lbl { color: var(--muted); }
        .totals .grand td {
            padding-top: 7px;
            font-size: 13px;
            font-weight: 800;
            border-top: 1px dashed var(--ink);
        }
        .pay-box {
            margin-top: 8px;
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 7px 9px;
            background: var(--soft);
        }
        .pay-box table { width: 100%; border-collapse: collapse; }
        .pay-box td { padding: 2px 0; font-size: 11px; }
        .footer {
            text-align: center;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 2px solid var(--ink);
        }
        .footer .thanks {
            font-weight: 800;
            font-size: 12px;
            margin: 0 0 4px;
        }
        .footer .note {
            margin: 0;
            font-size: 10px;
            color: var(--muted);
            line-height: 1.4;
        }
        .footer .stamp {
            margin-top: 8px;
            font-size: 9px;
            color: var(--muted);
            font-family: ui-monospace, "Cascadia Mono", Consolas, monospace;
        }
        .warning-box {
            border: 2px solid var(--ink);
            padding: 8px;
            text-align: center;
            margin: 10px 0;
            border-radius: 4px;
        }
        .no-print { text-align: center; margin: 14px 0 8px; }
        .no-print button {
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 18px;
            border-radius: 8px;
            border: 0;
            background: #2563eb;
            color: #fff;
            cursor: pointer;
        }
        @media print {
            body { background: #fff !important; }
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
<body @if(request()->boolean('print')) onload="setTimeout(function(){ window.focus(); window.print(); }, 200)" @endif>
    <div class="no-print">
        <button type="button" onclick="window.print()">Print invoice</button>
    </div>

    <div class="sheet">
        @if($isFullyPaid)
            <div class="paid-seal" aria-hidden="true">PAID</div>
        @elseif($hasOpenDue)
            <div class="due-seal" aria-hidden="true">{{ $dueLabel }}</div>
        @endif

        <header class="brand">
            <div class="doc-type">{{ $isOnline ? 'Tax Invoice / Online Order' : 'Sales Invoice / POS Receipt' }}</div>
            <h1>{{ $shopName }}</h1>
            <p class="tagline">
                @if($isOnline && $hasOpenDue && $order->isCashOnDelivery())
                    COD order — payment due on delivery
                @elseif($isOnline)
                    Online order confirmation
                @else
                    Point of sale receipt
                @endif
            </p>
        </header>

        <table class="meta">
            <tr>
                <td class="lbl">Invoice No</td>
                <td class="val">{{ $order->invoice_no }}</td>
            </tr>
            <tr>
                <td class="lbl">Date</td>
                <td class="val">{{ $issuedAt?->format('d M Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Time</td>
                <td class="val">{{ $issuedAt?->format('h:i A') ?? '—' }} (BST)</td>
            </tr>
            <tr>
                <td class="lbl">Channel</td>
                <td class="val">{{ $channel }}</td>
            </tr>
            @unless($isOnline)
            <tr>
                <td class="lbl">Cashier</td>
                <td class="val">{{ $cashier }}</td>
            </tr>
            @endunless
            <tr>
                <td class="lbl">Status</td>
                <td class="val">{{ strtoupper($order->status) }}</td>
            </tr>
        </table>

        @if($order->customer)
            <section class="party">
                <div class="eyebrow">Bill To</div>
                <p class="name">{{ $order->customer->name }}</p>
                @if($order->customer->phone)
                    <p>Phone: {{ $order->customer->phone }}</p>
                @endif
                @if($order->customer->address)
                    <p>Address: {{ $order->customer->address }}</p>
                @endif
            </section>
        @else
            <section class="party">
                <div class="eyebrow">Bill To</div>
                <p class="name">Walk-in Customer</p>
            </section>
        @endif

        @if($order->includes_sale)
            <div class="badge sale">*** SALE ***</div>
        @endif

        @if($isVoid)
            <div class="badge void">*** VOID / {{ strtoupper($order->status) }} ***</div>
        @elseif($isFullyPaid)
            <div class="badge paid">*** PAID ***</div>
        @elseif($hasOpenDue)
            <div class="badge due">*** {{ $dueLabel }} ***</div>
        @endif

        @if($order->is_exchange_receipt)
            <div class="badge exchange">*** Exchange Receipt ***</div>
            <div class="section-label">Returned Items</div>
            <table class="items">
                <thead>
                    <tr>
                        <th class="text-left">Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="item-name">
                            {{ $returnProduct?->receiptDisplayName() ?? ($returnProduct->name ?? 'Returned item') }}
                            @if($returnProduct)
                                @php $returnSpecs = $returnProduct->receiptSpecLines(); @endphp
                                @if(count($returnSpecs))
                                    <div class="item-specs">
                                        @foreach($returnSpecs as $spec)
                                            <div>{{ $spec['label'] }}: {{ $spec['value'] }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </td>
                        <td class="text-center">{{ $order->return_qty }}</td>
                        <td class="text-right">- {{ format_taka((float) $order->exchange_credit) }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="section-label">New Items</div>
        @else
            <div class="section-label">Items</div>
        @endif

        <table class="items">
            <thead>
                <tr>
                    <th class="text-left">Description</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    @php
                        $product = $item->product;
                        $title = $product?->receiptDisplayName() ?? ($product?->name ?? 'Product');
                        $detailLines = $item->receiptDetailLines();
                    @endphp
                    <tr>
                        <td class="item-name">
                            {{ $title }}
                            @if(count($detailLines))
                                <div class="item-specs">
                                    @foreach($detailLines as $line)
                                        <div>{{ $line }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format((float) $item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            @if($order->is_exchange_receipt)
                <tr>
                    <td class="lbl">Items total</td>
                    <td class="text-right">{{ format_taka((float) $order->total_amount) }}</td>
                </tr>
                <tr>
                    <td class="lbl">Return credit</td>
                    <td class="text-right">- {{ format_taka((float) $order->exchange_credit) }}</td>
                </tr>
                @if(($order->discount_amount ?? 0) > 0)
                    <tr>
                        <td class="lbl">{{ $order->includes_sale ? 'SALE discount' : 'Discount' }}</td>
                        <td class="text-right">- {{ format_taka((float) $order->discount_amount) }}</td>
                    </tr>
                @endif
                @if(($order->credit_amount ?? 0) > 0)
                    <tr>
                        <td class="lbl">{{ ($order->is_emi ?? false) ? 'EMI financed' : 'Baki (due)' }}</td>
                        <td class="text-right">{{ format_taka((float) $order->credit_amount) }}</td>
                    </tr>
                @endif
                @if(($order->is_emi ?? false) && ($order->emi_down_payment ?? 0) > 0)
                    <tr>
                        <td class="lbl">EMI down payment</td>
                        <td class="text-right">{{ format_taka((float) $order->emi_down_payment) }}</td>
                    </tr>
                @endif
                @if(($order->is_emi ?? false) && ($order->emi_months ?? 0) > 0)
                    <tr>
                        <td class="lbl">EMI tenure</td>
                        <td class="text-right">{{ (int) $order->emi_months }} months</td>
                    </tr>
                @endif
                <tr class="grand">
                    <td>Net payable</td>
                    <td class="text-right">{{ format_taka($order->netPayable()) }}</td>
                </tr>
            @else
                <tr>
                    <td class="lbl">Subtotal</td>
                    <td class="text-right">{{ format_taka($itemsSubtotal) }}</td>
                </tr>
                @if(($order->delivery_charge ?? 0) > 0 || ($isOnline && filled($order->delivery_zone)))
                    <tr>
                        <td class="lbl">
                            Delivery
                            @if(filled($order->delivery_zone))
                                ({{ $order->delivery_zone === 'outside_dhaka' ? 'Outside Dhaka' : 'Inside Dhaka' }})
                            @endif
                        </td>
                        <td class="text-right">
                            @if(($order->delivery_charge ?? 0) > 0)
                                + {{ format_taka((float) $order->delivery_charge) }}
                            @else
                                FREE
                            @endif
                        </td>
                    </tr>
                @endif
                @if(($order->confirmation_charge ?? 0) > 0)
                    <tr>
                        <td class="lbl">Confirmation paid</td>
                        <td class="text-right">{{ format_taka((float) $order->confirmation_charge) }}</td>
                    </tr>
                @endif
                @if(($order->discount_amount ?? 0) > 0)
                    <tr>
                        <td class="lbl">{{ $order->includes_sale ? 'SALE discount' : 'Discount' }}</td>
                        <td class="text-right">- {{ format_taka((float) $order->discount_amount) }}</td>
                    </tr>
                @endif
                <tr class="grand">
                    <td>Grand total</td>
                    <td class="text-right">{{ format_taka($order->netPayable()) }}</td>
                </tr>
                @if($isOnline && ($order->delivery_charge ?? 0) > 0)
                    <tr>
                        <td colspan="2" style="padding-top:4px;font-size:9px;color:#64748b;font-weight:600;line-height:1.35">
                            * Delivery charge is paid to the delivery person (not store income).
                        </td>
                    </tr>
                @endif
                @if(($order->credit_amount ?? 0) > 0)
                    <tr>
                        <td class="lbl">Paid now</td>
                        <td class="text-right">{{ format_taka((float) $order->paid_amount) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">{{ ($order->is_emi ?? false) ? 'EMI (due)' : 'Baki (due)' }}</td>
                        <td class="text-right" style="font-weight:800">{{ format_taka((float) $order->credit_amount) }}</td>
                    </tr>
                @endif
            @endif
        </table>

        <div class="pay-box">
            <table>
                <tr>
                    <td class="lbl">Payment method</td>
                    <td class="text-right" style="font-weight:700">{{ $paymentLabel }}{{ ($order->is_emi ?? false) ? ' / EMI' : (($order->is_baki ?? false) ? ' / BAKI' : '') }}</td>
                </tr>
                @if(! $isVoid)
                    @php $tenderLines = $order->tenderLines(); @endphp
                    @if(count($tenderLines) > 0)
                        @foreach($tenderLines as $line)
                            <tr>
                                <td class="lbl">{{ $line['label'] }}</td>
                                <td class="text-right" style="font-weight:700">{{ format_taka($line['amount']) }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endif
                <tr>
                    <td class="lbl">Amount paid</td>
                    <td class="text-right" style="font-weight:700">
                        {{ format_taka($isVoid ? 0 : $amountPaid) }}
                    </td>
                </tr>
                @if($hasOpenDue && ! $isVoid)
                    <tr>
                        <td class="lbl">{{ $dueLabel === 'COD DUE' ? 'COD due (collect on delivery)' : ($hasOpenCredit ? (($order->is_emi ?? false) ? 'EMI remaining' : 'Due / Baki') : 'Amount due') }}</td>
                        <td class="text-right" style="font-weight:800">{{ format_taka($amountDue) }}</td>
                    </tr>
                @endif
                @if(($order->is_emi ?? false) && ($order->customer?->emi_balance ?? 0) > 0 && ! $isVoid)
                    <tr>
                        <td class="lbl">Total EMI left</td>
                        <td class="text-right" style="font-weight:800">{{ format_taka((float) $order->customer->emi_balance) }}</td>
                    </tr>
                @endif
                @if(!($order->is_emi ?? false) && ($order->customer?->baki_balance ?? 0) > 0 && ! $isVoid)
                    <tr>
                        <td class="lbl">Total baki left</td>
                        <td class="text-right" style="font-weight:800">{{ format_taka((float) $order->customer->baki_balance) }}</td>
                    </tr>
                @endif
                @if(($order->change_amount ?? 0) > 0 && ! $isVoid)
                    <tr>
                        <td class="lbl">Change due</td>
                        <td class="text-right" style="font-weight:800">{{ format_taka((float) $order->change_amount) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        @if($order->is_exchange_receipt)
            <div class="warning-box">
                <div style="font-weight:800;font-size:13px;margin-bottom:4px">*** FINAL SALE ***</div>
                <div style="font-size:10px;line-height:1.4">
                    This transaction includes exchanged items.<br>
                    Items listed above are <strong>non-returnable</strong>.
                </div>
            </div>
        @endif

        <footer class="footer">
            <p class="thanks">Thank you for your business</p>
            <p class="note">Please retain this invoice for your records.</p>
            <p class="stamp">Printed: {{ $printedAt->format('d M Y, h:i A') }} · Asia/Dhaka</p>
            @include('partials.powered-by', ['variant' => 'print'])
        </footer>
    </div>
</body>
</html>
