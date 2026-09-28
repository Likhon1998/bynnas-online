{{-- Payment slip modal (iframe). Expect parent Alpine scope with: slipOpen, slipUrl, autoPrintSlip, openSlip(), closeSlip(), printSlip() --}}
<div x-show="slipOpen"
     x-cloak
     class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-6"
     style="display:none"
     @keydown.escape.window="closeSlip()">
    <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-[2px]" @click="closeSlip()"></div>
    <div class="relative flex w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
         style="max-height: min(92vh, 860px)"
         @click.stop>
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Payment slip</h3>
                <p class="text-[11px] text-slate-500">Print or close when done</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button"
                        @click="printSlip()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700">
                    Print
                </button>
                <button type="button"
                        @click="closeSlip()"
                        class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 hover:bg-slate-50 hover:text-slate-800"
                        aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="min-h-0 flex-1 overflow-hidden bg-slate-100">
            <iframe x-ref="slipFrame"
                    :src="slipUrl"
                    title="Payment slip"
                    class="h-[min(78vh,720px)] w-full border-0 bg-white"
                    @load="onSlipLoaded()"></iframe>
        </div>
    </div>
</div>
