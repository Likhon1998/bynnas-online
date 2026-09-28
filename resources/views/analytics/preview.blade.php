<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ $shopName }}</title>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --brand: #4f46e5;
            --bg: #f8fafc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background: var(--bg);
            font-size: 13px;
            line-height: 1.45;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            background: #fff;
            border-bottom: 1px solid var(--line);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .toolbar-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
        }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: #4338ca; }
        .btn-secondary { background: #fff; color: var(--ink); border-color: var(--line); }
        .btn-secondary:hover { background: #f1f5f9; }
        .hint { color: var(--muted); font-size: 12px; }
        .sheet {
            max-width: 960px;
            margin: 24px auto;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }
        .sheet-header {
            padding: 28px 32px 20px;
            border-bottom: 1px solid var(--line);
        }
        .shop {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--brand);
        }
        h1 {
            margin: 6px 0 8px;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            color: var(--muted);
            font-size: 12px;
        }
        .meta strong { color: var(--ink); }
        .sheet-body { padding: 8px 32px 32px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: top;
        }
        th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--muted);
            background: #f8fafc;
        }
        td { font-size: 13px; }
        tr.section-gap td {
            border: none;
            height: 18px;
            padding: 0;
            background: transparent;
        }
        tr.section-head td {
            font-weight: 800;
            background: #eef2ff;
            color: #312e81;
            border-bottom-color: #c7d2fe;
        }
        .footer {
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px dashed var(--line);
            color: var(--muted);
            font-size: 11px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet {
                margin: 0;
                border: none;
                border-radius: 0;
                box-shadow: none;
                max-width: none;
            }
            .sheet-header, .sheet-body { padding-left: 0; padding-right: 0; }
            a { color: inherit; text-decoration: none; }
        }
        @page { margin: 14mm; }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <div>
            <div style="font-weight:800;font-size:15px;">Report Preview</div>
            <div class="hint">Review the report, then print or save as PDF.</div>
        </div>
        <div class="toolbar-actions">
            <a href="{{ $backUrl }}" class="btn btn-secondary">← Back to Reports</a>
            <a href="{{ $csvUrl }}" class="btn btn-secondary">Download CSV</a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / Save PDF
            </button>
        </div>
    </div>

    <div class="sheet" id="report-sheet">
        <div class="sheet-header">
            <div class="shop">{{ $shopName }}</div>
            <h1>{{ $title }}</h1>
            <div class="meta">
                <div>Period: <strong>{{ $start->format('d M Y') }}</strong> – <strong>{{ $end->format('d M Y') }}</strong></div>
                <div>Generated: <strong>{{ now()->format('d M Y, h:i A') }}</strong></div>
            </div>
        </div>

        <div class="sheet-body">
            @php
                $tables = [];
                $current = null;
                foreach ($rows as $row) {
                    $isEmpty = empty(array_filter($row, fn ($v) => $v !== null && $v !== ''));
                    if ($isEmpty) {
                        if ($current) {
                            $tables[] = $current;
                            $current = null;
                        }
                        continue;
                    }
                    if ($current === null) {
                        $current = ['headers' => $row, 'body' => []];
                    } else {
                        $current['body'][] = $row;
                    }
                }
                if ($current) {
                    $tables[] = $current;
                }
            @endphp

            @forelse($tables as $table)
                <table>
                    <thead>
                        <tr>
                            @foreach($table['headers'] as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($table['body'] as $bodyRow)
                            <tr>
                                @foreach($bodyRow as $i => $cell)
                                    <td @if($i === count($bodyRow) - 1) style="text-align:right;font-weight:600;" @endif>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($table['headers']) }}" style="text-align:center;color:#94a3b8;padding:24px;">No data for this section.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @empty
                <p style="text-align:center;color:#94a3b8;padding:40px 0;">No report data available for this period.</p>
            @endforelse

            <div class="footer">
                <span>{{ $shopName }} · {{ $title }}</span>
                <span>Confidential — for internal use</span>
            </div>
        </div>
    </div>
</body>
</html>
