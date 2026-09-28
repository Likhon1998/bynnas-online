<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcodes — {{ $products->count() }} product(s)</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 20px;
            font-family: Inter, Segoe UI, Arial, sans-serif;
            background: #f4f6fb;
            color: #0f172a;
        }
        .toolbar {
            max-width: 980px;
            margin: 0 auto 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 14px;
        }
        .toolbar h1 {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
        }
        .toolbar p {
            margin: 2px 0 0;
            font-size: 12px;
            color: #64748b;
        }
        .actions { display: flex; gap: 8px; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-back { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
        .grid {
            max-width: 980px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        .label {
            width: 100%;
            max-width: 220px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 10px 8px;
            text-align: center;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .label-name {
            margin: 0 0 6px;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.25;
            color: #0f172a;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 1.25em;
        }
        .barcode-wrap {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            line-height: 0;
        }
        .barcode-wrap svg {
            max-width: 100% !important;
            height: auto !important;
            display: block;
        }
        .label-code {
            margin: 4px 0 0;
            font-size: 10px;
            font-weight: 600;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            color: #0f172a;
            letter-spacing: .02em;
            word-break: break-all;
        }
        .label-price {
            margin: 4px 0 0;
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none !important; }
            .grid {
                max-width: none;
                gap: 6mm;
                padding: 4mm;
                grid-template-columns: repeat(auto-fill, minmax(50mm, 1fr));
            }
            .label {
                max-width: none;
                width: 50mm;
                min-height: 28mm;
                border: 1px solid #000;
                border-radius: 0;
                padding: 2.5mm 2mm 2mm;
                break-inside: avoid;
            }
            .label-name { font-size: 9pt; }
            .label-code { font-size: 8pt; }
            .label-price { font-size: 10pt; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>Barcode labels ready</h1>
            <p>{{ $products->count() }} product(s) · {{ $copies }} cop{{ $copies === 1 ? 'y' : 'ies' }} each · {{ $products->count() * $copies }} label(s) total</p>
        </div>
        <div class="actions">
            <a class="btn btn-back" href="{{ route('products.barcodes') }}">Back to list</a>
            <button class="btn btn-print" onclick="window.print()">Print now</button>
        </div>
    </div>

    <div class="grid">
        @foreach($products as $product)
            @for($i = 0; $i < $copies; $i++)
                <div class="label">
                    <p class="label-name" title="{{ $product->name }}">{{ $product->name }}</p>
                    <div class="barcode-wrap">
                        <svg class="js-print-barcode" data-value="{{ $product->barcode }}"></svg>
                    </div>
                    <p class="label-code">{{ $product->barcode }}</p>
                    <p class="label-price">{{ format_taka($product->selling_price, 'Tk ') }}</p>
                </div>
            @endfor
        @endforeach
    </div>

    <script>
        function fitBarcode(el) {
            const value = el.getAttribute('data-value');
            if (!value) return;

            const wrap = el.closest('.barcode-wrap');
            const avail = Math.max(120, (wrap?.clientWidth || 180) - 4);
            // CODE128 roughly ~11 modules per char + start/stop/checksum/quiet zone
            const modules = (String(value).length * 11) + 35;
            const barWidth = Math.max(0.85, Math.min(1.8, avail / modules));

            JsBarcode(el, value, {
                format: 'CODE128',
                width: barWidth,
                height: 40,
                displayValue: false,
                margin: 0,
                background: '#ffffff',
                lineColor: '#000000',
            });

            el.style.maxWidth = '100%';
            el.style.width = '100%';
            el.style.height = 'auto';
        }

        document.querySelectorAll('.js-print-barcode').forEach(fitBarcode);
        window.addEventListener('load', () => {
            document.querySelectorAll('.js-print-barcode').forEach(fitBarcode);
            setTimeout(() => window.print(), 400);
        });
    </script>
</body>
</html>
