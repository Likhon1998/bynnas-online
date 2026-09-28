<x-app-layout>
    <div class="csv-import-page max-w-[1100px] mx-auto pt-1 pb-12 px-1 sm:px-0"
         x-data="{
            fileName: '',
            onFile(e) {
                const f = e.target.files && e.target.files[0];
                this.fileName = f ? f.name : '';
            }
         }">
        <style>
            .csv-import-page {
                --csv-blue: #1d68ff;
                --csv-blue-soft: #eef4ff;
                --csv-blue-border: #c7daff;
                --csv-ink: #0f172a;
                --csv-muted: #64748b;
            }
            .csv-card {
                background: #fff;
                border: 1px solid #e8edf5;
                border-radius: 14px;
                box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            }
            .csv-btn-primary {
                display: inline-flex; align-items: center; justify-content: center; gap: 8px;
                background: var(--csv-blue); color: #fff;
                font-size: 13px; font-weight: 700;
                padding: 10px 18px; border-radius: 10px; border: 0;
                transition: background .15s ease;
            }
            .csv-btn-primary:hover { background: #1557e0; }
            .csv-btn-outline {
                display: inline-flex; align-items: center; justify-content: center; gap: 6px;
                background: #fff; color: var(--csv-blue);
                font-size: 13px; font-weight: 700;
                padding: 9px 16px; border-radius: 10px;
                border: 1.5px solid var(--csv-blue);
                text-decoration: none;
                transition: background .15s ease;
            }
            .csv-btn-outline:hover { background: var(--csv-blue-soft); }
            .csv-btn-ghost {
                display: inline-flex; align-items: center; justify-content: center;
                background: #fff; color: #475569;
                font-size: 13px; font-weight: 700;
                padding: 10px 16px; border-radius: 10px;
                border: 1px solid #e2e8f0; text-decoration: none;
            }
            .csv-btn-ghost:hover { background: #f8fafc; }
            .csv-drop {
                border: 1.5px dashed #c9d4e5;
                border-radius: 12px;
                background: #fafbfe;
                padding: 28px 20px;
                text-align: center;
                transition: border-color .15s ease, background .15s ease;
            }
            .csv-drop.is-ready {
                border-color: var(--csv-blue);
                background: var(--csv-blue-soft);
            }
            .csv-notes {
                background: var(--csv-blue-soft);
                border: 1px solid var(--csv-blue-border);
                border-radius: 12px;
                padding: 18px 18px 14px;
                height: 100%;
            }
            .csv-badge-req {
                display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;
                background: #ecfdf5; color: #047857;
                border: 1px solid #a7f3d0;
                border-radius: 999px; padding: 6px 12px;
                font-size: 12px; font-weight: 700;
            }
            .csv-badge-opt {
                display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;
                background: #f5f3ff; color: #6d28d9;
                border: 1px solid #ddd6fe;
                border-radius: 999px; padding: 6px 12px;
                font-size: 12px; font-weight: 700;
            }
            .csv-sample {
                width: 100%; border-collapse: collapse; font-size: 12.5px;
            }
            .csv-sample th {
                background: #f1f5f9; color: #334155;
                font-size: 11px; font-weight: 800; text-transform: lowercase;
                letter-spacing: .02em; text-align: left;
                padding: 10px 12px; border: 1px solid #e2e8f0;
            }
            .csv-sample td {
                padding: 10px 12px; border: 1px solid #e8edf5; color: #0f172a;
                background: #fff;
            }
            .csv-sample tr:nth-child(even) td { background: #fcfdff; }
        </style>

        {{-- Page header --}}
        <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-[22px] sm:text-[24px] font-extrabold tracking-tight text-slate-900">Import Products from CSV</h1>
                <p class="mt-1 text-sm text-slate-500">Upload a CSV file to import multiple products at once.</p>
            </div>
            <a href="{{ route('products.index') }}" class="csv-btn-outline self-start sm:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Product List
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('import_errors') && count(session('import_errors')))
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-bold mb-2">Some rows were skipped:</p>
                <ul class="list-disc pl-5 space-y-1 text-xs">
                    @foreach(session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Top: upload + notes --}}
        <form action="{{ route('products.import.store') }}" method="POST" enctype="multipart/form-data" class="csv-card mb-5 overflow-hidden">
            @csrf
            <div class="grid lg:grid-cols-2 gap-0">
                <div class="p-5 sm:p-6 border-b lg:border-b-0 lg:border-r border-slate-100">
                    <label class="block text-[14px] font-extrabold text-slate-900 mb-3">CSV File</label>

                    <div class="csv-drop" :class="fileName ? 'is-ready' : ''">
                        <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-[#eef4ff] text-[#1d68ff]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700" x-text="fileName || 'No file chosen.'"></p>
                        <label class="csv-btn-outline mt-3 cursor-pointer inline-flex">
                            Choose File
                            <input type="file" name="csv_file" accept=".csv,.txt,text/csv" required class="sr-only"
                                   @change="onFile($event)">
                        </label>
                    </div>

                    <p class="mt-2.5 text-[12px] text-slate-500">Excel: File → Save As → CSV UTF-8 (or CSV). Max 5 MB.</p>
                    @error('csv_file')
                        <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-5 flex flex-wrap items-center gap-2.5">
                        <a href="{{ route('products.import.template') }}" class="csv-btn-outline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download demo Excel/CSV
                        </a>
                        <a href="{{ route('products.index') }}" class="csv-btn-ghost">Cancel</a>
                        <button type="submit" class="csv-btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Import Products
                        </button>
                    </div>
                </div>

                <div class="p-5 sm:p-6" style="background:#f0f7ff;">
                    <div class="csv-notes" style="background:transparent;border:0;padding:0;">
                        <div class="flex items-center gap-2 mb-3.5">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full text-[#1d68ff]">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            </span>
                            <h3 class="text-[14px] font-extrabold text-slate-900">Important Notes</h3>
                        </div>
                        <ul class="space-y-2.5">
                            @foreach([
                                'Download the demo CSV, open it in Excel, replace the sample rows with your products, then Save As → CSV UTF-8.',
                                'CSV file should be encoded in UTF-8 for special characters.',
                                'Maximum file size allowed is 5 MB.',
                                'Duplicate barcodes will be skipped.',
                                'stock_quantity creates opening stock with inventory + accounts audit trail.',
                                'Pictures: paste a public image_url in the CSV, OR leave blank and upload photos later from Edit Product.',
                                'Comma, semicolon, or tab separators are supported.',
                            ] as $note)
                                <li class="flex items-start gap-2.5 text-[13px] text-slate-600 leading-snug">
                                    <span class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-[#1d68ff] text-white">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <span>{{ $note }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </form>

        {{-- Bottom: format + sample table --}}
        <div class="csv-card p-5 sm:p-6">
            <div class="flex items-center gap-2.5 mb-4">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#eef4ff] text-[#1d68ff]">
                    <svg class="w-4.5 h-4.5 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
                <h2 class="text-[16px] font-extrabold text-slate-900">CSV Format</h2>
            </div>

            <div class="flex flex-col gap-2.5 mb-5">
                <div class="csv-badge-req">
                    <span class="uppercase tracking-wide text-[10px] font-extrabold opacity-80">Required</span>
                    <span>name, barcode, cost_price, selling_price</span>
                </div>
                <div class="csv-badge-opt">
                    <span class="uppercase tracking-wide text-[10px] font-extrabold opacity-80">Optional</span>
                    <span>sku, category, brand, stock_quantity, alert_quantity, image_url, color, color_hex, ram, storage, variant_group, short_description</span>
                </div>
            </div>

            <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-[13px] text-slate-600 leading-relaxed">
                <p class="font-bold text-slate-800 mb-1">How pictures work</p>
                <ol class="list-decimal pl-4 space-y-1">
                    <li><strong>Recommended:</strong> import the CSV first, then open each product → <em>Edit</em> → upload gallery photos.</li>
                    <li><strong>Optional in CSV:</strong> put a public image link in the <code class="text-[12px] bg-white px-1 rounded border">image_url</code> column (https://…). The system downloads it during import.</li>
                    <li>Excel cannot embed local phone photos inside a CSV file — local files must be uploaded in the product edit screen.</li>
                </ol>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="csv-sample min-w-[1100px]">
                    <thead>
                        <tr>
                            <th>name</th>
                            <th>barcode</th>
                            <th>category</th>
                            <th>brand</th>
                            <th>cost_price</th>
                            <th>selling_price</th>
                            <th>stock_quantity</th>
                            <th>image_url</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Samsung Galaxy S22 — Green / 8GB / 128GB</td>
                            <td>8801234567001</td>
                            <td>Smartphones</td>
                            <td>Samsung</td>
                            <td>24500</td>
                            <td>28999</td>
                            <td>10</td>
                            <td class="text-slate-400">(optional https link)</td>
                        </tr>
                        <tr>
                            <td>Somostel 65W GaN Charger</td>
                            <td>8801234567002</td>
                            <td>Chargers</td>
                            <td>Somostel</td>
                            <td>900</td>
                            <td>1490</td>
                            <td>40</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Oraimo FreePods 4</td>
                            <td>8801234567003</td>
                            <td>Earbuds</td>
                            <td>Oraimo</td>
                            <td>1600</td>
                            <td>2290</td>
                            <td>22</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <a href="{{ route('products.import.template') }}" class="csv-btn-outline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download demo Excel/CSV template
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
