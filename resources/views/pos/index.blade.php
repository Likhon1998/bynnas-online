<x-pos-layout>
<style>
@import url('https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap');

:root {
    --navy: #0b1b3a;
    --navy-2: #122647;
    --blue: #2563eb;
    --blue-soft: #eff6ff;
    --bg: #f4f6fb;
    --surface: #ffffff;
    --surface-2: #f8fafc;
    --border: #e8edf5;
    --text-1: #0f172a;
    --text-2: #475569;
    --text-3: #94a3b8;
    --green: #16a34a;
    --green-bg: #ecfdf5;
    --green-border: #a7f3d0;
    --red: #dc2626;
    --red-bg: #fef2f2;
    --red-border: #fecaca;
    --amber: #d97706;
    --amber-bg: #fffbeb;
    --amber-border: #fde68a;
    --teal: #2563eb;
    --teal-bg: #eff6ff;
    --teal-border: #bfdbfe;
    --radius: 14px;
    --font: Figtree, system-ui, sans-serif;
    --mono: ui-monospace, SFMono-Regular, Menlo, monospace;
}

[data-theme="dark"] {
    --navy: #060d1a;
    --navy-2: #0b1528;
    --bg: #0b1220;
    --surface: #111827;
    --surface-2: #1a2332;
    --border: #243044;
    --text-1: #f8fafc;
    --text-2: #94a3b8;
    --text-3: #64748b;
    --blue-soft: #0b1f44;
    --teal: #3b82f6;
    --teal-bg: #0b1f44;
    --teal-border: #1e3a8a;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
[x-cloak] { display: none !important; }

.pos-shell {
    height: 100%; height: 100dvh; width: 100%; min-width: 0;
    overflow: hidden;
    display: flex; flex-direction: column;
    background: var(--bg); font-family: var(--font); color: var(--text-1);
}

/* Counter fullscreen prompt */
.pos-fs-hint {
    flex-shrink: 0;
    background: linear-gradient(90deg, #1d4ed8, #2563eb);
    color: #fff;
    border-bottom: 1px solid rgba(255,255,255,.12);
}
.pos-fs-hint-inner {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 8px 16px; flex-wrap: wrap;
}
.pos-fs-hint-inner strong { font-size: 13px; font-weight: 750; margin-right: 8px; }
.pos-fs-hint-inner span { font-size: 12.5px; opacity: .92; }
.pos-fs-hint-actions { display: flex; align-items: center; gap: 8px; }
.pos-fs-btn {
    border: 0; border-radius: 9px; padding: 7px 14px;
    background: #fff; color: #1d4ed8;
    font: inherit; font-size: 12px; font-weight: 750; cursor: pointer;
}
.pos-fs-btn:hover { background: #eff6ff; }
.pos-fs-dismiss {
    border: 1px solid rgba(255,255,255,.35); border-radius: 9px; padding: 7px 12px;
    background: transparent; color: #fff;
    font: inherit; font-size: 12px; font-weight: 650; cursor: pointer;
}
.pos-fs-dismiss:hover { background: rgba(255,255,255,.1); }

/* ── Top bar ── */
.pos-chrome {
    flex-shrink: 0; height: 58px;
    display: flex; align-items: center; gap: 14px;
    padding: 0 16px;
    background: var(--navy); color: #fff;
    border-bottom: 1px solid rgba(255,255,255,.06);
}
.pos-brand {
    display: flex; align-items: center; gap: 10px; flex-shrink: 0;
    text-decoration: none; color: #fff; font-weight: 700; font-size: 14px;
}
.pos-brand-mark {
    width: 34px; height: 34px; border-radius: 10px;
    background: var(--blue); display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 18px rgba(37,99,235,.35);
}
.pos-brand-mark svg { width: 17px; height: 17px; }
.pos-brand em { font-style: normal; color: #93c5fd; font-weight: 600; }

.pos-chrome-search {
    flex: 1; max-width: 560px; margin: 0 auto; position: relative;
}
.pos-chrome-search .search-icon {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    width: 15px; height: 15px; color: #94a3b8; pointer-events: none;
}
.pos-chrome-search .search-input {
    width: 100%;
    padding: 10px 78px 10px 38px;
    border-radius: 12px; border: 1px solid rgba(255,255,255,.08);
    background: var(--navy-2); color: #fff;
    font-family: var(--font); font-size: 13px; outline: none;
}
.pos-chrome-search .search-input::placeholder { color: #64748b; }
.pos-chrome-search .search-input:focus {
    border-color: rgba(37,99,235,.55); box-shadow: 0 0 0 3px rgba(37,99,235,.2);
}
.pos-chrome-search .kbd {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    font-size: 10px; font-weight: 700; color: #64748b;
    border: 1px solid rgba(255,255,255,.1); border-radius: 6px; padding: 2px 7px;
}

.pos-chrome-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; margin-left: auto; }
.pos-tool-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 12px; border-radius: 10px;
    border: 1px solid rgba(255,255,255,.1); background: transparent;
    color: #e2e8f0; font-size: 12px; font-weight: 650; cursor: pointer; font-family: var(--font);
}
.pos-tool-btn:hover { background: rgba(255,255,255,.06); }
.pos-tool-btn.is-active { background: rgba(37,99,235,.25); border-color: rgba(96,165,250,.45); color: #93c5fd; }
.pos-tool-btn svg { width: 15px; height: 15px; }
.pos-close-day {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 12px; border-radius: 10px;
    border: 1px solid rgba(248,113,113,.35); background: rgba(239,68,68,.12);
    color: #fecaca; text-decoration: none; font-size: 12px; font-weight: 700;
}
.pos-close-day svg { width: 14px; height: 14px; }
.pos-user {
    display: flex; align-items: center; gap: 8px;
    padding-left: 8px; border-left: 1px solid rgba(255,255,255,.1);
}
.pos-user-avatar {
    width: 32px; height: 32px; border-radius: 999px;
    background: #1d4ed8; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700;
}
.pos-user-meta { line-height: 1.2; }
.pos-user-name { font-size: 12.5px; font-weight: 650; color: #fff; }
.pos-user-role { font-size: 11px; color: #94a3b8; }
.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 10px; border-radius: 999px;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    border: 1px solid transparent;
}
.status-badge.online  { background: rgba(22,163,74,.15); color: #86efac; border-color: rgba(22,163,74,.25); }
.status-badge.offline { background: rgba(239,68,68,.15); color: #fca5a5; border-color: rgba(239,68,68,.25); }
.status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

/* ── Body: full-width workspace (no left rail) ── */
.pos-body {
    flex: 1 1 0; min-height: 0;
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    overflow: hidden;
}
.pos-home-btn {
    display: inline-flex; align-items: center; gap: 6px;
    height: 34px; padding: 0 12px; border-radius: 10px;
    border: 1px solid rgba(255,255,255,.14);
    background: rgba(255,255,255,.08); color: #e2e8f0;
    text-decoration: none; font-size: 12px; font-weight: 700;
    font-family: var(--font); white-space: nowrap;
}
.pos-home-btn:hover { background: rgba(255,255,255,.14); color: #fff; }
.pos-home-btn svg { width: 15px; height: 15px; flex-shrink: 0; }

.pos-root {
    flex: 1 1 0; min-height: 0; min-width: 0;
    padding: 12px;
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(360px, .95fr);
    gap: 12px; overflow: hidden;
}

.panel-left, .panel-right {
    min-width: 0; min-height: 0; height: 100%;
    display: flex; flex-direction: column;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(15,23,42,.04);
    overflow: hidden;
}
.panel-left { overflow: visible; }
.panel-right {
    overflow: hidden;
    scrollbar-width: thin;
}

.panel-top {
    padding: 8px 12px 8px;
    border-bottom: 1px solid var(--border);
    flex-shrink: 0; background: var(--surface);
    overflow: visible;
    position: relative;
    z-index: 80;
}
.top-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.icon-btn {
    width: 36px; height: 36px; border-radius: 10px;
    background: var(--surface-2); border: 1px solid var(--border);
    color: var(--text-2); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}
.icon-btn:hover { color: var(--text-1); background: #eef2f7; }
.icon-btn svg { width: 16px; height: 16px; }

.filter-label-row {
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    margin-bottom: 6px;
}
.filter-label {
    font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
    color: var(--text-3); margin-bottom: 0;
}
.cat-scroll-wrap { position: relative; z-index: 90; overflow: visible; }
.cat-scroll {
    display: flex; gap: 6px; overflow-x: auto; padding: 2px 2px 4px;
    scrollbar-width: thin;
}
.cat-scroll::-webkit-scrollbar { height: 3px; }
.cat-card-wrap {
    position: relative; flex: 0 0 auto; z-index: 1;
}
.cat-card-wrap.open { z-index: 100; }
.cat-card {
    min-width: 78px; max-width: 96px; width: 88px;
    display: flex; flex-direction: column; align-items: center; gap: 3px;
    padding: 8px 6px 7px; border-radius: 12px; cursor: pointer;
    border: 1.5px solid var(--border); background: var(--surface);
    font-family: var(--font); text-align: center;
    transition: border-color .15s, box-shadow .15s, background .15s, color .15s;
    position: relative;
}
.cat-card:hover { border-color: #93c5fd; box-shadow: 0 4px 12px rgba(37,99,235,.08); }
.cat-card.active {
    background: #fff; border-color: var(--blue); color: var(--blue);
    box-shadow: 0 0 0 2px rgba(37,99,235,.12);
}
.cat-card-icon {
    width: 26px; height: 26px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    background: #f8fafc; color: #475569;
    border: 1px solid #e2e8f0;
}
.cat-card.active .cat-card-icon {
    background: #eff6ff; color: var(--blue); border-color: #bfdbfe;
}
.cat-card-icon svg { width: 15px; height: 15px; }
.cat-card-name {
    font-size: 10.5px; font-weight: 750; color: var(--text-1); line-height: 1.15;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;
}
.cat-card.active .cat-card-name { color: var(--blue); }
.cat-card-count {
    font-family: var(--mono); font-size: 10px; font-weight: 700;
    color: var(--text-3);
}
.cat-card.active .cat-card-count { color: #3b82f6; }
.cat-card-caret {
    position: absolute; top: 4px; right: 4px;
    width: 16px; height: 16px; border-radius: 5px; border: 0;
    background: var(--surface-2); color: var(--text-3);
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; z-index: 2;
}
.cat-card-caret:hover, .cat-card-wrap.open .cat-card-caret {
    background: var(--blue-soft); color: var(--blue);
}
.cat-card-caret svg { width: 10px; height: 10px; transition: transform .15s; }
.cat-card-wrap.open .cat-card-caret svg { transform: rotate(180deg); }
.cat-brand-menu {
    position: fixed;
    min-width: 190px; max-width: 240px;
    background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
    box-shadow: 0 18px 40px rgba(15,23,42,.2); z-index: 9999;
    padding: 6px; max-height: 260px; overflow-y: auto;
}
.cat-brand-menu-title {
    font-size: 10px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
    color: var(--text-3); padding: 6px 8px 4px;
}
.cat-brand-item {
    width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 8px 10px; border: 0; border-radius: 8px; background: transparent;
    font: inherit; font-size: 12.5px; font-weight: 650; color: var(--text-1);
    cursor: pointer; text-align: left;
}
.cat-brand-item:hover { background: var(--blue-soft); color: var(--blue); }
.cat-brand-item.active { background: var(--blue); color: #fff; }
.cat-brand-item span:last-child {
    font-family: var(--mono); font-size: 11px; opacity: .75;
}

.pos-toolbar {
    display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
    margin-top: 8px;
}
.pos-toolbar-search {
    flex: 1 1 180px; min-width: 160px; position: relative;
}
.pos-toolbar-search svg {
    position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
    width: 14px; height: 14px; color: var(--text-3); pointer-events: none;
}
.pos-toolbar-search input {
    width: 100%; padding: 9px 12px 9px 34px;
    border: 1px solid var(--border); border-radius: 10px;
    background: var(--surface-2); color: var(--text-1);
    font: inherit; font-size: 12.5px; outline: none;
}
.pos-toolbar-search input:focus {
    border-color: #93c5fd; background: #fff; box-shadow: 0 0 0 3px rgba(37,99,235,.12);
}
.pos-toolbar-select {
    padding: 9px 12px; border: 1px solid var(--border); border-radius: 10px;
    background: var(--surface-2); color: var(--text-1);
    font: inherit; font-size: 12.5px; font-weight: 650; outline: none; cursor: pointer;
}
.pos-toolbar-select:focus { border-color: #93c5fd; }
.pos-view-toggle {
    display: inline-flex; border: 1px solid var(--border); border-radius: 10px; overflow: hidden;
    background: var(--surface-2);
}
.pos-view-btn {
    width: 36px; height: 34px; border: 0; background: transparent; color: var(--text-3);
    display: flex; align-items: center; justify-content: center; cursor: pointer;
}
.pos-view-btn + .pos-view-btn { border-left: 1px solid var(--border); }
.pos-view-btn.active { background: #fff; color: var(--blue); }
.pos-view-btn svg { width: 15px; height: 15px; }

.product-stage {
    flex: 1 1 0; min-height: 0;
    display: flex; flex-direction: column;
    background: #eef2f7;
    overflow: hidden;
    border-radius: 0 0 16px 16px;
    position: relative;
    z-index: 1;
}
.product-list {
    flex: 1 1 0; min-height: 0; overflow-y: auto;
    padding: 12px;
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 12px;
    align-content: start;
    align-items: stretch;
    scrollbar-width: thin;
    position: relative;
    z-index: 1;
}
.product-list::-webkit-scrollbar { width: 8px; }
.product-list::-webkit-scrollbar-thumb {
    background: #cbd5e1; border-radius: 999px;
}
.product-list.is-list {
    display: flex;
    flex-direction: column;
    grid-template-columns: none;
    gap: 8px;
}
.product-list.is-list .product-card {
    flex-direction: row;
    align-items: center;
    min-height: 76px !important;
    height: 76px;
    max-height: 76px;
    border-radius: 12px;
    overflow: hidden;
}
.product-list.is-list .product-card:hover {
    transform: none;
    box-shadow: 0 4px 14px rgba(37, 99, 235, .12);
}
.product-list.is-list .product-card:active { transform: none; }
.product-list.is-list .p-selected-badge {
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
}
.product-list.is-list .p-img-wrap {
    width: 76px !important;
    height: 76px !important;
    min-height: 76px !important;
    max-width: 76px;
    flex: 0 0 76px !important;
    border-bottom: 0;
    border-right: 1px solid #eef2f7;
    background: #f8fafc;
}
.product-list.is-list .p-img-wrap img {
    padding: 10px;
}
.product-list.is-list .p-img-fallback {
    font-size: 14px;
}
.product-list.is-list .p-view-btn {
    display: none;
}
.product-list.is-list .p-body {
    flex: 1 1 auto;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 10px 48px 10px 14px;
    min-height: 0 !important;
    height: 100%;
}
.product-list.is-list .p-meta {
    flex: 1 1 auto;
    min-width: 0;
    gap: 2px;
}
.product-list.is-list .p-name {
    min-height: 0 !important;
    -webkit-line-clamp: 1;
    font-size: 13px;
}
.product-list.is-list .p-sku {
    font-size: 11px;
}
.product-list.is-list .p-foot {
    margin-top: 0;
    flex-direction: column;
    align-items: flex-end;
    justify-content: center;
    gap: 4px;
    flex-shrink: 0;
    padding-top: 0;
}
.product-list.is-list .p-price {
    font-size: 15px;
}
.product-list.is-list .p-stock {
    font-size: 10px;
}

.product-card {
    position: relative;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 0;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-width: 0;
    min-height: 248px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    transition: border-color .15s, box-shadow .15s, transform .12s;
}
.product-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 12px 28px rgba(37, 99, 235, .12);
    transform: translateY(-2px);
}
.product-card:active { transform: scale(.985); }
.product-card.out-of-stock {
    opacity: .6; cursor: not-allowed; pointer-events: none; filter: grayscale(.2);
}
.product-card.in-cart {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, .2), 0 8px 20px rgba(37, 99, 235, .1);
}
.product-card.is-sale {
    border-color: #fda4af;
    background: linear-gradient(180deg, #fff1f2 0%, #fff 48%);
}
.product-card.is-sale.in-cart {
    border-color: #fb7185;
    box-shadow: 0 0 0 2px rgba(244, 63, 94, .2), 0 8px 20px rgba(244, 63, 94, .1);
}
.p-sale-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    z-index: 3;
    background: linear-gradient(90deg, #e11d48, #f97316);
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
    padding: 3px 8px;
    border-radius: 999px;
    box-shadow: 0 6px 14px rgba(225, 29, 72, .28);
}
.p-price-wrap {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}
.p-price-list {
    font-size: 11px;
    font-weight: 650;
    color: #94a3b8;
    text-decoration: line-through;
    line-height: 1.1;
}
.p-price.is-sale-price { color: #e11d48; }
.p-selected-badge {
    position: absolute; top: 8px; right: 8px; z-index: 3;
    min-width: 22px; height: 22px; padding: 0 6px; border-radius: 999px;
    background: #2563eb; color: #fff;
    font-family: var(--mono); font-size: 11px; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 10px rgba(37, 99, 235, .4);
}
.p-img-wrap {
    width: 100%;
    height: 118px;
    min-height: 118px;
    flex: 0 0 118px;
    background: linear-gradient(180deg, #fff 0%, #f1f5f9 100%);
    border-bottom: 1px solid #eef2f7;
    position: relative;
    overflow: hidden;
}
.p-img-wrap img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 12px;
    display: block;
    z-index: 1;
}
.p-img-fallback {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; font-weight: 800; color: #94a3b8;
    background: linear-gradient(180deg, #f8fafc, #e2e8f0);
    z-index: 0;
}
.p-body {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 10px 11px 11px;
    background: #fff;
    min-height: 118px;
}
.p-meta {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
    flex: 1 1 auto;
}
.p-name {
    margin: 0;
    font-size: 12.5px;
    font-weight: 750;
    color: #0f172a;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
    min-height: 2.7em;
}
.p-sku {
    margin: 0;
    font-family: var(--mono);
    font-size: 10px;
    font-weight: 600;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.p-foot {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: auto;
    padding-top: 2px;
}
.p-price {
    font-size: 14px;
    font-weight: 800;
    color: #2563eb;
    line-height: 1.2;
    white-space: nowrap;
}
.p-price-sym {
    font-size: 11px;
    color: #64748b;
    margin-right: 2px;
    font-weight: 700;
}
.p-stock,
.stock-chip {
    width: fit-content;
    max-width: 100%;
    font-size: 10.5px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 999px;
    border: 1px solid #a7f3d0;
    background: #ecfdf5;
    color: #15803d;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}
.p-stock .stock-dot,
.stock-chip .stock-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: currentColor; flex-shrink: 0;
}
.p-stock.stock-ok,
.stock-chip.stock-ok { background: #ecfdf5; color: #15803d; border-color: #a7f3d0; }
.p-stock.stock-low,
.stock-chip.stock-low { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.p-stock.stock-out,
.stock-chip.stock-out { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

.pos-pager {
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
    padding: 10px 14px; border-top: 1px solid var(--border); background: var(--surface);
}
.pos-pager-meta { font-size: 12px; color: var(--text-2); font-weight: 650; }
.pos-pager-pages { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.pos-page-btn {
    min-width: 32px; height: 32px; padding: 0 10px;
    border: 1px solid var(--border); border-radius: 8px;
    background: var(--surface); color: var(--text-2);
    font: inherit; font-size: 12px; font-weight: 700; cursor: pointer;
}
.pos-page-btn:hover:not(:disabled) { border-color: #93c5fd; color: var(--blue); background: #eff6ff; }
.pos-page-btn.active { background: var(--blue); border-color: var(--blue); color: #fff; }
.pos-page-btn:disabled { opacity: .4; cursor: not-allowed; }

/* Cart */
.cart-head {
    padding: 10px 12px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
    z-index: 3; background: var(--surface);
}
.cart-title { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; }
.cart-badge-icon {
    width: 26px; height: 26px; border-radius: 8px;
    background: var(--blue-soft); border: 1px solid var(--teal-border);
    display: flex; align-items: center; justify-content: center;
}
.cart-badge-icon svg { width: 13px; height: 13px; color: var(--blue); }
.cart-count {
    min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px;
    background: var(--blue); color: #fff;
    font-family: var(--mono); font-size: 10px; font-weight: 600;
    display: inline-flex; align-items: center; justify-content: center;
}
.cart-head-actions { display: flex; align-items: center; gap: 6px; }
.held-btn, .clear-btn, .view-cart-btn {
    padding: 5px 8px; border-radius: 8px; font-size: 11px; font-weight: 650;
    cursor: pointer; font-family: var(--font); border: 1px solid transparent;
}
.held-btn {
    display: flex; align-items: center; gap: 4px;
    background: var(--amber-bg); color: var(--amber); border-color: var(--amber-border);
}
.held-btn svg { width: 12px; height: 12px; }
.clear-btn { background: transparent; color: var(--red); }
.clear-btn:hover { background: var(--red-bg); border-color: var(--red-border); }
.view-cart-btn {
    display: inline-flex; align-items: center; gap: 4px;
    background: var(--blue-soft); color: var(--blue); border-color: var(--teal-border);
}
.view-cart-btn:hover { background: #dbeafe; }
.view-cart-btn svg { width: 12px; height: 12px; }
.p-view-btn {
    position: absolute; left: 6px; bottom: 6px; z-index: 3;
    height: 24px; padding: 0 8px; border-radius: 7px; border: 0;
    background: rgba(15, 23, 42, .78); color: #fff;
    font: inherit; font-size: 10px; font-weight: 750; letter-spacing: .02em;
    display: inline-flex; align-items: center; gap: 4px; cursor: pointer;
    backdrop-filter: blur(4px);
}
.p-view-btn:hover { background: #2563eb; }
.p-view-btn svg { width: 11px; height: 11px; }

.cart-view-modal {
    width: min(860px, calc(100vw - 20px)) !important;
    max-width: none !important;
    max-height: min(90vh, 780px);
    display: flex;
    flex-direction: column;
}
.cart-view-modal .modal-head {
    padding: 16px 18px;
    background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
}
.cart-view-modal .modal-title { font-size: 16px; }
.cart-view-modal .modal-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-x: hidden;
    overflow-y: auto;
    background: #f1f5f9;
    padding: 14px 16px;
    gap: 0;
}
.cart-view-modal .modal-foot {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
    padding: 14px 16px 16px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
}
.cart-view-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.cart-view-item {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) 150px 158px 118px 40px;
    gap: 14px;
    align-items: center;
    padding: 14px 16px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
}
.cv-product {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.cv-thumb {
    width: 56px; height: 56px; border-radius: 12px; overflow: hidden;
    background: linear-gradient(180deg, #fff, #f1f5f9);
    border: 1px solid #e8edf5;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.cv-thumb img { width: 100%; height: 100%; object-fit: contain; padding: 5px; }
.cv-info { min-width: 0; flex: 1; }
.cv-name {
    font-size: 13.5px; font-weight: 750; color: #0f172a; line-height: 1.35;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.cv-sku {
    margin-top: 3px;
    font-size: 11px; color: #94a3b8; font-family: var(--mono); font-weight: 600;
}
.cv-reset {
    margin-top: 4px; border: 0; background: none; padding: 0;
    color: #2563eb; font-size: 11px; font-weight: 750; cursor: pointer;
}
.cv-reset:hover { text-decoration: underline; }
.cv-field { min-width: 0; }
.cv-field-label {
    display: block;
    font-size: 10px; font-weight: 800; letter-spacing: .04em;
    text-transform: uppercase; color: #94a3b8; margin-bottom: 6px;
}
.cv-price-wrap {
    display: flex; align-items: center;
    border: 1px solid #e2e8f0; border-radius: 11px; overflow: hidden;
    background: #f8fafc;
}
.cv-price-wrap:focus-within {
    border-color: #93c5fd; background: #fff;
    box-shadow: 0 0 0 3px rgba(37,99,235,.12);
}
.cv-currency {
    padding-left: 10px;
    font-size: 11px; font-weight: 800; color: #64748b; flex-shrink: 0;
}
.cv-input {
    width: 100%; min-width: 0; padding: 10px 10px 10px 6px;
    border: 0; background: transparent; color: #0f172a;
    font: inherit; font-size: 14px; font-weight: 750; font-family: var(--mono);
    outline: none;
}
.cv-qty-wrap {
    display: inline-flex; align-items: center;
    border: 1px solid #e2e8f0; border-radius: 11px; overflow: hidden; background: #fff;
    width: 100%;
}
.cv-qty-wrap .qty-btn {
    width: 36px; height: 40px; flex-shrink: 0;
    font-size: 16px; color: #475569; border: 0; background: transparent; cursor: pointer;
}
.cv-qty-wrap .qty-btn:hover { background: #f1f5f9; color: #2563eb; }
.cv-qty-wrap input {
    flex: 1; min-width: 0; width: 100%; height: 40px; border: 0; text-align: center;
    font-family: var(--mono); font-weight: 750; font-size: 14px; outline: none; background: transparent;
}
.cv-total-box { min-width: 0; }
.cv-line {
    font-family: var(--mono); font-weight: 800; font-size: 15px; color: #2563eb;
    white-space: nowrap;
}
.cv-remove {
    width: 36px; height: 36px; border: 0; border-radius: 10px; background: #f8fafc;
    color: #94a3b8; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
    justify-self: end;
}
.cv-remove:hover { background: #fef2f2; color: #dc2626; }
.cv-summary {
    display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    margin: 0; padding: 12px 14px; border-radius: 12px;
    background: #eff6ff; border: 1px solid #bfdbfe;
}
.cv-summary-label {
    font-size: 11px; font-weight: 800; letter-spacing: .04em;
    text-transform: uppercase; color: #1d4ed8;
}
.cv-summary-meta { font-size: 12px; color: #64748b; margin-top: 2px; }
.cv-summary strong { color: #2563eb; font-size: 20px; font-family: var(--mono); }
.cv-foot-actions { display: flex; gap: 8px; }
.cv-foot-actions .modal-cancel,
.cv-foot-actions .modal-confirm { flex: 1; }
@media (max-width: 720px) {
    .cart-view-item {
        grid-template-columns: 1fr 1fr auto;
        grid-template-areas:
            "product product remove"
            "price qty total";
        gap: 12px;
    }
    .cv-product { grid-area: product; }
    .cv-price-cell { grid-area: price; }
    .cv-qty-cell { grid-area: qty; }
    .cv-total-box { grid-area: total; align-self: end; padding-bottom: 8px; }
    .cv-remove { grid-area: remove; }
}

.exchange-banner {
    padding: 7px 12px; flex-shrink: 0;
    background: var(--amber-bg); border-bottom: 1px solid var(--amber-border);
    display: flex; justify-content: space-between; align-items: center;
    font-size: 11px; font-weight: 650; color: var(--amber);
}

.cart-items {
    flex: 1 1 0; min-height: 72px; overflow-y: auto;
    padding: 8px 10px; display: flex; flex-direction: column; gap: 6px;
    background: #f8fafc;
    scrollbar-width: thin;
}
.cart-items-label {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 2px 2px;
    font-size: 10px; font-weight: 800; color: var(--text-3);
    text-transform: uppercase; letter-spacing: .05em; flex-shrink: 0;
}
.cart-items-label strong { color: var(--blue); font-family: var(--mono); font-weight: 700; }

.cart-item {
    position: relative;
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 10px;
    padding: 8px;
    display: grid;
    grid-template-columns: 40px 1fr auto;
    grid-template-rows: auto auto;
    gap: 4px 8px;
    align-items: center;
    box-shadow: 0 1px 2px rgba(15,23,42,.04);
}
.cart-item.just-added {
    border-color: var(--blue);
    background: #eff6ff;
    animation: cartPulse .6s ease;
}
@keyframes cartPulse {
    0% { box-shadow: 0 0 0 0 rgba(37,99,235,.45); }
    100% { box-shadow: 0 0 0 8px rgba(37,99,235,0); }
}
.cart-item.at-limit { border-color: var(--amber-border); background: var(--amber-bg); }
.ci-thumb {
    grid-row: 1 / 3; width: 40px; height: 40px; border-radius: 8px;
    background: var(--surface-2); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.ci-thumb img { width: 100%; height: 100%; object-fit: contain; padding: 3px; }
.ci-thumb span { font-size: 10px; font-weight: 800; color: var(--text-3); }
.ci-index { display: none; }
.ci-info { min-width: 0; grid-column: 2 / 3; }
.ci-name {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-1);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ci-sale-tag {
    flex-shrink: 0;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .04em;
    color: #fff;
    background: #e11d48;
    border-radius: 999px;
    padding: 2px 6px;
}
.cart-item.is-sale {
    background: #fff7f7;
    border-color: #fecdd3;
}
.ci-sku { font-size: 10px; color: var(--text-3); margin-top: 1px; font-family: var(--mono); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ci-unit { font-size: 10.5px; color: var(--text-3); margin-top: 1px; font-family: var(--mono); }
.ci-sub { font-family: var(--mono); font-size: 12px; font-weight: 700; text-align: right; color: var(--blue); grid-column: 3 / 4; grid-row: 1 / 2; }
.qty-ctrl {
    display: flex; align-items: center;
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 8px; overflow: hidden; grid-column: 2 / 3; width: fit-content;
}
.qty-btn {
    width: 26px; height: 26px; border: none; background: none;
    color: var(--text-2); font-size: 14px; font-weight: 700; cursor: pointer;
}
.qty-btn:hover { background: #eef2f7; color: var(--text-1); }
.qty-num {
    width: 26px; text-align: center;
    font-family: var(--mono); font-size: 12px; font-weight: 700; color: var(--text-1);
}
.ci-remove {
    width: 24px; height: 24px; border: none; border-radius: 6px;
    background: transparent; color: var(--text-3); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    grid-column: 3 / 4; grid-row: 2 / 3; justify-self: end;
}
.ci-remove:hover { color: var(--red); background: var(--red-bg); }

.cart-empty {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    text-align: center; padding: 18px 12px; color: var(--text-3);
    min-height: 100px;
}
.empty-ring {
    width: 44px; height: 44px; border-radius: 50%;
    border: 1.5px dashed #cbd5e1; background: var(--surface);
    display: flex; align-items: center; justify-content: center; margin-bottom: 8px;
}
.empty-ring svg { width: 18px; height: 18px; }
.empty-title { font-size: 12.5px; font-weight: 650; color: var(--text-2); }
.empty-sub { font-size: 11px; margin-top: 3px; }

.cart-extras {
    flex: 0 1 auto;
    min-height: 0;
    max-height: min(34vh, 260px);
    overflow-x: hidden;
    overflow-y: auto;
    border-top: 1px solid var(--border);
    background: var(--surface-2);
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
}
.extras-body, .extras-body.always-open {
    padding: 8px 10px 10px; display: flex; flex-direction: column; gap: 6px;
}
.field-label {
    display: block; margin-bottom: 3px;
    font-size: 10px; font-weight: 700; color: var(--text-3);
    text-transform: uppercase; letter-spacing: .04em;
}
.extras-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
}
.cust-phone-wrap { position: relative; }
.cust-input, .discount-input, .coupon-input {
    width: 100%; padding: 7px 8px;
    border: 1px solid var(--border); border-radius: 8px;
    background: var(--surface); color: var(--text-1);
    font-family: var(--font); font-size: 12px; outline: none;
}
.cust-input:focus, .discount-input:focus, .coupon-input:focus {
    border-color: var(--blue); box-shadow: 0 0 0 2px rgba(37,99,235,.12);
}
.discount-row, .coupon-row { display: flex; gap: 5px; align-items: center; }
.credit-mode-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 4px;
}
.credit-mode-card {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 10px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: var(--surface);
    min-height: 84px;
}
.credit-mode-card.is-baki-on {
    border-color: #f59e0b;
    background: #fffbeb;
}
.credit-mode-card.is-emi-on {
    border-color: #818cf8;
    background: #eef2ff;
}
.credit-mode-card.is-disabled {
    opacity: .55;
    pointer-events: none;
}
.credit-mode-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
}
.credit-mode-title {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--ink);
    line-height: 1.2;
}
.credit-mode-sub {
    margin-top: 3px;
    font-size: 10px;
    font-weight: 600;
    color: var(--slate);
    line-height: 1.35;
}
.credit-mode-card.is-baki-on .credit-mode-sub { color: #92400e; }
.credit-mode-card.is-emi-on .credit-mode-sub { color: #3730a3; }
.credit-toggle {
    flex-shrink: 0;
    min-width: 54px;
    height: 30px;
    padding: 0 12px;
    border-radius: 999px;
    border: 1.5px solid var(--border);
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .04em;
    cursor: pointer;
    transition: background .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease;
}
.credit-toggle:hover:not(:disabled) {
    border-color: #94a3b8;
    color: #334155;
}
.credit-toggle.is-on-baki {
    background: #b45309;
    border-color: #b45309;
    color: #fff;
    box-shadow: 0 4px 12px rgba(180, 83, 9, .25);
}
.credit-toggle.is-on-emi {
    background: #4f46e5;
    border-color: #4f46e5;
    color: #fff;
    box-shadow: 0 4px 12px rgba(79, 70, 229, .25);
}
.credit-toggle:disabled {
    cursor: not-allowed;
    opacity: .7;
}
.credit-mode-note {
    margin-top: 6px;
    font-size: 11px;
    font-weight: 650;
    color: #be123c;
    line-height: 1.4;
}
.emi-options {
    margin-top: 8px;
    padding: 10px;
    border-radius: 12px;
    border: 1px solid #c7d2fe;
    background: #eef2ff;
}
.emi-options-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
@media (max-width: 520px) {
    .credit-mode-grid { grid-template-columns: 1fr; }
    .emi-options-grid { grid-template-columns: 1fr; }
}
.type-toggle {
    display: flex; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; flex-shrink: 0;
}
.tt-btn {
    padding: 6px 8px; border: none; cursor: pointer;
    background: var(--surface); color: var(--text-3);
    font-family: var(--mono); font-size: 11px; font-weight: 650;
}
.tt-btn.active { background: var(--blue); color: #fff; }
.discount-input { flex: 1; font-family: var(--mono); font-weight: 600; min-width: 0; }
.coupon-input { flex: 1; font-family: var(--mono); text-transform: uppercase; min-width: 0; }
.coupon-apply-btn {
    padding: 7px 10px; border-radius: 8px; border: 1px solid var(--border);
    background: var(--surface); color: var(--text-2);
    font-size: 11px; font-weight: 650; cursor: pointer; font-family: var(--font);
}
.coupon-apply-btn:hover { color: var(--blue); border-color: var(--teal-border); background: var(--blue-soft); }
.coupon-applied {
    display: flex; justify-content: space-between; align-items: center;
    padding: 6px 8px; border-radius: 8px;
    background: var(--green-bg); border: 1px solid var(--green-border);
    font-size: 11px; font-weight: 650; color: var(--green);
}
.coupon-remove { border: none; background: none; color: var(--red); font-weight: 650; cursor: pointer; font-size: 11px; }
.discount-badge {
    flex-shrink: 0; font-family: var(--mono); font-size: 10.5px; font-weight: 650;
    color: var(--green); background: var(--green-bg);
    border: 1px solid var(--green-border); padding: 5px 6px; border-radius: 7px;
}

.cart-foot {
    flex: 0 0 auto;
    flex-shrink: 0;
    padding: 10px 12px 12px;
    border-top: 1px solid var(--border);
    background: var(--surface);
    z-index: 5;
    box-shadow: 0 -6px 16px rgba(15,23,42,.06);
    position: relative;
}
.summary-rows { display: flex; flex-direction: column; gap: 2px; margin-bottom: 6px; }
.sum-row { display: flex; justify-content: space-between; font-size: 11.5px; color: var(--text-2); }
.sum-val { font-family: var(--mono); font-weight: 600; }
.sum-row.discount .sum-val, .sum-row.exchange .sum-val { color: var(--green); }
.total-row {
    display: flex; justify-content: space-between; align-items: baseline;
    padding: 8px 0 10px; border-top: 1px dashed #dbe3ef;
}
.total-label { font-size: 12px; font-weight: 700; }
.total-val { font-family: var(--mono); font-size: 20px; font-weight: 700; color: var(--blue); }
.total-sym { font-size: 11px; color: var(--text-3); margin-right: 2px; }

.pay-methods { display: grid; grid-template-columns: 1.2fr .9fr .9fr; gap: 5px; margin-bottom: 6px; }
.pay-method {
    padding: 8px 6px; border-radius: 9px; cursor: pointer; font-family: var(--font);
    font-size: 12px; font-weight: 700; border: 1px solid var(--border);
    background: var(--surface-2); color: var(--text-2);
}
.pay-method.primary { background: var(--blue); color: #fff; border-color: var(--blue); }
.pay-method:hover:not(:disabled) { filter: brightness(.97); }
.pay-method:disabled { opacity: .45; cursor: not-allowed; }

.cart-actions { display: grid; grid-template-columns: 104px minmax(0, 1fr); gap: 8px; }
.hold-btn, .pay-btn {
    border-radius: 12px; cursor: pointer; font-family: var(--font); font-weight: 700;
    display: flex; align-items: center; justify-content: center; gap: 6px; transition: .15s;
}
.hold-btn {
    padding: 14px 8px; border: 1px solid var(--border);
    background: var(--surface-2); color: var(--text-2); font-size: 13px;
}
.hold-btn:hover { background: #eef2f7; color: var(--text-1); }
.hold-btn:disabled { opacity: .4; cursor: not-allowed; }
.hold-btn svg { width: 15px; height: 15px; }
.pay-btn {
    min-width: 0; padding: 14px 12px; border: none; background: var(--green); color: #fff; font-size: 14px;
    box-shadow: 0 10px 22px rgba(22,163,74,.28);
    flex-direction: row; justify-content: space-between; gap: 8px;
}
.pay-btn > span:first-child {
    min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.pay-btn:hover { background: #15803d; }
.pay-btn:disabled { background: #cbd5e1; color: #64748b; box-shadow: none; cursor: not-allowed; }
.pay-btn .pay-hint {
    flex-shrink: 0;
    font-size: 10.5px; font-family: var(--mono); font-weight: 600;
    opacity: .9; background: rgba(255,255,255,.2); padding: 2px 7px; border-radius: 6px;
}

/* Modal / kb / toast — keep functional, blue-tint accents */
.modal-overlay {
    position: fixed; inset: 0; z-index: 80;
    display: flex; align-items: center; justify-content: center;
    padding: 12px;
}
.modal-bg { position: absolute; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(6px); }
.modal {
    position: relative; z-index: 1; width: 100%; max-width: 400px;
    max-height: min(90vh, 680px);
    display: flex; flex-direction: column;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; box-shadow: 0 20px 50px rgba(15,23,42,.22);
    overflow: hidden;
    transform: translateZ(0);
}
.checkout-modal {
    max-width: 420px;
    max-height: min(88vh, 720px);
}
.modal-head {
    padding: 12px 14px; border-bottom: 1px solid var(--border);
    display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
    flex-shrink: 0; background: var(--surface);
}
.modal-title { font-size: 14px; font-weight: 700; letter-spacing: -.01em; }
.modal-subtitle { font-size: 11px; color: var(--text-3); margin-top: 2px; }
.modal-close {
    width: 28px; height: 28px; border-radius: 8px; border: 1px solid var(--border);
    background: var(--surface-2); color: var(--text-2); cursor: pointer;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.modal-close svg { width: 13px; height: 13px; }
.modal-body {
    padding: 12px 14px; display: flex; flex-direction: column; gap: 10px;
    overflow-y: auto; flex: 1 1 auto; min-height: 0;
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
}
.amount-display {
    background: var(--blue-soft); border: 1px solid var(--teal-border);
    border-radius: 10px; padding: 10px 12px; text-align: center;
}
.amount-label { font-size: 10px; font-weight: 700; color: var(--text-3); text-transform: uppercase; letter-spacing: .06em; }
.amount-value { font-family: var(--mono); font-size: 24px; font-weight: 700; margin-top: 2px; color: var(--blue); line-height: 1.2; }
.amount-value .ccy { font-size: 13px; color: var(--text-3); margin-right: 2px; font-weight: 600; }
.amount-note { font-size: 11px; font-weight: 650; margin-top: 3px; }
.warning-box {
    display: flex; gap: 7px; align-items: flex-start;
    background: var(--amber-bg); border: 1px solid var(--amber-border);
    border-radius: 10px; padding: 8px 10px;
}
.warning-box svg { width: 14px; height: 14px; color: var(--amber); flex-shrink: 0; margin-top: 1px; }
.warning-text { font-size: 11px; font-weight: 600; color: var(--amber); line-height: 1.4; }
.field-label, .field-label-pay { font-size: 10px; font-weight: 700; color: var(--text-2); text-transform: uppercase; letter-spacing: .05em; display: block; margin-bottom: 6px; }
.split-grid { display: flex; flex-direction: column; gap: 5px; }
.sp-row {
    display: flex; align-items: center; justify-content: space-between;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 9px; padding: 7px 10px;
}
.sp-row:focus-within { border-color: var(--blue); box-shadow: 0 0 0 2px rgba(37,99,235,.12); }
.sp-label { font-size: 12px; font-weight: 650; display: flex; align-items: center; gap: 8px; }
.sp-input {
    width: 100px; text-align: right; border: none; outline: none; background: transparent;
    font-family: var(--mono); font-size: 14px; font-weight: 600; color: var(--text-1);
}
.sp-input::-webkit-inner-spin-button, .sp-input::-webkit-outer-spin-button { display: none; }
.qc-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; margin-top: 6px; }
.qc-btn {
    padding: 7px 4px; border: 1px solid var(--border); border-radius: 8px;
    background: var(--surface); color: var(--text-2);
    font-family: var(--mono); font-size: 11px; font-weight: 650; cursor: pointer;
}
.qc-btn:hover { border-color: var(--blue); color: var(--blue); background: var(--blue-soft); }
.qc-btn.exact { background: var(--navy); color: #fff; border-color: var(--navy); }
.change-box {
    display: flex; justify-content: space-between; align-items: center;
    padding: 9px 11px; border-radius: 9px; border: 1px solid;
}
.change-box.ok  { background: var(--green-bg); border-color: var(--green-border); }
.change-box.err { background: var(--red-bg); border-color: var(--red-border); }
.change-lbl { font-size: 11.5px; font-weight: 650; }
.change-box.ok .change-lbl { color: #166534; }
.change-box.err .change-lbl { color: var(--red); }
.change-val { font-family: var(--mono); font-size: 15px; font-weight: 700; }
.change-box.ok .change-val { color: var(--green); }
.change-box.err .change-val { color: var(--red); }
.err-hint { font-size: 11px; color: var(--red); text-align: center; font-weight: 650; margin-top: -2px; }
.pay-summary {
    display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px;
    border: 1px solid var(--border); border-radius: 10px; overflow: hidden;
    background: var(--surface);
}
.pay-summary-cell {
    padding: 8px 8px; text-align: center;
    border-right: 1px solid var(--border);
}
.pay-summary-cell:last-child { border-right: none; }
.pay-summary-cell .ps-label {
    font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: var(--text-3);
}
.pay-summary-cell .ps-val {
    margin-top: 3px; font-family: var(--mono); font-size: 12.5px; font-weight: 700; color: var(--text-1);
}
.pay-summary-cell.due .ps-val { color: var(--blue); }
.pay-summary-cell.paid .ps-val { color: #0f172a; }
.pay-summary-cell.change.ok .ps-val { color: var(--green); }
.pay-summary-cell.change.err .ps-val { color: var(--red); }
.pay-summary-cell.change.less .ps-val { color: #b45309; }
.pay-summary-cell.change.less .ps-label { color: #b45309; }

.invoice-overlay { z-index: 90; }
.invoice-modal {
    max-width: 420px; max-height: min(94vh, 720px);
    display: flex; flex-direction: column;
}
.invoice-frame-wrap {
    background: #f1f5f9; border-bottom: 1px solid var(--border);
    flex: 1 1 auto; min-height: 0; overflow: hidden;
    display: flex; flex-direction: column;
}
.invoice-frame {
    width: 100%; flex: 1 1 auto; min-height: 360px; border: 0; background: #fff;
}
.invoice-meta {
    display: grid; grid-template-columns: 1fr 1fr; gap: 6px;
    padding: 10px 14px; border-bottom: 1px solid var(--border); background: var(--surface-2);
    flex-shrink: 0;
}
.invoice-meta-item {
    background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 7px 8px;
}
.invoice-meta-item .im-label { font-size: 9px; font-weight: 800; color: var(--text-3); text-transform: uppercase; }
.invoice-meta-item .im-val { margin-top: 2px; font-size: 12px; font-weight: 700; font-family: var(--mono); color: var(--text-1); }
.invoice-meta-item.change .im-val { color: var(--green); }
.modal-foot {
    padding: 10px 14px 12px; display: flex; gap: 7px;
    flex: 0 0 auto; flex-shrink: 0;
    border-top: 1px solid var(--border); background: var(--surface);
    position: sticky; bottom: 0; z-index: 6;
    box-shadow: 0 -8px 18px rgba(15,23,42,.05);
}
.modal-cancel, .modal-confirm {
    flex: 1; padding: 9px 10px; border-radius: 10px; cursor: pointer;
    font-family: var(--font); font-size: 12.5px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; gap: 5px;
}
.modal-cancel { background: var(--surface-2); border: 1px solid var(--border); color: var(--text-2); }
.modal-confirm { background: var(--green); border: none; color: #fff; box-shadow: 0 4px 12px rgba(22,163,74,.22); }
.modal-confirm:hover { background: #15803d; }
.modal-confirm:disabled { background: #cbd5e1; color: #64748b; box-shadow: none; cursor: not-allowed; }
.modal-confirm svg { width: 14px; height: 14px; }
.modal-kbd {
    font-size: 9px; font-family: var(--mono); font-weight: 700;
    padding: 1px 5px; border-radius: 4px; border: 1px solid currentColor; opacity: .75;
}

.kb-overlay {
    position: fixed; inset: 0; z-index: 100;
    background: rgba(15,23,42,.55); backdrop-filter: blur(10px);
    display: flex; align-items: center; justify-content: center; padding: 20px;
}
.kb-modal {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 18px; width: 100%; max-width: 460px; overflow: hidden;
    box-shadow: 0 20px 50px rgba(15,23,42,.2);
}
.kb-head {
    padding: 18px 20px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.kb-title { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.kb-title svg { width: 17px; height: 17px; color: var(--blue); }
.kb-grid { padding: 14px 16px 18px; display: flex; flex-direction: column; gap: 6px; }
.kb-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-2);
}
.kb-desc { font-size: 13px; font-weight: 600; }
.key {
    font-family: var(--mono); font-size: 11px; font-weight: 650;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 6px; padding: 3px 8px; color: var(--text-1);
}

.toast-dock {
    position: fixed; bottom: 18px; right: 18px; z-index: 200;
    display: flex; flex-direction: column-reverse; gap: 7px;
    pointer-events: none; max-width: 320px;
}
.toast {
    display: flex; align-items: center; gap: 9px;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 11px 14px;
    box-shadow: 0 16px 40px rgba(15,23,42,.14); pointer-events: all;
}
.toast-bar { width: 3px; height: 30px; border-radius: 2px; flex-shrink: 0; }
.toast-msg { font-size: 13px; font-weight: 600; line-height: 1.35; }
.toast.success .toast-bar { background: var(--green); }
.toast.error .toast-bar { background: var(--red); }
.toast.warning .toast-bar { background: var(--amber); }
.toast.info .toast-bar { background: var(--blue); }

@media (max-width: 1400px) {
    .product-list { grid-template-columns: repeat(5, minmax(0, 1fr)); }
}
@media (max-width: 1180px) {
    .product-list { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
/* Counter tills: keep side-by-side on large screens; stack earlier for tablets/phones. */
@media (max-width: 960px) {
    .pos-root {
        grid-template-columns: minmax(0, 1.15fr) minmax(280px, 1fr);
        gap: 8px;
        padding: 8px;
    }
    .pos-chrome-search { max-width: 420px; }
    .pos-tool-btn span.pos-tool-label { display: none; }
    .product-list { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .product-card { min-height: 230px; }
    .product-list.is-list .product-card { min-height: 76px !important; }
}
@media (max-width: 768px) {
    .pos-body { grid-template-columns: minmax(0, 1fr); }
    .pos-root {
        grid-template-columns: 1fr;
        grid-template-rows: minmax(40vh, 1fr) minmax(46vh, 1.05fr);
        gap: 6px;
    }
    .pos-user-meta, .pos-brand span:not(.pos-brand-mark) { display: none; }
    .pos-home-btn { padding: 0 10px; font-size: 0; gap: 0; }
    .pos-home-btn svg { width: 16px; height: 16px; }
    .product-list { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
    .p-img-wrap { height: 96px; min-height: 96px; flex: 0 0 96px; }
    .product-card { min-height: 220px; }
    .product-list.is-list .product-card { min-height: 76px !important; }
    .p-body { min-height: 100px; }
    .cart-actions { grid-template-columns: 1fr; }
    .modal { width: min(100vw - 16px, 480px) !important; max-height: min(92vh, 720px); }
}
@media (max-width: 480px) {
    .pos-body { grid-template-columns: 1fr; }
    .pos-root {
        grid-template-columns: 1fr;
        grid-template-rows: minmax(36vh, 1fr) minmax(50vh, 1.15fr);
        padding: 6px;
    }
    .product-list { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px; }
    .product-card { min-height: 210px; }
    .product-list.is-list .product-card { min-height: 76px !important; }
}
</style>

<div class="pos-shell"
     x-data="posSystem()"
     :data-theme="darkMode ? 'dark' : 'light'"
     @keydown.window="handleKeydown($event)">

        <div class="pos-chrome">
        <a href="{{ route('dashboard') }}" class="pos-brand" title="Dashboard">
            @php
                $posSettings = \App\Models\SiteSetting::current();
                $posIcon = $posSettings->favicon_path
                    ? public_storage_url($posSettings->favicon_path)
                    : ($posSettings->logo_path ? public_storage_url($posSettings->logo_path) : null);
            @endphp
            <span class="pos-brand-mark">
                @if($posIcon)
                    <img src="{{ $posIcon }}" alt="" style="width:100%;height:100%;object-fit:contain;border-radius:8px;background:#000;">
                @else
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                @endif
            </span>
            <span>{{ Auth::user()->shop->name ?? config('app.name', 'Maks Gadget') }} <em>POS</em></span>
        </a>

        <div class="pos-chrome-search">
            <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="search" x-ref="searchInput" autofocus
                   placeholder="Search by product name, barcode / SKU..."
                   class="search-input"
                   @input="onSearchInput()"
                   @keydown.enter.prevent="onSearchEnter()">
            <span class="kbd">Ctrl K</span>
        </div>

        <div class="pos-chrome-right">
            <a href="{{ route('dashboard') }}" class="pos-home-btn" title="Home / Dashboard">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Home
            </a>
            <button type="button" class="pos-tool-btn" @click="$refs.searchInput.focus()" title="Focus barcode / search">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6.5 0a5.5 5.5 0 11-11 0 5.5 5.5 0 0111 0zM4 8V6a2 2 0 012-2h2m8 0h2a2 2 0 012 2v2"/></svg>
                <span class="pos-tool-label">Scan</span>
            </button>
            <button type="button" class="pos-tool-btn" @click="kbOpen = true" title="Keyboard shortcuts">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>
            <button type="button" class="pos-tool-btn" @click="toggleFullscreen()"
                    :title="isFullscreen ? 'Exit fullscreen (F11)' : 'Fullscreen (F11)'"
                    :class="isFullscreen ? 'is-active' : ''">
                <svg x-show="!isFullscreen" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                <svg x-show="isFullscreen" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/></svg>
            </button>
            <button type="button" class="pos-tool-btn" @click="toggleDark()" :title="darkMode ? 'Light' : 'Dark'">
                <svg x-show="!darkMode" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-show="darkMode" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
            @if(!empty($openSession))
                <a href="{{ route('counters.sessions.close-form', $openSession) }}" class="pos-close-day" title="Close day">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Close day
                </a>
            @endif
            <div class="status-badge" :class="isOnline ? 'online' : 'offline'"
                 @click="isOnline && pendingOfflineCount() > 0 && (syncPromptOpen = true)"
                 :title="pendingOfflineCount() > 0 ? (pendingOfflineCount() + ' offline bill(s) waiting') : ''"
                 :style="pendingOfflineCount() > 0 ? 'cursor:pointer' : ''">
                <span class="status-dot"></span>
                <span x-text="isOnline ? 'Online' : 'Offline'"></span>
                <span x-show="pendingOfflineCount() > 0" x-cloak
                      style="margin-left:4px;background:rgba(255,255,255,.2);padding:0 5px;border-radius:999px;font-size:10px;font-weight:800"
                      x-text="pendingOfflineCount()"></span>
            </div>
            <div class="pos-user">
                <div class="pos-user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div class="pos-user-meta">
                    <div class="pos-user-name">{{ Auth::user()->name }}</div>
                    @if(Auth::user()->isAdminUser())
                        <div class="pos-user-role" x-text="selectedCounterLabel()"></div>
                    @else
                        <div class="pos-user-role">{{ Auth::user()->counter->name ?? 'Counter' }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Counter fullscreen prompt (browser blocks auto-fullscreen without a click) --}}
    <div x-show="showFullscreenHint && !isFullscreen" x-cloak
         class="pos-fs-hint"
         x-transition.opacity>
        <div class="pos-fs-hint-inner">
            <div>
                <strong>Counter mode</strong>
                <span>Use the full screen so the register never shrinks into a small layout.</span>
            </div>
            <div class="pos-fs-hint-actions">
                <button type="button" class="pos-fs-btn" @click="enterCounterFullscreen()">Enter fullscreen</button>
                <button type="button" class="pos-fs-dismiss" @click="dismissFullscreenHint()">Not now</button>
            </div>
        </div>
    </div>

    <div class="pos-body">
        <div class="pos-root">
    <!-- LEFT PANEL - Products -->
    <div class="panel-left">
        <div class="panel-top">
            <div class="filter-label-row">
                <div class="filter-label">Categories</div>
            </div>
            <div class="cat-scroll-wrap" @click.outside="openCatId = null">
                <div class="cat-scroll">
                    <div class="cat-card-wrap">
                        <button type="button" class="cat-card" :class="selectedCategory === 'all' && selectedBrand === 'all' ? 'active' : ''" @click="pickAllProducts()">
                            <div class="cat-card-icon" x-html="categoryIconSvg({ icon: 'all', name: 'All' })"></div>
                            <div class="cat-card-name">All</div>
                            <div class="cat-card-count" x-text="products.length"></div>
                        </button>
                    </div>

                    <template x-for="cat in categories" :key="'cat-card-' + cat.id">
                        <div class="cat-card-wrap" :class="openCatId === String(cat.id) && 'open'">
                            <button type="button" class="cat-card"
                                    :class="selectedCategory == String(cat.id) ? 'active' : ''"
                                    @click="pickCategory(cat.id)">
                                <div class="cat-card-icon" x-html="categoryIconSvg(cat)"></div>
                                <div class="cat-card-name" x-text="cat.name" :title="cat.name"></div>
                                <div class="cat-card-count" x-text="categoryProductCount(cat.id)"></div>
                            </button>
                            <button type="button" class="cat-card-caret"
                                    title="Show brands"
                                    @click.stop="toggleCatMenu(cat.id, $event)">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div class="cat-brand-menu"
                                 x-show="openCatId === String(cat.id)"
                                 x-cloak
                                 @click.stop
                                 :style="catMenuStyle">
                                <div class="cat-brand-menu-title" x-text="'Brands · ' + brandsForCategory(cat.id).length"></div>
                                <button type="button" class="cat-brand-item"
                                        :class="selectedCategory == String(cat.id) && selectedBrand === 'all' && 'active'"
                                        @click="pickBrand(cat.id, 'all')">
                                    <span>All brands</span>
                                    <span x-text="categoryProductCount(cat.id)"></span>
                                </button>
                                <template x-for="brand in brandsForCategory(cat.id)" :key="'cat-brand-' + cat.id + '-' + brand.id">
                                    <button type="button" class="cat-brand-item"
                                            :class="selectedCategory == String(cat.id) && selectedBrand == String(brand.id) && 'active'"
                                            @click="pickBrand(cat.id, brand.id)">
                                        <span x-text="brand.name"></span>
                                        <span x-text="brandProductCount(cat.id, brand.id)"></span>
                                    </button>
                                </template>
                                <button type="button" class="cat-brand-item"
                                        x-show="unbrandedCount(cat.id) > 0"
                                        :class="selectedCategory == String(cat.id) && selectedBrand === 'none' && 'active'"
                                        @click="pickBrand(cat.id, 'none')">
                                    <span>No brand</span>
                                    <span x-text="unbrandedCount(cat.id)"></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="pos-toolbar">
                <div class="pos-toolbar-search">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                    <input type="text" x-model="catalogQuery" @input="page = 1" placeholder="Search products...">
                </div>

                <select class="pos-toolbar-select" x-model="selectedBrand" @change="page = 1">
                    <option value="all">All Brands</option>
                    <template x-for="brand in brandsForCurrentView()" :key="'tb-brand-' + brand.id">
                        <option :value="String(brand.id)" x-text="brand.name"></option>
                    </template>
                    <option value="none" x-show="unbrandedCount(selectedCategory === 'all' ? null : selectedCategory) > 0">No brand</option>
                </select>

                <select class="pos-toolbar-select" x-model="sortBy" @change="page = 1">
                    <option value="name">Sort by: Name A-Z</option>
                    <option value="name_desc">Sort by: Name Z-A</option>
                    <option value="price_asc">Sort by: Price Low-High</option>
                    <option value="price_desc">Sort by: Price High-Low</option>
                    <option value="stock">Sort by: Stock</option>
                </select>

                <div class="pos-view-toggle" role="group" aria-label="View mode">
                    <button type="button" class="pos-view-btn" :class="viewMode === 'grid' && 'active'" @click="setViewMode('grid')" title="Grid view">
                        <svg fill="currentColor" viewBox="0 0 24 24"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                    </button>
                    <button type="button" class="pos-view-btn" :class="viewMode === 'list' && 'active'" @click="setViewMode('list')" title="List view">
                        <svg fill="currentColor" viewBox="0 0 24 24"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="product-stage">
            <div class="product-list" :class="viewMode === 'list' && 'is-list'">
                <template x-for="product in paginatedProducts()" :key="product.id">
                    <div @click="addToCart(product)"
                         class="product-card"
                         :class="{
                            'out-of-stock': product.stock_quantity < 1,
                            'in-cart': cartQty(product.id) > 0,
                            'is-sale': !!product.on_sale
                         }">

                        <div class="p-selected-badge"
                             x-show="cartQty(product.id) > 0"
                             x-text="cartQty(product.id)"
                             x-cloak></div>

                        <div class="p-sale-badge"
                             x-show="product.on_sale"
                             x-cloak
                             x-text="(product.sale_percent ? ('-' + product.sale_percent + '%') : 'SALE')"></div>

                        <div class="p-img-wrap">
                            <div class="p-img-fallback" x-text="productInitials(product.name)"></div>
                            <img :src="product.image_url"
                                 :alt="product.name"
                                 width="200"
                                 height="200"
                                 loading="eager"
                                 decoding="async"
                                 x-on:error="recoverProductImage($event, product)">
                            <button type="button"
                                    class="p-view-btn"
                                    x-show="cartQty(product.id) > 0"
                                    x-cloak
                                    @click.stop="openCartView()"
                                    title="View selected cart items">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                View
                            </button>
                        </div>

                        <div class="p-body">
                            <div class="p-meta">
                                <div class="p-name" x-text="product.name" :title="product.name"></div>
                                <div class="p-sku" x-text="product.sku || product.barcode || '—'"></div>
                            </div>
                            <div class="p-foot">
                                <div class="p-price-wrap">
                                    <div class="p-price-list"
                                         x-show="product.on_sale && Number(product.list_price) > Number(product.selling_price)"
                                         x-cloak>
                                        Tk<span x-text="formatNumber(product.list_price)"></span>
                                    </div>
                                    <div class="p-price" :class="product.on_sale && 'is-sale-price'">
                                        <span class="p-price-sym">Tk</span><span x-text="formatNumber(product.selling_price)"></span>
                                    </div>
                                </div>
                                <div class="p-stock"
                                     :class="product.stock_quantity < 1 ? 'stock-out' : product.stock_quantity < 5 ? 'stock-low' : 'stock-ok'">
                                    <span class="stock-dot"></span>
                                    <span x-text="product.stock_quantity < 1 ? 'Out of stock' : ('Stock: ' + product.stock_quantity)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div x-show="filteredProducts().length === 0"
                     style="grid-column:1/-1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 16px;color:var(--text-3);text-align:center">
                    <p style="font-size:14px;font-weight:650;color:var(--text-2)">No products found</p>
                    <p style="font-size:12.5px;margin-top:4px">Try another category, brand, or search.</p>
                </div>
            </div>

            <div class="pos-pager" x-show="filteredProducts().length > 0" x-cloak>
                <div class="pos-pager-meta"
                     x-text="'Showing ' + pageFrom() + ' to ' + pageTo() + ' of ' + filteredProducts().length + ' products'"></div>
                <div class="pos-pager-pages">
                    <button type="button" class="pos-page-btn" :disabled="currentPage() <= 1" @click="goPage(currentPage() - 1)">Prev</button>
                    <template x-for="p in pageNumbers()" :key="'page-' + p">
                        <button type="button" class="pos-page-btn"
                                :class="p === currentPage() && 'active'"
                                x-text="p === '…' ? '…' : p"
                                :disabled="p === '…'"
                                @click="p !== '…' && goPage(p)"></button>
                    </template>
                    <button type="button" class="pos-page-btn" :disabled="currentPage() >= totalPages()" @click="goPage(currentPage() + 1)">Next</button>
                </div>
            </div>
        </div>
    </div>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         RIGHT PANEL - Cart
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="panel-right">

        <!-- Cart Header -->
        <div class="cart-head">
            <div class="cart-title">
                <div class="cart-badge-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                Cart
                <span class="cart-count" x-show="cartUnitCount() > 0" x-text="cartUnitCount()" x-cloak></span>
            </div>
            <div class="cart-head-actions">
                <button type="button"
                        @click="openCartView()"
                        x-show="cart.length > 0"
                        x-cloak
                        class="view-cart-btn"
                        title="View all selected items">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    View
                </button>
                <button @click="holdCartsModalOpen = true"
                    x-show="heldCarts.length > 0"
                    x-cloak
                    class="held-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-text="heldCarts.length"></span> Held
                </button>
                <button @click="clearCart()" x-show="cart.length > 0" class="clear-btn">Clear Cart</button>
            </div>
        </div>

        <!-- Exchange Mode Banner -->
        <div x-show="isExchangeMode" x-cloak class="exchange-banner">
            <div style="display:flex;align-items:center;gap:7px">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Exchange Mode
            </div>
            <span>Credit: Tk<span x-text="formatNumber(exchangeCredit)"></span></span>
        </div>

        <div class="cart-items" id="pos-cart-items">
            <div class="cart-items-label" x-show="cart.length > 0" x-cloak>
                <span>Selected items</span>
                <button type="button" @click="openCartView()"
                        style="border:0;background:none;color:var(--blue);font-size:11px;font-weight:800;cursor:pointer;text-transform:none;letter-spacing:0">
                    View all →
                </button>
            </div>
            <template x-for="(item, index) in cart" :key="item.id">
                <div class="cart-item" :class="{ 'at-limit': item.qty >= item.max_stock, 'just-added': lastAddedId === item.id, 'is-sale': !!item.on_sale }">
                    <div class="ci-thumb">
                        <template x-if="item.image_url">
                            <img :src="item.image_url" :alt="item.name" referrerpolicy="no-referrer"
                                 x-on:error="recoverCartImage($event, item)">
                        </template>
                        <template x-if="!item.image_url">
                            <span x-text="productInitials(item.name)"></span>
                        </template>
                    </div>
                    <div class="ci-info">
                        <div class="ci-name">
                            <span x-text="item.name" :title="item.name"></span>
                            <span class="ci-sale-tag" x-show="item.on_sale" x-cloak>SALE</span>
                        </div>
                        <div class="ci-sku" x-show="item.color || item.ram || item.storage" x-cloak
                             x-text="[item.color, [item.ram, item.storage].filter(Boolean).join('/')].filter(Boolean).join(' · ')"></div>
                        <div class="ci-sku" x-show="item.sku || item.barcode" x-text="item.sku || item.barcode" x-cloak></div>
                        <div class="ci-sku" x-show="item.requires_imei && item.imeis && item.imeis.length" x-cloak
                             style="color:#c2410c;font-weight:600"
                             x-text="'IMEI: ' + (item.imeis || []).join(', ')"></div>
                        <div class="ci-unit">
                            <span x-show="item.on_sale && Number(item.list_price) > Number(item.sale_price)" x-cloak style="text-decoration:line-through;color:var(--text-3);margin-right:6px">
                                Tk<span x-text="formatNumber(item.list_price)"></span>
                            </span>
                            <span>Tk<span x-text="formatNumber(item.on_sale ? item.list_price : item.price)"></span> each</span>
                            <span x-show="item.on_sale" x-cloak style="color:#e11d48;font-weight:800;margin-left:6px">
                                → Tk<span x-text="formatNumber(item.sale_price)"></span>
                            </span>
                        </div>
                    </div>
                    <div class="ci-sub">Tk<span x-text="formatNumber((item.on_sale ? item.list_price : item.price) * item.qty)"></span></div>
                    <div class="qty-ctrl">
                        <button @click.stop="updateQty(index, -1)" class="qty-btn">-</button>
                        <div class="qty-num" x-text="item.qty"></div>
                        <button @click.stop="updateQty(index, 1)" class="qty-btn">+</button>
                    </div>
                    <button @click.stop="removeItem(index)" class="ci-remove" title="Remove">
                        <svg style="width:15px;height:15px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </template>

            <template x-if="cart.length === 0">
                <div class="cart-empty">
                    <div class="empty-ring">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="empty-title">Cart is empty</div>
                    <div class="empty-sub">Tap a product — it will appear here</div>
                </div>
            </template>
        </div>

        <div class="cart-extras">
            <div class="extras-body always-open">
                <div class="field-label" x-text="(isBaki || isEmi) ? 'Customer (required)' : 'Customer (optional)'"></div>
                <div class="extras-grid">
                    <div class="cust-phone-wrap">
                        <input type="text" x-model="customerPhone" @input.debounce.500ms="searchCustomer()" placeholder="Mobile number" class="cust-input"
                               :style="(isBaki || isEmi) && !customerPhone ? 'border-color:#dc2626' : ''">
                        <div x-show="isSearchingCustomer" x-cloak style="position:absolute;right:8px;top:50%;transform:translateY(-50%)">
                            <svg class="animate-spin" style="width:13px;height:13px;color:var(--teal)" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                        </div>
                    </div>
                    <input type="text" x-model="customerName" placeholder="Customer name" class="cust-input"
                           :style="(isBaki || isEmi) && !customerName ? 'border-color:#dc2626' : ''">
                </div>

                <div class="field-label" style="margin-top:4px">Credit mode</div>
                <div class="credit-mode-grid">
                    <div class="credit-mode-card" x-show="posBakiEnabled" x-cloak
                         :class="{ 'is-baki-on': isBaki }">
                        <div class="credit-mode-top">
                            <div>
                                <div class="credit-mode-title">Baki</div>
                                <div class="credit-mode-sub" x-text="isBaki ? 'Pay later credit' : 'Off'"></div>
                            </div>
                            <button type="button"
                                    class="credit-toggle"
                                    :class="{ 'is-on-baki': isBaki }"
                                    @click="toggleBaki()"
                                    x-text="isBaki ? 'ON' : 'OFF'"></button>
                        </div>
                        <div x-show="customerBakiBalance > 0" x-cloak style="font-size:11px;font-weight:700;color:#b45309">
                            Due: Tk<span x-text="formatNumber(customerBakiBalance)"></span>
                        </div>
                    </div>

                    <div class="credit-mode-card" x-show="posEmiEnabled" x-cloak
                         :class="{ 'is-emi-on': isEmi }">
                        <div class="credit-mode-top">
                            <div>
                                <div class="credit-mode-title">EMI</div>
                                <div class="credit-mode-sub" x-text="isEmi ? 'Installment plan' : 'Off'"></div>
                            </div>
                            <button type="button"
                                    class="credit-toggle"
                                    :class="{ 'is-on-emi': isEmi }"
                                    @click="toggleEmi()"
                                    x-text="isEmi ? 'ON' : 'OFF'"></button>
                        </div>
                        <div x-show="customerEmiBalance > 0" x-cloak style="font-size:11px;font-weight:700;color:#4338ca">
                            Due: Tk<span x-text="formatNumber(customerEmiBalance)"></span>
                        </div>
                    </div>
                </div>
                <p class="credit-mode-note" x-show="!posEmiEnabled && !posBakiEnabled" x-cloak>
                    EMI and Baki are turned off in POS Modes.
                </p>

                <div class="emi-options" x-show="isEmi && posEmiEnabled" x-cloak>
                    <div class="emi-options-grid">
                        <div>
                            <label class="field-label" style="margin-bottom:4px">Months</label>
                            <input type="number" min="1" max="36" step="1" x-model.number="emiMonths" class="cust-input" style="width:100%" placeholder="e.g. 8">
                        </div>
                        <div>
                            <label class="field-label" style="margin-bottom:4px">Down payment (Tk)</label>
                            <input type="number" min="0" step="0.01" x-model.number="emiDownPayment" class="cust-input" style="width:100%" placeholder="0">
                        </div>
                    </div>
                    <div class="emi-rest-line">
                        Rest amount (EMI): Tk<span x-text="formatNumber(getEmiPrincipal())"></span>
                        <span x-show="emiMonths > 0"> · <span x-text="emiMonths"></span> months</span>
                    </div>
                </div>

                <div class="field-label" style="margin-top:2px">Discount</div>
                <div class="discount-row">
                    <div class="type-toggle">
                        <button type="button" @click="discountType = 'percent'" class="tt-btn" :class="discountType === 'percent' ? 'active' : ''">%</button>
                        <button type="button" @click="discountType = 'flat'" class="tt-btn" :class="discountType === 'flat' ? 'active' : ''">Tk</button>
                    </div>
                    <input type="number"
                           x-model.number="discountValue"
                           class="discount-input"
                           :placeholder="discountType === 'percent' ? '%' : 'Amount'"
                           min="0"
                           :max="discountType === 'percent' ? 100 : getDiscountBase()">
                    <span x-show="getDiscount() > 0" class="discount-badge" x-cloak>
                        -Tk<span x-text="formatNumber(getDiscount())"></span>
                    </span>
                </div>
                <p x-show="hasSaleItems()" x-cloak style="margin:6px 0 0;font-size:11px;font-weight:650;color:#be123c">
                    Sale savings included (<span x-text="salePercentLabel()"></span>). You can still add more discount.
                </p>
                <div class="coupon-row">
                    <input type="text" x-model="couponCode" @keyup.enter="applyCoupon()" placeholder="Coupon code" class="coupon-input">
                    <button type="button" @click="applyCoupon()" class="coupon-apply-btn">Apply</button>
                </div>
                <div x-show="appliedCoupon" x-cloak class="coupon-applied">
                    <span>Coupon <strong x-text="appliedCoupon?.code"></strong> applied</span>
                    <button type="button" @click="removeCoupon()" class="coupon-remove">Remove</button>
                </div>
            </div>
        </div>
        <!-- Cart Footer -->
        <div class="cart-foot">
            <div class="summary-rows">
                <div class="sum-row">
                    <span class="sum-label">Subtotal (<span x-text="cartUnitCount()"></span> items)</span>
                    <span class="sum-val">Tk<span x-text="formatNumber(getTotal())"></span></span>
                </div>
                <div class="sum-row discount" x-show="getDiscount() > 0">
                    <span class="sum-label">Discount</span>
                    <span class="sum-val">-Tk<span x-text="formatNumber(getDiscount())"></span></span>
                </div>
                <div class="sum-row exchange" x-show="isExchangeMode && exchangeCredit > 0" style="display:none">
                    <span class="sum-label">Exchange Credit</span>
                    <span class="sum-val">-Tk<span x-text="formatNumber(exchangeCredit)"></span></span>
                </div>
                <div class="sum-row">
                    <span class="sum-label">Tax</span>
                    <span class="sum-val" style="color:var(--text-3)">Included</span>
                </div>
            </div>

            <div class="total-row">
                <div class="total-label">Total</div>
                <div class="total-val">
                    <span class="total-sym">Tk</span><span x-text="formatNumber(getPayableTotal())"></span>
                </div>
            </div>

            <div class="pay-methods">
                <button type="button" class="pay-method primary" @click="openCheckout()" :disabled="!canProceedToCheckout()">Cash</button>
                <button type="button" class="pay-method" @click="openCheckout()" :disabled="!canProceedToCheckout()">Card</button>
                <button type="button" class="pay-method" @click="openCheckout()" :disabled="!canProceedToCheckout()">Other</button>
            </div>

            <div class="cart-actions">
                <button @click="suspendCurrentCart()" :disabled="cart.length === 0" class="hold-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Hold
                </button>
                <button @click="openCheckout()" :disabled="!canProceedToCheckout()" class="pay-btn">
                    <span style="display:inline-flex;align-items:center;gap:6px">
                        Pay Tk<span x-text="formatNumber(getPayableTotal())"></span>
                    </span>
                    <span class="pay-hint">F2</span>
                </button>
            </div>
        </div>
    </div>
        </div>
    </div>

    <!-- CART VIEW MODAL — edit qty & price for all selected items -->
    <div x-show="cartViewModalOpen" style="display:none" class="modal-overlay"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="modal-bg" @click="cartViewModalOpen = false"></div>
        <div class="modal cart-view-modal"
             x-show="cartViewModalOpen"
             @click.stop
             x-transition:enter="ease-out duration-250" x-transition:enter-start="opacity-0 scale-95 translate-y-3" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Edit cart items</div>
                    <div class="modal-subtitle" x-text="cart.length + ' selected · change price or quantity'"></div>
                </div>
                <button type="button" @click="cartViewModalOpen = false" class="modal-close" aria-label="Close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <template x-if="cart.length === 0">
                    <div style="text-align:center;padding:48px 12px;color:var(--text-3)">
                        <div style="font-weight:750;color:var(--text-2)">No items in cart</div>
                        <div style="font-size:12.5px;margin-top:4px">Tap products on the left to add them.</div>
                    </div>
                </template>

                <div class="cart-view-list" x-show="cart.length > 0">
                    <template x-for="(item, index) in cart" :key="'cv-' + item.id">
                        <div class="cart-view-item">
                            <div class="cv-product">
                                <div class="cv-thumb">
                                    <template x-if="item.image_url">
                                        <img :src="item.image_url" :alt="item.name">
                                    </template>
                                    <template x-if="!item.image_url">
                                        <span x-text="productInitials(item.name)" style="font-size:12px;font-weight:800;color:#94a3b8"></span>
                                    </template>
                                </div>
                                <div class="cv-info">
                                    <div class="cv-name">
                                        <span x-text="item.name" :title="item.name"></span>
                                        <span class="ci-sale-tag" x-show="item.on_sale" x-cloak>SALE</span>
                                    </div>
                                    <div class="cv-sku" x-text="item.sku || item.barcode || '—'"></div>
                                    <button type="button" class="cv-reset"
                                            x-show="!item.on_sale && item.list_price && Number(item.list_price) !== Number(item.price)"
                                            x-cloak
                                            @click="resetCartPrice(index)">
                                        Reset to list Tk<span x-text="formatNumber(item.list_price)"></span>
                                    </button>
                                    <div x-show="item.on_sale" x-cloak style="margin-top:4px;font-size:11px;font-weight:700;color:#be123c">
                                        Sale Tk<span x-text="formatNumber(item.sale_price)"></span>
                                        <span x-show="item.sale_percent"> · <span x-text="item.sale_percent"></span>% off</span>
                                    </div>
                                </div>
                            </div>

                            <div class="cv-field cv-price-cell">
                                <label class="cv-field-label" x-text="item.on_sale ? 'Actual price' : 'Unit price'"></label>
                                <div class="cv-price-wrap">
                                    <span class="cv-currency">Tk</span>
                                    <input type="number" class="cv-input" min="0" step="0.01"
                                           :value="item.price"
                                           :readonly="item.on_sale"
                                           :style="item.on_sale ? 'opacity:.75;cursor:not-allowed;background:#fff7ed' : ''"
                                           @change="setCartPrice(index, $event.target.value)"
                                           @keydown.enter.prevent="setCartPrice(index, $event.target.value)">
                                </div>
                            </div>

                            <div class="cv-field cv-qty-cell">
                                <label class="cv-field-label">Qty <span style="font-weight:650;text-transform:none;letter-spacing:0" x-text="'(max ' + item.max_stock + ')'"></span></label>
                                <div class="cv-qty-wrap">
                                    <button type="button" class="qty-btn" @click="updateQty(index, -1)">−</button>
                                    <input type="number" min="1" :max="item.max_stock"
                                           :value="item.qty"
                                           @change="setCartQty(index, $event.target.value)"
                                           @keydown.enter.prevent="setCartQty(index, $event.target.value)">
                                    <button type="button" class="qty-btn" @click="updateQty(index, 1)">+</button>
                                </div>
                            </div>

                            <div class="cv-total-box">
                                <div class="cv-field-label">Line total</div>
                                <div class="cv-line">Tk<span x-text="formatNumber(item.price * item.qty)"></span></div>
                            </div>

                            <button type="button" class="cv-remove" @click="removeItem(index)" title="Remove">
                                <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
            <div class="modal-foot">
                <div class="cv-summary" x-show="cart.length > 0" x-cloak>
                    <div>
                        <div class="cv-summary-label">Cart subtotal</div>
                        <div class="cv-summary-meta" x-text="cart.length + ' lines · ' + cartUnitCount() + ' items'"></div>
                    </div>
                    <strong>Tk<span x-text="formatNumber(getTotal())"></span></strong>
                </div>
                <div class="cv-foot-actions">
                    <button type="button" @click="cartViewModalOpen = false" class="modal-cancel">Done</button>
                    <button type="button" class="modal-confirm" style="background:var(--blue);box-shadow:0 4px 12px rgba(37,99,235,.25)"
                            @click="cartViewModalOpen = false; openCheckout()"
                            :disabled="!canProceedToCheckout()">
                        Pay now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- IMEI PICKER MODAL -->
    <div x-show="imeiModalOpen" style="display:none" class="modal-overlay"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="modal-bg" @click="cancelImeiPick()"></div>
        <div class="modal" style="max-width:420px;width:92%"
             x-show="imeiModalOpen"
             @keydown.enter.prevent="confirmImeiPick()"
             x-transition:enter="ease-out duration-250" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Enter / select IMEI</div>
                    <div class="modal-subtitle" x-text="imeiPickProduct?.name || ''"></div>
                </div>
                <button type="button" @click="cancelImeiPick()" class="modal-close" aria-label="Close">×</button>
            </div>
            <div style="padding:16px 18px;display:flex;flex-direction:column;gap:12px">
                <div x-show="imeiPickOptions.length" x-cloak>
                    <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:6px">Available IMEIs</label>
                    <select x-model="imeiPickValue" class="form-control" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e2e8f0;font-family:var(--mono)">
                        <option value="">— Select IMEI —</option>
                        <template x-for="im in imeiPickOptions" :key="im">
                            <option :value="im" x-text="im"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:6px">Or type / scan IMEI</label>
                    <input type="text" x-model="imeiPickValue" x-ref="imeiInput"
                           placeholder="15-digit IMEI…"
                           style="width:100%;padding:10px;border-radius:8px;border:1px solid #e2e8f0;font-family:var(--mono);font-size:14px">
                </div>
            </div>
            <div class="modal-foot" style="display:flex;gap:8px;justify-content:flex-end;padding:12px 18px;border-top:1px solid #e2e8f0">
                <button type="button" class="modal-cancel" @click="cancelImeiPick()">Cancel</button>
                <button type="button" class="modal-confirm" style="background:#ea580c" @click="confirmImeiPick()">Add to cart</button>
            </div>
        </div>
    </div>

    <!-- CHECKOUT MODAL -->
    <div x-show="checkoutModalOpen" style="display:none" class="modal-overlay"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="modal-bg" @click="checkoutModalOpen = false"></div>

        <div class="modal checkout-modal"
             x-show="checkoutModalOpen"
             x-transition:enter="ease-out duration-250" x-transition:enter-start="opacity-0 scale-95 translate-y-3" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <div class="modal-head">
                <div>
                    <div class="modal-title">Complete Payment</div>
                    <div class="modal-subtitle" x-text="cartUnitCount() + ' item(s) · ' + (customerName || 'Walk-in Customer')"></div>
                </div>
                <button type="button" @click="checkoutModalOpen = false" class="modal-close" aria-label="Close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                @if(Auth::user()->isAdminUser())
                    <div class="mb-3" style="background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.2);border-radius:12px;padding:12px">
                        <label style="display:block;font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:var(--slate);margin-bottom:6px">Your open till</label>
                        <template x-for="c in posCounters" :key="c.id">
                            <div style="font-weight:800;color:var(--ink)" x-text="c.name + ' · opened by you'"></div>
                        </template>
                        <p style="margin:8px 0 0;font-size:11px;color:var(--slate)">
                            Admin sells only on counters with no staff assigned, after entering opening cash. Assigned tills stay with their cashier (they can open later).
                        </p>
                    </div>
                @endif
                <div class="amount-display">
                    <div class="amount-label">Bill total</div>
                    <div class="amount-value">
                        <span class="ccy">Tk</span><span x-text="formatNumber(getPayableTotal())"></span>
                    </div>
                    <div x-show="getDiscount() > 0" class="amount-note" style="color:var(--green)" x-cloak>
                         Discount Tk<span x-text="formatNumber(getDiscount())"></span>
                    </div>
                    <div x-show="isExchangeMode" class="amount-note" style="color:var(--amber)" x-cloak>
                        Exchange credit: -Tk<span x-text="formatNumber(exchangeCredit)"></span>
                    </div>
                    <div class="amount-note" style="color:var(--slate);margin-top:4px" x-show="!isBaki && !isEmi">
                        Enter what the customer pays — any shortfall is counted as Less.
                    </div>
                    <div class="amount-note" style="color:#b45309;margin-top:4px" x-show="isBaki" x-cloak>
                        BAKI on — Pay now settles this bill first, then previous baki. Shortfall stays as credit.
                    </div>
                    <div class="amount-note" style="color:#4338ca;margin-top:4px" x-show="isEmi" x-cloak>
                        EMI on — Collect down payment now. Remaining is split into monthly installments.
                    </div>
                </div>

                <div x-show="isBaki" x-cloak style="margin-bottom:12px;border:1px solid #fcd34d;background:#fffbeb;border-radius:12px;padding:12px">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12px">
                        <div>
                            <div style="color:#92400e;font-weight:700;font-size:10px;text-transform:uppercase">Previous baki</div>
                            <div style="font-weight:800;color:#78350f">Tk<span x-text="formatNumber(customerBakiBalance)"></span></div>
                        </div>
                        <div>
                            <div style="color:#92400e;font-weight:700;font-size:10px;text-transform:uppercase">This bill</div>
                            <div style="font-weight:800;color:#78350f">Tk<span x-text="formatNumber(getPayableTotal())"></span></div>
                        </div>
                        <div>
                            <div style="color:#92400e;font-weight:700;font-size:10px;text-transform:uppercase">Total due</div>
                            <div style="font-weight:800;color:#78350f">Tk<span x-text="formatNumber(getBakiTotalDue())"></span></div>
                        </div>
                        <div>
                            <div style="color:#92400e;font-weight:700;font-size:10px;text-transform:uppercase">Baki left</div>
                            <div style="font-weight:800;color:#b45309">Tk<span x-text="formatNumber(getBakiLeft())"></span></div>
                        </div>
                    </div>
                    <p x-show="!customerName || !customerPhone" style="margin:8px 0 0;font-size:11px;color:#dc2626;font-weight:700" x-cloak>
                        Name and mobile are required for BAKI.
                    </p>
                </div>

                <div x-show="isEmi" x-cloak style="margin-bottom:12px;border:1px solid #c7d2fe;background:#eef2ff;border-radius:12px;padding:12px">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12px">
                        <div>
                            <div style="color:#3730a3;font-weight:700;font-size:10px;text-transform:uppercase">Bill total</div>
                            <div style="font-weight:800;color:#312e81">Tk<span x-text="formatNumber(getPayableTotal())"></span></div>
                        </div>
                        <div>
                            <div style="color:#3730a3;font-weight:700;font-size:10px;text-transform:uppercase">Down payment</div>
                            <div style="font-weight:800;color:#312e81">Tk<span x-text="formatNumber(getEmiDownPayment())"></span></div>
                        </div>
                        <div style="grid-column:1 / -1">
                            <div style="color:#3730a3;font-weight:700;font-size:10px;text-transform:uppercase">Rest amount (EMI)</div>
                            <div style="font-weight:800;color:#4338ca">Tk<span x-text="formatNumber(getEmiPrincipal())"></span>
                                <span x-show="emiMonths > 0" style="font-weight:700;color:#312e81"> · <span x-text="emiMonths"></span> months</span>
                            </div>
                        </div>
                    </div>
                    <p x-show="!customerName || !customerPhone" style="margin:8px 0 0;font-size:11px;color:#dc2626;font-weight:700" x-cloak>
                        Name and mobile are required for EMI.
                    </p>
                    <p x-show="getEmiPrincipal() <= 0" style="margin:8px 0 0;font-size:11px;color:#dc2626;font-weight:700" x-cloak>
                        Lower down payment so some amount remains for EMI.
                    </p>
                </div>

                <div x-show="hasLowStockItems()" class="warning-box" x-cloak>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="warning-text">One or more items are at stock limit. Verify before confirming.</div>
                </div>

                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                        <label class="field-label" style="margin-bottom:0" x-text="isBaki ? 'Pay now' : (isEmi ? 'Down payment now' : 'Customer pays')"></label>
                        <span style="font-size:9px;font-weight:800;color:var(--teal);background:var(--teal-bg);padding:2px 7px;border-radius:100px;border:1px solid var(--teal-border)">Split pay</span>
                    </div>
                    <div class="split-grid">
                        <div class="sp-row">
                            <div class="sp-label">Cash</div>
                            <input type="number" x-model.number="payCash" class="sp-input" placeholder="0" min="0" step="0.01"
                                   @keydown.enter.prevent="if (canConfirmSale() && !isProcessing) submitOrder()">
                        </div>
                        <div class="sp-row">
                            <div class="sp-label">Card</div>
                            <input type="number" x-model.number="payCard" class="sp-input" placeholder="0" min="0" step="0.01"
                                   @keydown.enter.prevent="if (canConfirmSale() && !isProcessing) submitOrder()">
                        </div>
                        <div class="sp-row">
                            <div class="sp-label">bKash</div>
                            <input type="number" x-model.number="payBkash" class="sp-input" placeholder="0" min="0" step="0.01"
                                   @keydown.enter.prevent="if (canConfirmSale() && !isProcessing) submitOrder()">
                        </div>
                    </div>
                    <div class="qc-grid">
                        <button type="button" @click="payCash = isBaki ? getBakiTotalDue() : (isEmi ? getEmiDownPayment() : getPayableTotal()); payCard = 0; payBkash = 0;" class="qc-btn exact">Exact</button>
                        <button type="button" @click="payCash = 500"  class="qc-btn">Tk500</button>
                        <button type="button" @click="payCash = 1000" class="qc-btn">Tk1K</button>
                        <button type="button" @click="payCash = 2000" class="qc-btn">Tk2K</button>
                    </div>
                </div>

                {{-- Paid / less / change / baki summary --}}
                <div class="pay-summary" x-show="!isBaki && !isEmi">
                    <div class="pay-summary-cell due">
                        <div class="ps-label">Bill total</div>
                        <div class="ps-val">Tk<span x-text="formatNumber(getPayableTotal())"></span></div>
                    </div>
                    <div class="pay-summary-cell paid">
                        <div class="ps-label">Customer pays</div>
                        <div class="ps-val">Tk<span x-text="formatNumber(getPaidAmount())"></span></div>
                    </div>
                    <div class="pay-summary-cell change"
                         :class="getLessAmount() > 0 ? 'less' : (getChange() >= 0 ? 'ok' : 'err')">
                        <div class="ps-label" x-text="getLessAmount() > 0 ? 'Less (discount)' : (getChange() >= 0 ? 'Change to return' : 'Still owed')"></div>
                        <div class="ps-val">Tk<span x-text="formatNumber(getLessAmount() > 0 ? getLessAmount() : Math.abs(getChange()))"></span></div>
                    </div>
                </div>
                <div class="pay-summary" x-show="isBaki" x-cloak>
                    <div class="pay-summary-cell due">
                        <div class="ps-label">Total due</div>
                        <div class="ps-val">Tk<span x-text="formatNumber(getBakiTotalDue())"></span></div>
                    </div>
                    <div class="pay-summary-cell paid">
                        <div class="ps-label">Pay now</div>
                        <div class="ps-val">Tk<span x-text="formatNumber(getPaidAmount())"></span></div>
                    </div>
                    <div class="pay-summary-cell change less">
                        <div class="ps-label">Baki left</div>
                        <div class="ps-val">Tk<span x-text="formatNumber(getBakiLeft())"></span></div>
                    </div>
                </div>
                <div class="pay-summary" x-show="isEmi" x-cloak>
                    <div class="pay-summary-cell due">
                        <div class="ps-label">Down due</div>
                        <div class="ps-val">Tk<span x-text="formatNumber(getEmiDownPayment())"></span></div>
                    </div>
                    <div class="pay-summary-cell paid">
                        <div class="ps-label">Paying now</div>
                        <div class="ps-val">Tk<span x-text="formatNumber(getPaidAmount())"></span></div>
                    </div>
                    <div class="pay-summary-cell change less">
                        <div class="ps-label">EMI remaining</div>
                        <div class="ps-val">Tk<span x-text="formatNumber(getEmiPrincipal())"></span></div>
                    </div>
                </div>
                <p x-show="!isBaki && !isEmi && getLessAmount() > 0" class="err-hint" style="color:var(--green);border-color:var(--green)" x-cloak>
                    Rest of bill (Tk<span x-text="formatNumber(getLessAmount())"></span>) will be counted as Less / discount.
                </p>
                <p x-show="isBaki && getBakiNewCredit() > 0" class="err-hint" style="color:#b45309;border-color:#fcd34d" x-cloak>
                    This bill adds Tk<span x-text="formatNumber(getBakiNewCredit())"></span> to baki (after paying this invoice first).
                </p>
                <p x-show="isEmi && getEmiPrincipal() > 0" class="err-hint" style="color:#4338ca;border-color:#c7d2fe" x-cloak>
                    Rest EMI Tk<span x-text="formatNumber(getEmiPrincipal())"></span> over <span x-text="emiMonths"></span> months.
                </p>
            </div>

            <div class="modal-foot">
                <button type="button" @click="checkoutModalOpen = false" class="modal-cancel">
                    Cancel <span class="modal-kbd">ESC</span>
                </button>
                <button type="button" @click="submitOrder()"
                        :disabled="!canConfirmSale() || isProcessing"
                        class="modal-confirm"
                        x-ref="confirmSaleBtn">
                    <svg x-show="!isProcessing" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-show="!isProcessing" x-cloak style="display:flex;align-items:center;gap:6px">
                        Confirm sale <span class="modal-kbd">ENTER</span>
                    </span>
                    <span x-show="isProcessing" x-cloak>Processing...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- INVOICE PREVIEW MODAL (after successful sale) -->
    <div x-show="invoiceModalOpen" style="display:none" class="modal-overlay invoice-overlay"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="modal-bg" @click="closeInvoiceModal()"></div>
        <div class="modal invoice-modal"
             x-show="invoiceModalOpen"
             x-transition:enter="ease-out duration-250" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Sale complete · Invoice</div>
                    <div class="modal-subtitle" x-text="(lastSale?.invoice_no || '') + (lastSale?.customer ? ' · ' + lastSale.customer : '')"></div>
                </div>
                <button type="button" @click="closeInvoiceModal()" class="modal-close" aria-label="Close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="invoice-meta" x-show="lastSale" x-cloak>
                <div class="invoice-meta-item">
                    <div class="im-label">Customer paid</div>
                    <div class="im-val">Tk<span x-text="formatNumber(lastSale?.paid_amount || 0)"></span></div>
                </div>
                <div class="invoice-meta-item change" x-show="(lastSale?.credit_amount || 0) > 0" x-cloak>
                    <div class="im-label" x-text="lastSale?.is_emi ? 'EMI financed' : 'Baki on this bill'"></div>
                    <div class="im-val">Tk<span x-text="formatNumber(lastSale?.credit_amount || 0)"></span></div>
                </div>
                <div class="invoice-meta-item change" x-show="(lastSale?.is_emi) && (lastSale?.emi_months || 0) > 0" x-cloak>
                    <div class="im-label">EMI tenure</div>
                    <div class="im-val"><span x-text="lastSale?.emi_months"></span> months</div>
                </div>
                <div class="invoice-meta-item change" x-show="!(lastSale?.is_emi) && (lastSale?.baki_balance || 0) > 0" x-cloak>
                    <div class="im-label">Baki left</div>
                    <div class="im-val">Tk<span x-text="formatNumber(lastSale?.baki_balance || 0)"></span></div>
                </div>
                <div class="invoice-meta-item change" x-show="!(lastSale?.is_baki) && !(lastSale?.is_emi) && (lastSale?.discount_amount || 0) > 0 && (lastSale?.change || 0) <= 0" x-cloak>
                    <div class="im-label">Less / discount</div>
                    <div class="im-val">Tk<span x-text="formatNumber(lastSale?.discount_amount || 0)"></span></div>
                </div>
                <div class="invoice-meta-item change" x-show="(lastSale?.change || 0) > 0 || (!(lastSale?.is_baki) && !(lastSale?.is_emi) && (lastSale?.discount_amount || 0) <= 0 && !(lastSale?.credit_amount))" x-cloak>
                    <div class="im-label">Change to return</div>
                    <div class="im-val">Tk<span x-text="formatNumber(lastSale?.change || 0)"></span></div>
                </div>
            </div>
            <div class="invoice-frame-wrap">
                <iframe x-ref="receiptFrame" class="invoice-frame"
                        :src="lastSale?.receipt_html ? false : (lastSale?.receipt_url || 'about:blank')"
                        :srcdoc="lastSale?.receipt_html || false"
                        title="POS Invoice"></iframe>
            </div>
            <div class="modal-foot">
                <button type="button" @click="closeInvoiceModal()" class="modal-cancel">
                    Close <span class="modal-kbd">ESC</span>
                </button>
                <button type="button" @click="printLastReceipt()" class="modal-confirm" style="background:var(--blue);box-shadow:0 4px 12px rgba(37,99,235,.25)">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print invoice <span class="modal-kbd">ENTER</span>
                </button>
            </div>
        </div>
    </div>

    <!-- SYNC PERMISSION (when network returns) -->
    <div x-show="syncPromptOpen" style="display:none" class="modal-overlay"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="modal-bg" @click="syncPromptOpen = false"></div>
        <div class="modal" style="max-width:420px"
             x-show="syncPromptOpen"
             x-transition:enter="ease-out duration-250" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Network is back</div>
                    <div class="modal-subtitle">Offline bills are ready to upload</div>
                </div>
                <button type="button" @click="syncPromptOpen = false" class="modal-close" aria-label="Close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <p style="font-size:13.5px;line-height:1.5;color:var(--text-2);margin:0">
                    <strong x-text="pendingOfflineCount()"></strong> offline bill(s) were saved while the network was down.
                    Sync now to update stock and sales on the server?
                </p>
                <p style="font-size:12px;color:var(--text-3);margin:10px 0 0">You can also sync later by clicking the Online badge.</p>
            </div>
            <div class="modal-foot">
                <button type="button" @click="syncPromptOpen = false" class="modal-cancel">Later</button>
                <button type="button" @click="confirmSyncOffline()" class="modal-confirm" :disabled="isSyncing"
                        style="background:var(--blue);box-shadow:0 4px 12px rgba(37,99,235,.25)">
                    <span x-show="!isSyncing" x-cloak>Sync now</span>
                    <span x-show="isSyncing" x-cloak>Syncing...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         HELD CARTS MODAL
    ═══════════════════════════════════════════════════════════════ -->
    <div x-show="holdCartsModalOpen" style="display:none" class="modal-overlay"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="modal-bg" @click="holdCartsModalOpen = false"></div>
        <div class="modal" style="max-width:440px"
             x-show="holdCartsModalOpen"
             x-transition:enter="ease-out duration-250" x-transition:enter-start="opacity-0 scale-95 translate-y-3" x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            <div class="modal-head">
                <div>
                    <div class="modal-title" style="display:flex;align-items:center;gap:8px">
                        <svg style="width:18px;height:18px;color:var(--amber)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Suspended Carts
                    </div>
                    <div class="modal-subtitle">Tap Resume to continue a suspended order</div>
                </div>
                <button @click="holdCartsModalOpen = false" class="modal-close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div style="max-height:400px;overflow-y:auto;background:var(--bg)">
                <template x-if="heldCarts.length === 0">
                    <div style="padding:36px;text-align:center;font-size:13px;font-weight:600;color:var(--text-3)">No suspended carts.</div>
                </template>
                <template x-for="(hCart, index) in heldCarts" :key="hCart.id">
                    <div style="padding:13px 18px;display:flex;justify-content:space-between;align-items:center;background:var(--surface);border-bottom:1px solid var(--border)">
                        <div>
                            <div style="font-weight:700;font-size:13px;color:var(--text-1)">
                                Held at <span style="color:var(--teal);font-family:var(--mono)" x-text="hCart.time"></span>
                            </div>
                            <div style="font-size:11px;color:var(--text-2);margin-top:3px;font-family:var(--mono)">
                                <span x-text="hCart.cartData.length"></span> items  ·  Tk<span x-text="formatNumber(hCart.total)"></span>
                            </div>
                            <div x-show="hCart.customerName || hCart.customerPhone"
                                 style="font-size:11px;color:var(--text-3);margin-top:2px">
                                <span x-text="hCart.customerName || 'Guest'"></span>
                                <span x-show="hCart.customerPhone" x-text="'  ·  ' + hCart.customerPhone"></span>
                            </div>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:6px">
                            <button @click="resumeHeldCart(index)"
                                style="background:var(--teal-bg);color:var(--teal);border:1.5px solid var(--teal-border);padding:7px 16px;border-radius:8px;font-size:12px;font-weight:800;cursor:pointer;font-family:var(--font)">
                                Resume
                            </button>
                            <button @click="deleteHeldCart(index)"
                                style="background:var(--red-bg);color:var(--red);border:1.5px solid var(--red-border);padding:7px 16px;border-radius:8px;font-size:12px;font-weight:800;cursor:pointer;font-family:var(--font)">
                                Drop
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         KEYBOARD SHORTCUT CHEATSHEET
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div x-show="kbOpen" style="display:none" class="kb-overlay"
         @click.self="kbOpen = false"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="kb-modal"
             x-transition:enter="ease-out duration-250" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="kb-head">
                <h2 class="kb-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Keyboard Shortcuts
                </h2>
                <button @click="kbOpen = false" class="modal-close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="kb-grid">
                <div class="kb-row">
                    <span class="kb-desc">Pay</span>
                    <span class="key">F2</span>
                </div>
                <div class="kb-row">
                    <span class="kb-desc">Confirm Sale (when modal open)</span>
                    <span class="key">Enter</span>
                </div>
                <div class="kb-row">
                    <span class="kb-desc">Close modal / Clear search</span>
                    <span class="key">Esc</span>
                </div>
                <div class="kb-row">
                    <span class="kb-desc">Auto-add single barcode result</span>
                    <span class="key">Enter</span>
                </div>
                <div class="kb-row">
                    <span class="kb-desc">Show this cheatsheet</span>
                    <span class="key">?</span>
                </div>
                <div class="kb-row">
                    <span class="kb-desc">Toggle dark / light mode</span>
                    <span class="key">D</span>
                </div>
                <div class="kb-row">
                    <span class="kb-desc">Toggle fullscreen</span>
                    <span class="key">F11</span>
                    <span class="key">F</span>
                </div>
            </div>
        </div>
    </div>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         TOAST NOTIFICATIONS
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="toast-dock">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="toast" :class="toast.type">
                <div class="toast-bar"></div>
                <span class="toast-msg" x-text="toast.msg"></span>
            </div>
        </template>
    </div>

</div>

<script>
function posSystem() {
    return {
        /* â”€â”€ Core â”€â”€ */
        isOnline: navigator.onLine,
        showExtras: false,
        isSyncing: false,
        syncPromptOpen: false,
        darkMode: localStorage.getItem('nexa_dark') === 'true',
        isFullscreen: !!(document.fullscreenElement || document.webkitFullscreenElement),
        showFullscreenHint: localStorage.getItem('nexa_pos_fs_hint') !== 'dismissed',
        shopName: @json(Auth::user()->shop->name ?? config('app.name', 'Maks Gadget')),
        cashierName: @json(Auth::user()->name),
        offlinePendingTick: 0, // forces UI refresh of pending count

        search: '',
        catalogQuery: '',
        selectedCategory: 'all',
        selectedBrand: 'all',
        openCatId: null,
        catMenuStyle: { top: '0px', left: '0px' },
        sortBy: 'name',
        viewMode: localStorage.getItem('nexa_pos_view') || 'grid',
        page: 1,
        perPage: 15,
        brokenImages: {},
        imageFallbacks: {
            phone: 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600&q=80',
            laptop: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600&q=80',
            tablet: 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=600&q=80',
            headphones: 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=600&q=80',
            earbuds: 'https://images.unsplash.com/photo-1600294037681-c80b4cb5b434?w=600&q=80',
            watch: 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?w=600&q=80',
            camera: 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=600&q=80',
            game: 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=600&q=80',
            speaker: 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600&q=80',
            charger: 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=600&q=80',
            accessory: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80',
            monitor: 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=600&q=80',
            default: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80',
        },
        categories: @json($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'icon' => $c->icon])->values()),
        brands: @json($brands),
        products: @json($products),
        cart: [],
        lastAddedId: null,
        imeiModalOpen: false,
        imeiPickProduct: null,
        imeiPickValue: '',
        imeiPickOptions: [],
        imeiPickMode: 'add', // add | bump
        imeiPickCartIndex: null,

        /* â”€â”€ Customer â”€â”€ */
        customerName: '',
        customerPhone: '',
        isSearchingCustomer: false,
        customerBakiBalance: 0,
        customerEmiBalance: 0,
        isBaki: false,
        isEmi: false,
        emiMonths: 3,
        emiDownPayment: 0,
        posEmiEnabled: @json((bool) ($posEmiEnabled ?? true)),
        posBakiEnabled: @json((bool) ($posBakiEnabled ?? true)),
        posSaleEnabled: @json((bool) ($posSaleEnabled ?? true)),

        /* â”€â”€ Discount â”€â”€ */
        discountType: 'percent',
        discountValue: 0,
        _saleDiscountActive: false,
        _lastSaleSavings: 0,
        couponCode: '',
        appliedCoupon: null,

        /* â”€â”€ Checkout â”€â”€ */
        checkoutModalOpen: false,
        invoiceModalOpen: false,
        cartViewModalOpen: false,
        lastSale: null,
        isProcessing: false,
        payCash: 0,
        payCard: 0,
        payBkash: 0,
        isAdminPos: {{ Auth::user()->isAdminUser() ? 'true' : 'false' }},
        posCounters: @json($posCounters ?? []),
        selectedCounterId: '{{ $defaultPosCounterId ?? '' }}',

        /* â”€â”€ UI State â”€â”€ */
        heldCarts: JSON.parse(localStorage.getItem('nexa_held_carts')) || [],
        holdCartsModalOpen: false,
        kbOpen: false,
        toasts: [],

        /* â”€â”€ Exchange Mode â”€â”€ */
        isExchangeMode: {{ $exchangeOrder ? 'true' : 'false' }},
        exchangeOrderId: {{ $exchangeOrder ?? 'null' }},
        returnProductId: {{ $returnProduct ?? 'null' }},
        returnQty: {{ $returnQty ?? 0 }},
        exchangeCredit: {{ $credit ?? 0 }},

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           INIT
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        init() {
            // Guarantee every product has a displayable image URL before first paint
            this.products = (this.products || []).map((p) => {
                let url = this.normalizeImageUrl(p.image_url || p.image);
                if (!url) url = this.fallbackImageFor(p);
                if (!url) url = this.placeholderDataUri(p.name);
                return { ...p, image_url: url };
            });

            window.addEventListener('online', () => {
                this.isOnline = true;
                this.showToast('Network is back', 'success');
                if (this.pendingOfflineCount() > 0) {
                    this.syncPromptOpen = true;
                }
            });
            window.addEventListener('offline', () => {
                this.isOnline = false;
                this.showToast('Offline mode — you can still sell and print bills', 'warning');
            });
            // Ask permission if offline bills already waiting when POS opens online
            if (this.isOnline && this.pendingOfflineCount() > 0) {
                this.$nextTick(() => { this.syncPromptOpen = true; });
            }
            const syncFs = () => {
                this.isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement);
                if (this.isFullscreen) this.showFullscreenHint = false;
            };
            document.addEventListener('fullscreenchange', syncFs);
            document.addEventListener('webkitfullscreenchange', syncFs);
            syncFs();

            // Expand popup/window to fill the monitor when launched for a till
            try {
                if (window.name === 'nexa_pos_terminal' || window.opener) {
                    window.moveTo(0, 0);
                    window.resizeTo(screen.availWidth, screen.availHeight);
                }
            } catch (e) {}
        },

        /* ───────────────────────────────
           DARK MODE
        ─────────────────────────────── */
        toggleDark() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('nexa_dark', this.darkMode);
        },

        /* ───────────────────────────────
           FULLSCREEN
        ─────────────────────────────── */
        async toggleFullscreen() {
            try {
                const root = document.documentElement;
                const active = document.fullscreenElement || document.webkitFullscreenElement;
                if (active) {
                    if (document.exitFullscreen) await document.exitFullscreen();
                    else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
                } else {
                    if (root.requestFullscreen) await root.requestFullscreen();
                    else if (root.webkitRequestFullscreen) root.webkitRequestFullscreen();
                    else {
                        this.showToast('Fullscreen not supported in this browser', 'warning');
                        return;
                    }
                }
            } catch (e) {
                this.showToast('Could not toggle fullscreen', 'error');
            }
            this.isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement);
            if (this.isFullscreen) this.showFullscreenHint = false;
        },

        async enterCounterFullscreen() {
            await this.toggleFullscreen();
            if (this.isFullscreen) {
                localStorage.setItem('nexa_pos_fs_hint', 'dismissed');
                this.showFullscreenHint = false;
            }
        },

        dismissFullscreenHint() {
            localStorage.setItem('nexa_pos_fs_hint', 'dismissed');
            this.showFullscreenHint = false;
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           TOAST SYSTEM
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        showToast(msg, type = 'info', ms = 3200) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, msg, type });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, ms);
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           BEEP FEEDBACK
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        playBeep(ok = true) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc  = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.value = ok ? 1200 : 380;
                osc.type = ok ? 'sine' : 'square';
                gain.gain.setValueAtTime(0.22, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + (ok ? 0.13 : 0.22));
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + (ok ? 0.13 : 0.22));
            } catch(e) {}
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           KEYBOARD SHORTCUTS
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        handleKeydown(e) {
            // Invoice preview after sale — Enter prints, Esc closes
            if (this.invoiceModalOpen) {
                if (e.key === 'Enter' || e.key === 'NumpadEnter') {
                    e.preventDefault();
                    e.stopPropagation();
                    this.printLastReceipt();
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    this.closeInvoiceModal();
                }
                return;
            }
            // Inside checkout modal — Enter confirms, Esc cancels
            if (this.checkoutModalOpen) {
                if (e.key === 'Enter' || e.key === 'NumpadEnter') {
                    e.preventDefault();
                    e.stopPropagation();
                    if (this.canConfirmSale() && !this.isProcessing) this.submitOrder();
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    this.checkoutModalOpen = false;
                }
                return;
            }
            // Cart view modal — Esc closes
            if (this.cartViewModalOpen) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    this.cartViewModalOpen = false;
                }
                return;
            }
            // Shortcut overlay open
            if (this.kbOpen) {
                if (e.key === 'Escape') { this.kbOpen = false; }
                return;
            }
            // Global
            if (e.key === 'F2')    { e.preventDefault(); if (this.canProceedToCheckout()) this.openCheckout(); }
            if (e.key === 'F11')   { e.preventDefault(); this.toggleFullscreen(); }
            if (e.key === 'Escape'){ this.search = ''; this.$refs.searchInput.focus(); }
            if ((e.key === 'k' || e.key === 'K') && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                this.$refs.searchInput?.focus();
                this.$refs.searchInput?.select();
            }
            if (e.key === '?')     { e.preventDefault(); this.kbOpen = true; }
            if ((e.key === 'd' || e.key === 'D') && document.activeElement.tagName !== 'INPUT') this.toggleDark();
            if ((e.key === 'f' || e.key === 'F') && !e.ctrlKey && !e.metaKey && !e.altKey && document.activeElement.tagName !== 'INPUT') {
                e.preventDefault();
                this.toggleFullscreen();
            }
            // Enter on search is handled by @keydown.enter on the input
        },

        /* ───────────────────────────────
           CATEGORY / BRAND / CATALOG FILTERS
        ─────────────────────────────── */
        pickAllProducts() {
            this.selectedCategory = 'all';
            this.selectedBrand = 'all';
            this.openCatId = null;
            this.page = 1;
        },
        pickCategory(id) {
            this.selectedCategory = String(id);
            this.selectedBrand = 'all';
            this.openCatId = null;
            this.page = 1;
        },
        pickBrand(categoryId, brandId) {
            this.selectedCategory = String(categoryId);
            this.selectedBrand = String(brandId);
            this.openCatId = null;
            this.page = 1;
        },
        toggleCatMenu(id, event) {
            const key = String(id);
            if (this.openCatId === key) {
                this.openCatId = null;
                return;
            }
            this.openCatId = key;
            const el = event?.currentTarget || event?.target;
            const rect = el?.getBoundingClientRect?.();
            if (rect) {
                const left = Math.min(rect.left, window.innerWidth - 220);
                this.catMenuStyle = {
                    top: Math.round(rect.bottom + 6) + 'px',
                    left: Math.round(Math.max(8, left)) + 'px',
                };
            }
        },
        setViewMode(mode) {
            this.viewMode = mode === 'list' ? 'list' : 'grid';
            localStorage.setItem('nexa_pos_view', this.viewMode);
        },
        normalizeImageUrl(raw) {
            let value = String(raw || '').replace(/\\/g, '/').trim();
            if (!value) return '';
            if (value.startsWith('data:')) return value;
            // Force same-origin for /storage assets (fixes localhost vs 127.0.0.1:8000)
            try {
                if (/^(https?:)?\/\//i.test(value) || value.startsWith('/')) {
                    const u = new URL(value, window.location.origin);
                    if (u.pathname.includes('/storage/')) {
                        return u.pathname + u.search;
                    }
                    if (/^(https?:)?\/\//i.test(value)) return value;
                }
            } catch (e) {}
            if (value.startsWith('/storage/')) return value;
            if (value.startsWith('storage/')) return '/' + value;
            const base = document.querySelector('meta[name="app-base"]')?.content || '';
            return base + '/storage/' + value.replace(/^\/+/, '');
        },
        productImageSrc(product) {
            if (!product) return '';
            return this.normalizeImageUrl(product.image_url || product.image || '');
        },
        fallbackImageFor(product) {
            const key = String(product?.category_name || product?.category || '').toLowerCase();
            const map = this.imageFallbacks;
            if (key.includes('phone') || key.includes('mobile')) return map.phone;
            if (key.includes('laptop')) return map.laptop;
            if (key.includes('tablet') || key.includes('ipad')) return map.tablet;
            if (key.includes('earbud')) return map.earbuds;
            if (key.includes('headphone') || key.includes('audio')) return map.headphones;
            if (key.includes('watch')) return map.watch;
            if (key.includes('camera')) return map.camera;
            if (key.includes('game')) return map.game;
            if (key.includes('speaker')) return map.speaker;
            if (key.includes('charg') || key.includes('cable')) return map.charger;
            if (key.includes('monitor') || key.includes('display')) return map.monitor;
            if (key.includes('accessor')) return map.accessory;
            return map.default;
        },
        recoverProductImage(event, product) {
            const img = event?.target;
            if (!img || !product) return;
            const tried = img.dataset.fallbackTried === '1';
            const fallback = this.fallbackImageFor(product);
            if (!tried && fallback && !String(img.src || '').includes(fallback.split('?')[0])) {
                img.dataset.fallbackTried = '1';
                product.image_url = fallback;
                img.src = fallback;
                return;
            }
            // Final local SVG placeholder — always paints something
            const initials = this.productInitials(product.name || '?');
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240"><defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#f8fafc"/><stop offset="1" stop-color="#e2e8f0"/></linearGradient></defs><rect width="240" height="240" fill="url(#g)"/><text x="120" y="128" text-anchor="middle" fill="#64748b" font-size="42" font-family="Segoe UI,Arial,sans-serif" font-weight="700">${initials}</text></svg>`;
            img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
            product.image_url = img.src;
        },
        placeholderDataUri(name) {
            const initials = this.productInitials(name || '?');
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240"><rect width="240" height="240" fill="#f1f5f9"/><text x="120" y="128" text-anchor="middle" fill="#94a3b8" font-size="42" font-family="Segoe UI,Arial,sans-serif" font-weight="700">${initials}</text></svg>`;
            return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
        },
        recoverCartImage(event, item) {
            const img = event?.target;
            if (!img || !item) return;
            const fallback = this.fallbackImageFor(item);
            if (fallback && img.src !== fallback) {
                item.image_url = fallback;
                img.src = fallback;
                return;
            }
            img.style.display = 'none';
        },
        cartItemImage(item) {
            if (!item) return '';
            return this.normalizeImageUrl(item.image_url || item.image || '');
        },
        productsInCategory(categoryId) {
            if (categoryId === null || categoryId === undefined || categoryId === 'all') {
                return this.products;
            }
            return this.products.filter(p => String(p.category_id) === String(categoryId));
        },
        categoryProductCount(categoryId) {
            return this.productsInCategory(categoryId).length;
        },
        brandsForCategory(categoryId) {
            const ids = new Set();
            const looseNames = new Map();
            this.productsInCategory(categoryId).forEach(p => {
                const resolved = this.resolveBrand(p);
                if (resolved.id) {
                    ids.add(String(resolved.id));
                } else if (resolved.name) {
                    const key = resolved.name.toLowerCase();
                    if (!looseNames.has(key)) looseNames.set(key, resolved.name);
                }
            });
            const fromCatalog = (this.brands || []).filter(b => ids.has(String(b.id)));
            const synthetic = Array.from(looseNames.entries()).map(([key, name]) => ({
                id: 'name:' + key,
                name,
                _synthetic: true,
            }));
            return [...fromCatalog, ...synthetic];
        },
        brandsForCurrentView() {
            return this.brandsForCategory(this.selectedCategory === 'all' ? null : this.selectedCategory)
                .filter(b => !String(b.id).startsWith('name:'));
        },
        categoryIconSvg(cat) {
            const key = String(cat?.icon || cat?.name || '').toLowerCase();
            // Decorative stroke icons matching the Nexa mockup style
            const icons = {
                all: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
                phone: '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
                smartphone: '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
                laptop: '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/><path d="M8 20h8"/>',
                tablet: '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M11 18h2"/>',
                headphones: '<path d="M4 13a8 8 0 0 1 16 0"/><path d="M4 13v5a2 2 0 0 0 2 2h1v-7H4zm16 0v5a2 2 0 0 1-2 2h-1v-7h3z"/>',
                earbuds: '<path d="M7 10a3 3 0 1 0 0 6h1v-6H7zm9 0h1a3 3 0 1 1 0 6h-1v-6z"/><path d="M8 13h8"/>',
                watch: '<circle cx="12" cy="12" r="7"/><path d="M12 9v3l2 1"/><path d="M9 3h6M9 21h6"/>',
                smartwatch: '<circle cx="12" cy="12" r="7"/><path d="M12 9v3l2 1"/><path d="M9 3h6M9 21h6"/>',
                camera: '<path d="M4 8h3l2-2h6l2 2h3v11H4V8z"/><circle cx="12" cy="13" r="3.5"/>',
                game: '<rect x="2" y="8" width="20" height="10" rx="4"/><path d="M8 12h.01M10 12h.01M9 11v2M15 11.5h.01M17 13.5h.01"/>',
                gaming: '<rect x="2" y="8" width="20" height="10" rx="4"/><path d="M8 12h.01M10 12h.01M9 11v2M15 11.5h.01M17 13.5h.01"/>',
                speaker: '<path d="M11 5L6 9H3v6h3l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M18 6a8 8 0 0 1 0 12"/>',
                charger: '<path d="M9 2v4M15 2v4"/><rect x="7" y="6" width="10" height="11" rx="2"/><path d="M11 17v5M13 17v5"/><path d="M10 10h4"/>',
                plug: '<path d="M9 2v4M15 2v4"/><rect x="7" y="6" width="10" height="11" rx="2"/><path d="M11 17v5M13 17v5"/>',
                accessory: '<rect x="5" y="10" width="14" height="9" rx="2"/><path d="M8 10V8a4 4 0 0 1 8 0v2"/><circle cx="9" cy="14" r="1"/><path d="M12 17h5"/>',
                mouse: '<rect x="8" y="3" width="8" height="14" rx="4"/><path d="M12 3v5"/>',
                monitor: '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
            };
            let body = icons.accessory;
            const order = ['all','smartphone','phone','laptop','tablet','earbuds','headphones','smartwatch','watch','camera','gaming','game','speaker','charger','plug','mouse','accessory','monitor'];
            for (const k of order) {
                if (key === k || key.includes(k)) { body = icons[k]; break; }
            }
            if (key.includes('mobile')) body = icons.phone;
            if (key.includes('earbud') || key.includes('earphone')) body = icons.earbuds;
            if (key.includes('audio') && !key.includes('speaker')) body = icons.headphones;
            if (key.includes('cable') || key.includes('power') || key.includes('charg')) body = icons.charger;
            if (key.includes('accessor')) body = icons.mouse;
            return `<svg fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">${body}</svg>`;
        },
        resolveBrand(product) {
            if (product.brand_id) {
                return { id: String(product.brand_id), name: product.brand_name || null };
            }
            const explicit = String(product.brand_name || '').trim();
            if (explicit) {
                const match = (this.brands || []).find(b => String(b.name).toLowerCase() === explicit.toLowerCase());
                if (match) return { id: String(match.id), name: match.name };
                return { id: null, name: explicit };
            }
            const pname = String(product.name || '').trim().toLowerCase();
            if (pname) {
                const match = (this.brands || [])
                    .slice()
                    .sort((a, b) => String(b.name).length - String(a.name).length)
                    .find(b => {
                        const bn = String(b.name || '').trim().toLowerCase();
                        if (!bn) return false;
                        return pname === bn || pname.startsWith(bn + ' ') || pname.startsWith(bn + '-') || pname.startsWith(bn + '_');
                    });
                if (match) return { id: String(match.id), name: match.name };
            }
            return { id: null, name: null };
        },
        brandProductCount(categoryId, brandId) {
            const key = String(brandId);
            return this.productsInCategory(categoryId).filter(p => {
                const resolved = this.resolveBrand(p);
                if (key.startsWith('name:')) {
                    return !resolved.id && resolved.name && ('name:' + resolved.name.toLowerCase()) === key;
                }
                return resolved.id && String(resolved.id) === key;
            }).length;
        },
        unbrandedCount(categoryId) {
            return this.productsInCategory(categoryId).filter(p => {
                const resolved = this.resolveBrand(p);
                return !resolved.id && !resolved.name;
            }).length;
        },
        matchesBrand(product) {
            if (this.selectedBrand === 'all') return true;
            const resolved = this.resolveBrand(product);
            if (this.selectedBrand === 'none') return !resolved.id && !resolved.name;
            const key = String(this.selectedBrand);
            if (key.startsWith('name:')) {
                return !resolved.id && resolved.name && ('name:' + resolved.name.toLowerCase()) === key;
            }
            return resolved.id && String(resolved.id) === key;
        },
        matchesCategory(product) {
            return this.selectedCategory === 'all' || String(product.category_id) === String(this.selectedCategory);
        },
        filteredProducts() {
            const q = (this.catalogQuery || '').trim().toLowerCase();
            let list = this.products.filter(p => {
                if (!this.matchesCategory(p) || !this.matchesBrand(p)) return false;
                if (!q) return true;
                const name = (p.name || '').toLowerCase();
                const barcode = String(p.barcode || '').toLowerCase();
                const sku = String(p.sku || '').toLowerCase();
                const brand = String(p.brand_name || '').toLowerCase();
                const category = String(p.category_name || '').toLowerCase();
                return name.includes(q) || barcode.includes(q) || sku.includes(q) || brand.includes(q) || category.includes(q);
            });

            const sort = this.sortBy;
            list = list.slice().sort((a, b) => {
                if (sort === 'price_asc') return Number(a.selling_price) - Number(b.selling_price);
                if (sort === 'price_desc') return Number(b.selling_price) - Number(a.selling_price);
                if (sort === 'stock') return Number(b.stock_quantity) - Number(a.stock_quantity);
                if (sort === 'name_desc') return String(b.name || '').localeCompare(String(a.name || ''));
                return String(a.name || '').localeCompare(String(b.name || ''));
            });
            return list;
        },
        headerSearchMatches() {
            const q = (this.search || '').trim().toLowerCase();
            if (!q) return [];
            return this.products.filter(p => {
                const name = (p.name || '').toLowerCase();
                const barcode = String(p.barcode || '').toLowerCase();
                const sku = String(p.sku || '').toLowerCase();
                const brand = String(p.brand_name || '').toLowerCase();
                return name.includes(q) || barcode.includes(q) || sku.includes(q) || brand.includes(q);
            });
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.filteredProducts().length / this.perPage));
        },
        currentPage() {
            return Math.min(Math.max(1, this.page), this.totalPages());
        },
        pageFrom() {
            if (this.filteredProducts().length === 0) return 0;
            return ((this.currentPage() - 1) * this.perPage) + 1;
        },
        pageTo() {
            return Math.min(this.currentPage() * this.perPage, this.filteredProducts().length);
        },
        paginatedProducts() {
            const start = (this.currentPage() - 1) * this.perPage;
            return this.filteredProducts().slice(start, start + this.perPage);
        },
        goPage(p) {
            const page = Number(p);
            if (!page || page < 1 || page > this.totalPages()) return;
            this.page = page;
            this.$nextTick(() => {
                document.querySelector('.product-list')?.scrollTo({ top: 0, behavior: 'smooth' });
            });
        },
        pageNumbers() {
            const total = this.totalPages();
            const current = this.currentPage();
            if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
            const pages = [1];
            const start = Math.max(2, current - 1);
            const end = Math.min(total - 1, current + 1);
            if (start > 2) pages.push('…');
            for (let i = start; i <= end; i++) pages.push(i);
            if (end < total - 1) pages.push('…');
            pages.push(total);
            return pages;
        },

        findExactScanMatch(code) {
            const q = String(code || '').trim().toLowerCase();
            if (!q) return null;
            return this.products.find(p => {
                const barcode = String(p.barcode || '').trim().toLowerCase();
                const sku = String(p.sku || '').trim().toLowerCase();
                return (barcode && barcode === q) || (sku && sku === q);
            }) || null;
        },

        onSearchInput() {
            if (this.checkoutModalOpen || this.invoiceModalOpen) return;
            const q = (this.search || '').trim();
            // Barcode scanners type fast; auto-add on exact barcode/SKU match (no click needed)
            if (q.length < 3) return;
            const match = this.findExactScanMatch(q);
            if (match) {
                this.addToCart(match);
            }
        },

        onSearchEnter() {
            if (this.checkoutModalOpen || this.invoiceModalOpen) return;
            const q = (this.search || '').trim();
            if (!q) return;

            // Prefer exact barcode / SKU
            const exact = this.findExactScanMatch(q);
            if (exact) {
                this.addToCart(exact);
                return;
            }

            // Otherwise add if only one header search match
            const matches = this.headerSearchMatches();
            if (matches.length === 1) {
                this.addToCart(matches[0]);
                return;
            }

            if (matches.length === 0) {
                this.playBeep(false);
                this.showToast('No product found for “' + q + '”', 'error');
                this.search = '';
                this.$nextTick(() => this.$refs.searchInput?.focus());
            } else {
                // Multiple matches — push query into catalog filter so cashier can pick
                this.catalogQuery = q;
                this.page = 1;
                this.search = '';
                this.showToast(matches.length + ' matches — pick from the list', 'info');
            }
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           CUSTOMER LOOKUP
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        async searchCustomer() {
            if (this.customerPhone.length < 9) {
                if (!this.customerPhone) {
                    this.customerName = '';
                    this.customerBakiBalance = 0;
                    this.customerEmiBalance = 0;
                }
                return;
            }
            if (!this.isOnline) return;
            this.isSearchingCustomer = true;
            try {
                const res = await fetch(`{{ route('pos.customer-lookup') }}?phone=${this.customerPhone}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.found) {
                        this.customerName = data.name;
                        this.customerBakiBalance = Number(data.baki_balance) || 0;
                        this.customerEmiBalance = Number(data.emi_balance) || 0;
                        let msg = 'Customer found: ' + data.name;
                        if (this.customerBakiBalance > 0) msg += ' · Baki Tk' + this.formatNumber(this.customerBakiBalance);
                        if (this.customerEmiBalance > 0) msg += ' · EMI Tk' + this.formatNumber(this.customerEmiBalance);
                        this.showToast(msg, 'success');
                    } else {
                        this.customerBakiBalance = 0;
                        this.customerEmiBalance = 0;
                    }
                }
            } catch(e) {}
            this.isSearchingCustomer = false;
        },

        toggleBaki() {
            if (!this.posBakiEnabled) {
                this.showToast('Baki is turned off in POS Modes.', 'warning');
                return;
            }
            this.isBaki = !this.isBaki;
            if (this.isBaki) {
                this.isEmi = false;
                this.showToast('BAKI on — unpaid amount is credit, not Less', 'info');
            }
        },

        toggleEmi() {
            if (!this.posEmiEnabled) {
                this.showToast('EMI is turned off in POS Modes.', 'warning');
                return;
            }
            this.isEmi = !this.isEmi;
            if (this.isEmi) {
                this.isBaki = false;
                if (!this.emiMonths || this.emiMonths < 1) this.emiMonths = 3;
                if (this.emiDownPayment == null) this.emiDownPayment = 0;
                this.showToast('EMI on — collect down payment, rest over months', 'info');
            }
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           CART
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        addToCart(product) {
            if (product.stock_quantity < 1) {
                this.playBeep(false);
                this.showToast(product.name + ' is out of stock!', 'error');
                return;
            }
            if (product.requires_imei) {
                this.openImeiPicker(product, 'add');
                return;
            }
            this.pushCartLine(product, null);
        },

        openImeiPicker(product, mode = 'add', cartIndex = null) {
            const used = this.cartImeisInUse(product.id);
            const pool = (product.available_imeis || []).filter(im => !used.includes(String(im)));
            if (pool.length === 0 && !(product.available_imeis || []).length) {
                // No preloaded list — still allow manual entry
            } else if (pool.length === 0) {
                this.playBeep(false);
                this.showToast('No unused IMEIs left for ' + product.name, 'warning');
                return;
            }
            this.imeiPickProduct = product;
            this.imeiPickMode = mode;
            this.imeiPickCartIndex = cartIndex;
            this.imeiPickOptions = pool;
            this.imeiPickValue = pool.length === 1 ? pool[0] : '';
            this.imeiModalOpen = true;
            this.$nextTick(() => this.$refs.imeiInput?.focus());
        },

        cancelImeiPick() {
            this.imeiModalOpen = false;
            this.imeiPickProduct = null;
            this.imeiPickValue = '';
            this.imeiPickOptions = [];
            this.imeiPickCartIndex = null;
        },

        confirmImeiPick() {
            const imei = String(this.imeiPickValue || '').replace(/\s+/g, '').trim();
            if (!imei) {
                this.showToast('Enter or select an IMEI', 'warning');
                return;
            }
            if (this.cartImeisInUse().includes(imei)) {
                this.showToast('IMEI already in cart', 'warning');
                return;
            }
            const product = this.imeiPickProduct;
            if (!product) return;

            if (this.imeiPickMode === 'bump' && this.imeiPickCartIndex != null) {
                const item = this.cart[this.imeiPickCartIndex];
                if (!item) { this.cancelImeiPick(); return; }
                if (item.qty >= item.max_stock) {
                    this.showToast('Maximum stock reached for ' + item.name, 'warning');
                    this.cancelImeiPick();
                    return;
                }
                item.imeis = [...(item.imeis || []), imei];
                item.qty = item.imeis.length;
                this.lastAddedId = item.id;
                this.playBeep(true);
            } else {
                this.pushCartLine(product, imei);
            }
            this.cancelImeiPick();
            this.syncSaleDiscount();
            this.search = '';
            this.$refs.searchInput?.focus();
        },

        cartImeisInUse(productId = null) {
            const list = [];
            for (const item of this.cart) {
                if (productId != null && item.id !== productId) continue;
                for (const im of (item.imeis || [])) list.push(String(im));
            }
            return list;
        },

        pushCartLine(product, imei) {
            const onSale = !!product.on_sale;
            const listPrice = Number(product.list_price ?? product.selling_price) || 0;
            const salePrice = Number(product.selling_price) || 0;
            const existing = this.cart.find(i => i.id === product.id);

            if (product.requires_imei) {
                if (existing) {
                    if (existing.qty >= product.stock_quantity) {
                        this.playBeep(false);
                        this.showToast('Maximum stock reached for ' + product.name, 'warning');
                        return;
                    }
                    existing.imeis = [...(existing.imeis || []), imei];
                    existing.qty = existing.imeis.length;
                    this.lastAddedId = product.id;
                    this.playBeep(true);
                } else {
                    this.cart.push({
                        id: product.id,
                        name: product.name,
                        price: onSale ? listPrice : salePrice,
                        list_price: listPrice,
                        sale_price: onSale ? salePrice : salePrice,
                        on_sale: onSale,
                        sale_percent: Number(product.sale_percent) || 0,
                        qty: 1,
                        max_stock: product.stock_quantity,
                        image: product.image || null,
                        image_url: product.image_url || this.productImageSrc(product) || this.fallbackImageFor(product),
                        sku: product.sku || null,
                        barcode: product.barcode || null,
                        category_name: product.category_name || null,
                        requires_imei: true,
                        imeis: [imei],
                        color: product.color || null,
                        ram: product.ram || null,
                        storage: product.storage || null,
                    });
                    this.lastAddedId = product.id;
                    this.playBeep(true);
                }
                this.syncSaleDiscount();
                this.search = '';
                this.$refs.searchInput?.focus();
                return;
            }

            if (existing) {
                if (existing.qty < product.stock_quantity) {
                    existing.qty++;
                    this.lastAddedId = product.id;
                    this.playBeep(true);
                } else {
                    this.playBeep(false);
                    this.showToast('Maximum stock reached for ' + product.name, 'warning');
                }
            } else {
                this.cart.push({
                    id: product.id,
                    name: product.name,
                    // Cart always shows actual/list price; sale is applied via locked discount.
                    price: onSale ? listPrice : salePrice,
                    list_price: listPrice,
                    sale_price: onSale ? salePrice : salePrice,
                    on_sale: onSale,
                    sale_percent: Number(product.sale_percent) || 0,
                    qty: 1,
                    max_stock: product.stock_quantity,
                    image: product.image || null,
                    image_url: product.image_url || this.productImageSrc(product) || this.fallbackImageFor(product),
                    sku: product.sku || null,
                    barcode: product.barcode || null,
                    category_name: product.category_name || null,
                    requires_imei: false,
                    imeis: [],
                    color: product.color || null,
                    ram: product.ram || null,
                    storage: product.storage || null,
                });
                this.lastAddedId = product.id;
                this.playBeep(true);
                if (onSale) {
                    this.showToast('SALE item added — sale savings applied (discount still editable)', 'info');
                }
            }
            this.syncSaleDiscount();
            this.search = '';
            this.$refs.searchInput.focus();
            this.$nextTick(() => {
                const box = document.getElementById('pos-cart-items');
                if (box) box.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            });
        },

        clearCart() {
            this.cart = [];
            this.lastAddedId = null;
            this.syncSaleDiscount();
        },

        hasSaleItems() {
            return this.posSaleEnabled && this.cart.some(i => i.on_sale);
        },

        getSaleSavings() {
            return this.cart.reduce((sum, item) => {
                if (!item.on_sale) return sum;
                const list = Number(item.list_price ?? item.price) || 0;
                const sale = Number(item.sale_price ?? item.price) || 0;
                return sum + Math.max(0, list - sale) * (Number(item.qty) || 0);
            }, 0);
        },

        salePercentLabel() {
            const saleItems = this.cart.filter(i => i.on_sale);
            if (!saleItems.length) return '';
            const percents = [...new Set(saleItems.map(i => Number(i.sale_percent) || 0).filter(p => p > 0))];
            if (percents.length === 1) return percents[0] + '% off';
            const savings = this.getSaleSavings();
            return 'Tk' + this.formatNumber(savings) + ' off';
        },

        syncSaleDiscount() {
            if (!this.hasSaleItems()) {
                if (this._saleDiscountActive) {
                    this.discountValue = 0;
                    this.discountType = 'percent';
                    this._saleDiscountActive = false;
                    this._lastSaleSavings = 0;
                }
                return;
            }
            // Sale items cannot use BAKI / EMI
            if (this.isBaki || this.isEmi) {
                this.isBaki = false;
                this.isEmi = false;
                this.showToast('BAKI / EMI turned off — not allowed with sale products', 'warning');
            }
            const saleSavings = Math.round(this.getSaleSavings() * 100) / 100;
            if (!this._saleDiscountActive) {
                this._saleDiscountActive = true;
                this.discountType = 'flat';
                this.discountValue = Math.max(saleSavings, Number(this.discountValue) || 0);
            } else if (this.discountType === 'flat') {
                // Keep any extra discount the cashier added above the sale savings.
                const prevSale = Number(this._lastSaleSavings) || 0;
                const current = Number(this.discountValue) || 0;
                const extra = Math.max(0, current - prevSale);
                this.discountValue = Math.round((saleSavings + extra) * 100) / 100;
            }
            this._lastSaleSavings = saleSavings;
        },

        cartQty(productId) {
            const item = this.cart.find(i => i.id === productId);
            return item ? item.qty : 0;
        },

        cartUnitCount() {
            return this.cart.reduce((s, i) => s + i.qty, 0);
        },

        productInitials(name) {
            if (!name) return '?';
            const parts = String(name).trim().split(/\s+/).filter(Boolean);
            if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
            return (parts[0][0] + parts[1][0]).toUpperCase();
        },

        updateQty(index, amount) {
            const item   = this.cart[index];
            if (!item) return;
            if (item.requires_imei) {
                if (amount > 0) {
                    const product = this.products.find(p => p.id === item.id);
                    if (!product) return;
                    this.openImeiPicker(product, 'bump', index);
                    return;
                }
                // decrease: drop last IMEI
                const newQty = item.qty + amount;
                if (newQty <= 0) {
                    if (this.lastAddedId === item.id) this.lastAddedId = null;
                    this.cart.splice(index, 1);
                    if (this.cart.length === 0) this.cartViewModalOpen = false;
                } else {
                    item.imeis = (item.imeis || []).slice(0, -1);
                    item.qty = item.imeis.length;
                    this.lastAddedId = item.id;
                }
                this.syncSaleDiscount();
                return;
            }
            const newQty = item.qty + amount;
            if (newQty <= 0) {
                if (this.lastAddedId === item.id) this.lastAddedId = null;
                this.cart.splice(index, 1);
                if (this.cart.length === 0) this.cartViewModalOpen = false;
            } else if (newQty <= item.max_stock) {
                item.qty = newQty;
                this.lastAddedId = item.id;
            } else {
                this.playBeep(false);
                this.showToast('Cannot exceed available stock for ' + item.name, 'warning');
            }
            this.syncSaleDiscount();
        },

        setCartQty(index, value) {
            const item = this.cart[index];
            if (!item) return;
            if (item.requires_imei) {
                this.showToast('Use + / − to add or remove IMEIs for this phone', 'info');
                return;
            }
            let qty = Math.floor(Number(value));
            if (!Number.isFinite(qty) || qty < 1) qty = 1;
            if (qty > item.max_stock) {
                qty = item.max_stock;
                this.playBeep(false);
                this.showToast('Max stock for ' + item.name + ' is ' + item.max_stock, 'warning');
            }
            item.qty = qty;
            this.lastAddedId = item.id;
            this.syncSaleDiscount();
        },

        setCartPrice(index, value) {
            const item = this.cart[index];
            if (!item || item.on_sale) return;
            let price = Number(value);
            if (!Number.isFinite(price) || price < 0) price = 0;
            item.price = Math.round(price * 100) / 100;
        },

        resetCartPrice(index) {
            const item = this.cart[index];
            if (!item || item.on_sale) return;
            item.price = Number(item.list_price ?? item.price) || 0;
        },

        openCartView() {
            if (this.cart.length === 0) {
                this.showToast('Cart is empty — add products first', 'warning');
                return;
            }
            this.cartViewModalOpen = true;
        },

        removeItem(index) {
            const removed = this.cart[index];
            this.cart.splice(index, 1);
            if (removed && this.lastAddedId === removed.id) this.lastAddedId = null;
            if (this.cart.length === 0) this.cartViewModalOpen = false;
            this.syncSaleDiscount();
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           DISCOUNT & COUPONS
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        getDiscount() {
            const base = this.getDiscountBase();
            let disc = 0;
            // Coupon takes priority over manual discount
            if (this.appliedCoupon) {
                disc = this.appliedCoupon.type === 'percent'
                    ? (base * this.appliedCoupon.value / 100)
                    : this.appliedCoupon.value;
            } else if (this.discountValue > 0) {
                disc = this.discountType === 'percent'
                    ? (base * Math.min(this.discountValue, 100) / 100)
                    : this.discountValue;
            }
            // Sale savings are always included; cashier can add more on top.
            if (this.hasSaleItems()) {
                disc = Math.max(disc, this.getSaleSavings());
            }
            return Math.max(0, Math.min(disc, base));
        },

        /** Amount discount can apply to (after exchange credit). Matches server Order::resolvePosSettlement. */
        getDiscountBase() {
            return Math.max(0, this.getTotal() - (Number(this.exchangeCredit) || 0));
        },

        applyCoupon() {
            const code = this.couponCode.trim().toUpperCase();
            if (!code) return;
            // Demo coupons - validate server-side in production
            const coupons = {
                'SAVE10':  { code: 'SAVE10',  type: 'percent', value: 10 },
                'FLAT50':  { code: 'FLAT50',  type: 'flat',    value: 50 },
                'WELCOME': { code: 'WELCOME', type: 'percent', value: 5  }
            };
            if (coupons[code]) {
                this.appliedCoupon = coupons[code];
                this.discountValue = 0;
                this.showToast('Coupon applied! ' + (coupons[code].type === 'percent' ? coupons[code].value + '% off' : 'Tk' + coupons[code].value + ' off'), 'success');
            } else {
                this.playBeep(false);
                this.showToast('Invalid coupon code: ' + code, 'error');
            }
        },

        removeCoupon() {
            this.appliedCoupon = null;
            this.couponCode    = '';
            this.showToast('Coupon removed', 'info');
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           TOTALS
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        getTotal() {
            return this.cart.reduce((s, i) => s + (i.price * i.qty), 0);
        },
        getPayableTotal() {
            return Math.max(0, this.getDiscountBase() - this.getDiscount());
        },
        getPaidAmount() {
            return (Number(this.payCash) || 0) + (Number(this.payCard) || 0) + (Number(this.payBkash) || 0);
        },
        /** Shortfall when customer pays less than bill — counted as Less / discount (BAKI off only). */
        getLessAmount() {
            if (this.isBaki || this.isEmi) return 0;
            return Math.max(0, this.getPayableTotal() - this.getPaidAmount());
        },
        /** Cart discount + pay-less amount (same figure stored as orders.discount_amount). */
        getTotalDiscountAmount() {
            if (this.isBaki || this.isEmi) return this.getDiscount();
            return this.getDiscount() + this.getLessAmount();
        },
        getChange() {
            if (this.isBaki) {
                return Math.max(0, this.getPaidAmount() - this.getBakiTotalDue());
            }
            if (this.isEmi) {
                return Math.max(0, this.getPaidAmount() - this.getEmiDownPayment());
            }
            return Math.max(0, this.getPaidAmount() - this.getPayableTotal());
        },
        getBakiTotalDue() {
            return Math.max(0, (Number(this.customerBakiBalance) || 0) + this.getPayableTotal());
        },
        getBakiAppliedPay() {
            return Math.min(this.getPaidAmount(), this.getBakiTotalDue());
        },
        getBakiTowardBill() {
            return Math.min(this.getBakiAppliedPay(), this.getPayableTotal());
        },
        getBakiNewCredit() {
            return Math.max(0, this.getPayableTotal() - this.getBakiTowardBill());
        },
        getBakiLeft() {
            return Math.max(0, this.getBakiTotalDue() - this.getBakiAppliedPay());
        },
        getEmiDownPayment() {
            const bill = this.getPayableTotal();
            const down = Math.max(0, Number(this.emiDownPayment) || 0);
            return Math.min(bill, Math.round(down * 100) / 100);
        },
        getEmiPrincipal() {
            return Math.max(0, Math.round((this.getPayableTotal() - this.getEmiDownPayment()) * 100) / 100);
        },
        getEmiMonthly() {
            const months = Math.max(1, Number(this.emiMonths) || 1);
            const principal = this.getEmiPrincipal();
            if (principal <= 0) return 0;
            return Math.round((principal / months) * 100) / 100;
        },
        canConfirmSale() {
            if (this.cart.length === 0) return false;
            if (this.isBaki) {
                const nameOk = (this.customerName || '').trim().length >= 2;
                const phoneOk = (this.customerPhone || '').trim().length >= 5;
                if (!nameOk || !phoneOk) return false;
                return true;
            }
            if (this.isEmi) {
                const nameOk = (this.customerName || '').trim().length >= 2;
                const phoneOk = (this.customerPhone || '').trim().length >= 5;
                if (!nameOk || !phoneOk) return false;
                const months = Math.floor(Number(this.emiMonths) || 0);
                if (months < 1 || months > 36) return false;
                if (this.getEmiPrincipal() <= 0) return false;
                return this.getPaidAmount() + 0.009 >= this.getEmiDownPayment();
            }
            // Fully covered by exchange credit — nothing to collect
            if (this.getPayableTotal() <= 0) return true;
            // Must enter what the customer pays (shortfall becomes Less)
            return this.getPaidAmount() > 0;
        },
        canProceedToCheckout() {
            if (this.cart.length === 0) return false;
            if (this.isExchangeMode && this.getTotal() < this.exchangeCredit) return false;
            return true;
        },
        hasLowStockItems() {
            return this.cart.some(i => i.qty >= i.max_stock);
        },
        formatNumber(n) {
            return Math.round(Number(n) || 0).toLocaleString('en-US');
        },
        getPaymentMethodString() {
            const methods = [];
            if (this.payCash   > 0) methods.push('Cash');
            if (this.payCard   > 0) methods.push('Card');
            if (this.payBkash  > 0) methods.push('bKash');
            if (methods.length > 1) return methods.join(' + ');
            if (methods.length === 1) return methods[0].toLowerCase();
            return this.isBaki ? 'baki' : (this.isEmi ? 'emi' : 'cash');
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           HOLD / RESUME CARTS
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        suspendCurrentCart() {
            if (!this.cart.length) return;
            const rec = {
                id:           Date.now(),
                time:         new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                cartData:     JSON.parse(JSON.stringify(this.cart)),
                customerName: this.customerName,
                customerPhone:this.customerPhone,
                total:        this.getTotal()
            };
            this.heldCarts.push(rec);
            localStorage.setItem('nexa_held_carts', JSON.stringify(this.heldCarts));
            this.cart = [];
            this.customerName = '';
            this.customerPhone = '';
            this.customerBakiBalance = 0;
            this.customerEmiBalance = 0;
            this.isBaki = false;
            this.isEmi = false;
            this.emiDownPayment = 0;
            this.emiMonths = 3;
            this.syncSaleDiscount();
            this.showToast('Cart suspended. Ready for next customer.', 'info');
        },
        async resumeHeldCart(index) {
            const rec = this.heldCarts[index];
            if (this.cart.length > 0) {
                const ok = await window.adminConfirm({
                    title: 'Resume cart?',
                    message: 'Resume this cart? Your current cart will be cleared.',
                    confirmText: 'Resume',
                    tone: 'warning',
                });
                if (!ok) return;
            }
            this.cart          = JSON.parse(JSON.stringify(rec.cartData));
            this.customerName  = rec.customerName  || '';
            this.customerPhone = rec.customerPhone || '';
            this.customerBakiBalance = 0;
            this.customerEmiBalance = 0;
            this.isBaki = false;
            this.isEmi = false;
            this.emiDownPayment = 0;
            this.emiMonths = 3;
            if (this.customerPhone) this.searchCustomer();
            this.heldCarts.splice(index, 1);
            localStorage.setItem('nexa_held_carts', JSON.stringify(this.heldCarts));
            this.holdCartsModalOpen = false;
            this.syncSaleDiscount();
            this.showToast('Cart resumed!', 'success');
        },
        async deleteHeldCart(index) {
            const ok = await window.adminConfirm({
                title: 'Delete?',
                message: 'Permanently delete this suspended cart?',
                confirmText: 'Delete',
                tone: 'danger',
            });
            if (!ok) return;
            this.heldCarts.splice(index, 1);
            localStorage.setItem('nexa_held_carts', JSON.stringify(this.heldCarts));
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           CHECKOUT
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
openCheckout() {
            if (!this.canProceedToCheckout()) {
                this.showToast('Cart must total Tk' + this.formatNumber(this.exchangeCredit) + ' or more for exchange.', 'error');
                return;
            }
            if (this.isBaki) {
                const nameOk = (this.customerName || '').trim().length >= 2;
                const phoneOk = (this.customerPhone || '').trim().length >= 5;
                if (!nameOk || !phoneOk) {
                    this.showToast('Name and mobile are required for BAKI sales.', 'error');
                    return;
                }
            }
            if (this.isEmi) {
                const nameOk = (this.customerName || '').trim().length >= 2;
                const phoneOk = (this.customerPhone || '').trim().length >= 5;
                if (!nameOk || !phoneOk) {
                    this.showToast('Name and mobile are required for EMI sales.', 'error');
                    return;
                }
                const months = Math.floor(Number(this.emiMonths) || 0);
                if (months < 1 || months > 36) {
                    this.showToast('Enter EMI months between 1 and 36.', 'error');
                    return;
                }
                if (this.getEmiPrincipal() <= 0) {
                    this.showToast('Lower down payment so some amount remains for EMI.', 'error');
                    return;
                }
                if (this.isExchangeMode) {
                    this.showToast('EMI cannot be combined with exchange.', 'error');
                    return;
                }
            }
            if (this.hasLowStockItems()) this.showToast('! Some items are at stock limit - check before confirming', 'warning');
            for (const item of this.cart) {
                if (item.requires_imei && (!item.imeis || item.imeis.length !== item.qty)) {
                    this.showToast('IMEI missing for ' + item.name, 'error');
                    return;
                }
            }
            // Leave cash empty so cashier types what the customer pays; Exact fills bill total
            this.payCash  = '';
            this.payCard  = 0;
            this.payBkash = 0;
            this.checkoutModalOpen = true;
            this.$nextTick(() => {
                const cash = document.querySelector('.modal .sp-input');
                if (cash) { cash.focus(); cash.select(); }
            });
        },

        async submitOrder() {
            if (this.isProcessing || !this.canConfirmSale()) return;
            this.isProcessing = true;

            if (this.isBaki && !this.isOnline) {
                this.isProcessing = false;
                this.playBeep(false);
                this.showToast('BAKI sales require an online connection.', 'error');
                return;
            }
            if (this.isEmi && !this.isOnline) {
                this.isProcessing = false;
                this.playBeep(false);
                this.showToast('EMI sales require an online connection.', 'error');
                return;
            }

            const payload = {
                client_uuid:             (window.crypto && crypto.randomUUID)
                    ? crypto.randomUUID()
                    : ('off-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10)),
                cart:                    this.cart,
                items:                   this.cart,
                total_amount:            this.getTotal(),
                discount_amount:         this.getTotalDiscountAmount(),
                coupon_code:             this.appliedCoupon?.code || null,
                payment_method:          this.getPaymentMethodString(),
                paid_amount:             this.getPaidAmount(),
                cash_paid:               Number(this.payCash) || 0,
                card_paid:               Number(this.payCard) || 0,
                mobile_paid:             Number(this.payBkash) || 0,
                is_baki:                 !!this.isBaki,
                is_emi:                  !!this.isEmi,
                emi_months:              this.isEmi ? (Number(this.emiMonths) || 3) : null,
                emi_down_payment:        this.isEmi ? this.getEmiDownPayment() : 0,
                customer_name:           this.customerName,
                customer_phone:          this.customerPhone,
                created_at:              new Date().toISOString(),
                is_exchange:             this.isExchangeMode,
                exchange_for_order_id:   this.exchangeOrderId,
                return_product_id:       this.returnProductId,
                return_qty:              this.returnQty,
                exchange_credit:         this.exchangeCredit,
                counter_id:              this.isAdminPos ? (Number(this.selectedCounterId) || null) : null,
            };

            if (this.isAdminPos && !this.hasOpenAdminCounter()) {
                this.isProcessing = false;
                this.playBeep(false);
                this.showToast('Open an unassigned counter with opening cash before billing.', 'error');
                return;
            }

            if (this.isOnline) {
                try {
                    const res  = await fetch('{{ route('pos.checkout') }}', {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body:    JSON.stringify(payload)
                    });
                    const data = await res.json();

                    if (res.ok && data.success) {
                        this.playBeep(true);
                        const soldItems = JSON.parse(JSON.stringify(this.cart));
                        // Instant local stock update — no page reload needed
                        this.applyStockUpdates(data.stock_updates, soldItems, {
                            return_product_id: this.isExchangeMode ? this.returnProductId : null,
                            return_qty: this.isExchangeMode ? this.returnQty : 0,
                        });

                        const paidSnap = this.getPaidAmount();
                        const lessSnap = this.getLessAmount();
                        const bakiLeftSnap = data.baki_balance ?? this.getBakiLeft();
                        const creditSnap = data.credit_amount ?? (this.isEmi ? this.getEmiPrincipal() : this.getBakiNewCredit());
                        const changeSnap = data.change ?? (this.isBaki || this.isEmi ? this.getChange() : Math.max(0, paidSnap - this.getPayableTotal()));
                        const customerSnap = this.customerName || 'Walk-in Customer';
                        const wasBaki = this.isBaki;
                        const wasEmi = this.isEmi;
                        const emiMonthsSnap = this.emiMonths;
                        const emiDownSnap = this.getEmiDownPayment();

                        this.cart = []; this.customerName = ''; this.customerPhone = '';
                        this.customerBakiBalance = 0; this.customerEmiBalance = 0;
                        this.isBaki = false; this.isEmi = false;
                        this.emiDownPayment = 0; this.emiMonths = 3;
                        this.discountValue = 0; this.appliedCoupon = null; this.couponCode = '';
                        this._saleDiscountActive = false;
                        this._lastSaleSavings = 0;
                        this.lastAddedId = null;
                        this.checkoutModalOpen = false;

                        this.lastSale = {
                            order_id: data.order_id,
                            invoice_no: data.invoice_no || ('#' + data.order_id),
                            paid_amount: data.paid_amount ?? paidSnap,
                            change: changeSnap,
                            discount_amount: data.discount_amount ?? lessSnap,
                            credit_amount: creditSnap,
                            baki_balance: bakiLeftSnap,
                            emi_balance: data.emi_balance ?? 0,
                            is_baki: wasBaki,
                            is_emi: wasEmi,
                            emi_months: emiMonthsSnap,
                            emi_down_payment: emiDownSnap,
                            total_amount: data.total_amount ?? 0,
                            customer: customerSnap,
                            receipt_url: data.receipt_url || ('/pos/receipt/' + data.order_id),
                            receipt_html: null,
                            offline: false,
                        };
                        this.invoiceModalOpen = true;
                        if (wasEmi) {
                            this.showToast('EMI sale complete! Remaining: Tk' + this.formatNumber(creditSnap) + ' over ' + emiMonthsSnap + ' months', 'success');
                        } else if (wasBaki) {
                            this.showToast('Sale complete! Baki left: Tk' + this.formatNumber(bakiLeftSnap), 'success');
                        } else if (lessSnap > 0) {
                            this.showToast('Sale complete! Less: Tk' + this.formatNumber(lessSnap), 'success');
                        } else {
                            this.showToast('Sale complete! Change to return: Tk' + this.formatNumber(changeSnap), 'success');
                        }
                    } else {
                        this.playBeep(false);
                        this.showToast('Error: ' + (data.message || 'Something went wrong'), 'error');
                    }
                } catch(e) {
                    // Only queue offline if we never got a successful JSON body
                    // (avoid duplicate when server committed but response was lost —
                    // client_uuid + unique constraint makes resync safe).
                    this.isOnline = false;
                    if (payload.is_baki || payload.is_emi) {
                        this.showToast('Connection lost. Credit/EMI sale was not saved — retry when online.', 'error');
                    } else {
                        this.saveOfflineSale(payload);
                    }
                }
            } else {
                this.saveOfflineSale(payload);
            }

            this.isProcessing = false;
        },

        /**
         * Keep product grid stock in sync after a sale without reloading POS.
         * Prefer server stock_updates when present; otherwise deduct from sold cart lines.
         */
        applyStockUpdates(serverUpdates, soldItems = [], exchange = {}) {
            const byId = {};

            (soldItems || []).forEach((item) => {
                const id = Number(item.id);
                if (!id) return;
                byId[id] = (byId[id] || 0) + (Number(item.qty) || 0);
            });

            Object.entries(byId).forEach(([id, qty]) => {
                const product = this.products.find(p => Number(p.id) === Number(id));
                if (!product) return;
                product.stock_quantity = Math.max(0, (Number(product.stock_quantity) || 0) - qty);
            });

            const returnId = Number(exchange?.return_product_id || 0);
            const returnQty = Number(exchange?.return_qty || 0);
            if (returnId && returnQty > 0) {
                const returned = this.products.find(p => Number(p.id) === returnId);
                if (returned) {
                    returned.stock_quantity = (Number(returned.stock_quantity) || 0) + returnQty;
                }
            }

            if (Array.isArray(serverUpdates) && serverUpdates.length) {
                serverUpdates.forEach((row) => {
                    const product = this.products.find(p => Number(p.id) === Number(row.id));
                    if (!product || row.stock_quantity == null) return;
                    product.stock_quantity = Math.max(0, Number(row.stock_quantity) || 0);
                    if (Array.isArray(row.available_imeis)) {
                        product.available_imeis = row.available_imeis;
                    }
                });
            }

            // Keep open cart line limits honest if same SKU is still in another held cart later
            this.cart.forEach((item) => {
                const product = this.products.find(p => Number(p.id) === Number(item.id));
                if (product) {
                    item.max_stock = Number(product.stock_quantity) || 0;
                    if (item.qty > item.max_stock) item.qty = item.max_stock;
                }
            });
        },

        saveOfflineSale(payload) {
            if (this.isExchangeMode) {
                this.showToast('Cannot process exchanges while offline.', 'error');
                return;
            }

            const paidSnap = this.getPaidAmount();
            const lessSnap = this.getLessAmount();
            const changeSnap = this.getChange();
            const customerSnap = this.customerName || 'Walk-in Customer';
            const invoiceNo = 'OFF-' + Date.now().toString().slice(-10);
            const cartSnap = JSON.parse(JSON.stringify(this.cart));

            payload.local_invoice_no = invoiceNo;
            payload.items = cartSnap;
            payload.cart = cartSnap;

            const offline = JSON.parse(localStorage.getItem('nexa_offline_orders')) || [];
            offline.push(payload);
            localStorage.setItem('nexa_offline_orders', JSON.stringify(offline));
            this.offlinePendingTick++;

            // Reduce local stock so POS stays accurate until sync
            this.applyStockUpdates(null, cartSnap);

            const receiptHtml = this.buildOfflineReceiptHtml({
                invoiceNo,
                customer: customerSnap,
                phone: this.customerPhone || '',
                items: cartSnap,
                total: this.getTotal(),
                discount: this.getTotalDiscountAmount(),
                includesSale: this.hasSaleItems(),
                payable: this.getPayableTotal(),
                paid: paidSnap,
                change: changeSnap,
                method: this.getPaymentMethodString(),
                createdAt: payload.created_at,
            });

            this.playBeep(true);
            this.cart = []; this.customerName = ''; this.customerPhone = '';
            this.discountValue = 0; this.appliedCoupon = null; this.couponCode = '';
            this._saleDiscountActive = false;
            this._lastSaleSavings = 0;
            this.lastAddedId = null;
            this.checkoutModalOpen = false;

            this.lastSale = {
                order_id: null,
                invoice_no: invoiceNo,
                paid_amount: paidSnap,
                change: changeSnap,
                discount_amount: payload.discount_amount || 0,
                total_amount: payload.total_amount,
                customer: customerSnap,
                receipt_url: null,
                receipt_html: receiptHtml,
                offline: true,
            };
            this.invoiceModalOpen = true;
            this.showToast('Offline bill saved — print now. Sync when network returns.', 'warning');
        },

        buildOfflineReceiptHtml(data) {
            const esc = (s) => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            const money = (n) => this.formatNumber(n);
            const when = new Date(data.createdAt || Date.now());
            const fmt = { timeZone: 'Asia/Dhaka', day: '2-digit', month: 'short', year: 'numeric' };
            const fmtTime = { timeZone: 'Asia/Dhaka', hour: '2-digit', minute: '2-digit', hour12: true };
            const dateStr = when.toLocaleDateString('en-GB', fmt);
            const timeStr = when.toLocaleTimeString('en-GB', fmtTime);
            const printed = new Date().toLocaleString('en-GB', {
                timeZone: 'Asia/Dhaka', day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit', hour12: true,
            });
            const rows = (data.items || []).map(i => `
                <tr>
                    <td class="item-name">${esc(i.name || 'Product')}</td>
                    <td class="text-center">${Number(i.qty) || 0}</td>
                    <td class="text-right">${money(i.price)}</td>
                    <td class="text-right">${money((Number(i.price) || 0) * (Number(i.qty) || 0))}</td>
                </tr>`).join('');

            return `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invoice ${esc(data.invoiceNo)}</title>
<style>
*{box-sizing:border-box}
body{font-family:Segoe UI,Tahoma,sans-serif;font-size:12px;color:#0f172a;margin:0;padding:0;background:#fff}
.sheet{width:80mm;max-width:80mm;margin:0 auto;padding:12px}
.brand{text-align:center;border-bottom:2px solid #0f172a;padding-bottom:8px;margin-bottom:8px}
.brand .doc{font-size:9px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#64748b}
.brand h1{margin:4px 0 0;font-size:16px;font-weight:800}
.meta{width:100%;border-collapse:collapse;margin-bottom:8px;font-size:11px}
.meta td{padding:2px 0}.meta .lbl{color:#64748b;font-weight:600}.meta .val{text-align:right;font-weight:700}
.party{background:#f8fafc;border:1px solid #cbd5e1;border-radius:6px;padding:8px;margin-bottom:8px}
.party .eyebrow{font-size:9px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#64748b}
.party .name{font-weight:800;margin:2px 0}
.section{font-size:9px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#64748b;border-bottom:1px solid #cbd5e1;padding-bottom:3px;margin:6px 0 4px}
table.items{width:100%;border-collapse:collapse}
table.items th{font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b;border-bottom:1.5px solid #0f172a;padding:4px 0}
table.items td{padding:5px 0;border-bottom:1px dotted #e2e8f0;vertical-align:top;font-size:11px}
.item-name{font-weight:700}.text-center{text-align:center}.text-right{text-align:right}
.totals{width:100%;border-collapse:collapse;border-top:1.5px solid #0f172a;margin-top:4px}
.totals td{padding:3px 0;font-size:11px}.totals .lbl{color:#64748b}
.totals .grand td{padding-top:7px;font-size:13px;font-weight:800;border-top:1px dashed #0f172a}
.pay{margin-top:8px;border:1px solid #cbd5e1;border-radius:6px;padding:7px 9px;background:#f8fafc}
.pay table{width:100%}.pay td{padding:2px 0;font-size:11px}
.footer{text-align:center;margin-top:12px;padding-top:10px;border-top:2px solid #0f172a}
.footer .thanks{font-weight:800;margin:0 0 4px}.footer .note{margin:0;font-size:10px;color:#64748b}
.footer .stamp{margin-top:8px;font-size:9px;color:#64748b;font-family:ui-monospace,Consolas,monospace}
.badge{text-align:center;font-weight:800;border:2px dashed #b45309;color:#b45309;padding:5px;margin-bottom:8px;font-size:11px;letter-spacing:.06em}
</style></head><body>
<div class="sheet">
<div class="brand"><div class="doc">Sales Invoice / POS Receipt</div><h1>${esc(this.shopName)}</h1></div>
${data.includesSale ? '<div class="badge" style="border-color:#e11d48;color:#be123c">*** SALE ***</div>' : ''}
<div class="badge">*** OFFLINE BILL ***</div>
<table class="meta">
<tr><td class="lbl">Invoice No</td><td class="val">${esc(data.invoiceNo)}</td></tr>
<tr><td class="lbl">Date</td><td class="val">${esc(dateStr)}</td></tr>
<tr><td class="lbl">Time</td><td class="val">${esc(timeStr)} (BST)</td></tr>
<tr><td class="lbl">Cashier</td><td class="val">${esc(this.cashierName)}</td></tr>
<tr><td class="lbl">Status</td><td class="val">PENDING SYNC</td></tr>
</table>
<div class="party"><div class="eyebrow">Bill To</div><p class="name">${esc(data.customer || 'Walk-in Customer')}</p>
${data.phone ? `<p>Phone: ${esc(data.phone)}</p>` : ''}</div>
<div class="section">Items</div>
<table class="items">
<thead><tr><th class="text-left">Description</th><th class="text-center">Qty</th><th class="text-right">Rate</th><th class="text-right">Amount</th></tr></thead>
<tbody>${rows}</tbody>
</table>
<table class="totals">
<tr><td class="lbl">Subtotal</td><td class="text-right">৳${money(data.total)}</td></tr>
${(data.discount || 0) > 0 ? `<tr><td class="lbl">${data.includesSale ? 'SALE discount' : 'Discount'}</td><td class="text-right">- ৳${money(data.discount)}</td></tr>` : ''}
<tr class="grand"><td>Grand total</td><td class="text-right">৳${money(data.payable)}</td></tr>
</table>
<div class="pay"><table>
<tr><td class="lbl">Payment method</td><td class="text-right" style="font-weight:700">${esc(String(data.method || 'cash').toUpperCase())}</td></tr>
<tr><td class="lbl">Amount paid</td><td class="text-right" style="font-weight:700">৳${money(data.paid)}</td></tr>
${(data.change || 0) > 0 ? `<tr><td class="lbl">Change due</td><td class="text-right" style="font-weight:800">৳${money(data.change)}</td></tr>` : ''}
</table></div>
<div class="footer">
<p class="thanks">Thank you for your business</p>
<p class="note">Will sync when network is available. Please retain this invoice.</p>
<p class="stamp">Printed: ${esc(printed)} · Asia/Dhaka</p>
</div>
</div></body></html>`;
        },

        printLastReceipt() {
            if (this.lastSale?.receipt_html) {
                this.printHtmlDocument(this.lastSale.receipt_html);
                return;
            }
            if (!this.lastSale?.receipt_url) {
                this.showToast('No receipt available to print', 'error');
                return;
            }
            // Prefer a dedicated print window — iframe.contentWindow.print() often
            // prints the blank POS shell (title "POS Terminal") in Chromium.
            this.printReceiptUrl(this.lastSale.receipt_url);
        },

        printReceiptUrl(url) {
            const printUrl = url + (url.includes('?') ? '&' : '?') + 'print=1';
            const w = window.open(printUrl, 'nexa_pos_receipt', 'width=440,height=720');
            if (!w) {
                this.showToast('Allow pop-ups to print receipts', 'error');
                // Last resort: navigate iframe then try after load
                const frame = this.$refs.receiptFrame;
                if (frame) {
                    frame.onload = () => {
                        try {
                            frame.contentWindow?.focus();
                            frame.contentWindow?.print();
                        } catch (e) {}
                    };
                    frame.src = printUrl;
                }
                return;
            }
            // Receipt view auto-prints when ?print=1; also retry after load for stubborn browsers
            const tryPrint = () => {
                try { w.focus(); w.print(); } catch (e) {}
            };
            w.addEventListener?.('load', () => setTimeout(tryPrint, 150));
            setTimeout(tryPrint, 600);
        },

        printHtmlDocument(html) {
            const w = window.open('', 'nexa_pos_receipt', 'width=440,height=720');
            if (!w) {
                this.showToast('Allow pop-ups to print receipts', 'error');
                return;
            }
            w.document.open();
            w.document.write(html);
            w.document.close();
            const tryPrint = () => {
                try { w.focus(); w.print(); } catch (e) {}
            };
            if (w.document.readyState === 'complete') {
                setTimeout(tryPrint, 150);
            } else {
                w.addEventListener('load', () => setTimeout(tryPrint, 150));
                setTimeout(tryPrint, 600);
            }
        },

        closeInvoiceModal() {
            this.invoiceModalOpen = false;
            this.$nextTick(() => {
                if (this.$refs.searchInput) this.$refs.searchInput.focus();
            });
        },

        /* ───────────────────────────────
           OFFLINE SYNC (ask permission first)
        ─────────────────────────────── */
        pendingOfflineCount() {
            this.offlinePendingTick; // dependency for Alpine reactivity
            try {
                return (JSON.parse(localStorage.getItem('nexa_offline_orders')) || []).length;
            } catch (e) {
                return 0;
            }
        },
        selectedCounterLabel() {
            const c = (this.posCounters || []).find(x => String(x.id) === String(this.selectedCounterId));
            if (!c) return 'Select counter';
            return c.has_open_session ? c.name : (c.name + ' (closed)');
        },
        hasOpenAdminCounter() {
            if (!this.isAdminPos) return true;
            const c = (this.posCounters || []).find(x => String(x.id) === String(this.selectedCounterId));
            return !!(c && c.has_open_session);
        },
        async confirmSyncOffline() {
            this.syncPromptOpen = false;
            await this.syncOfflineOrders();
        },
        async syncOfflineOrders() {
            const offline = JSON.parse(localStorage.getItem('nexa_offline_orders')) || [];
            if (!offline.length || this.isSyncing) return;
            this.isSyncing = true;
            try {
                const res  = await fetch('{{ route('pos.sync') }}', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body:    JSON.stringify({
                        orders: offline,
                        counter_id: this.isAdminPos ? (Number(this.selectedCounterId) || null) : null,
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    localStorage.removeItem('nexa_offline_orders');
                    this.offlinePendingTick++;
                    this.showToast(`Synced ${data.synced} offline bill(s)!`, 'success');
                    setTimeout(() => window.location.reload(), 1600);
                } else {
                    this.showToast(data.message || 'Sync failed. Try again.', 'error');
                }
            } catch(e) {
                this.showToast('Sync failed. Check network and try again.', 'error');
            }
            this.isSyncing = false;
        }
    };
}
</script>
</x-pos-layout>