/**
 * Admin feedback — toast notifications + confirm dialogs for add/update/delete actions.
 */
export function initAdminFeedback() {
    ensureDom();

    window.adminToast = toast;
    window.adminConfirm = confirmDialog;
    window.adminAlert = (message, type = 'info') => toast(message, type);

    bindConfirmForms();
    bindConfirmButtons();
    showFlashFromPage();
}

function ensureDom() {
    if (document.getElementById('admin-toast-host')) return;

    const host = document.createElement('div');
    host.id = 'admin-toast-host';
    host.className = 'admin-toast-host';
    host.setAttribute('aria-live', 'polite');
    host.setAttribute('aria-relevant', 'additions');
    document.body.appendChild(host);

    const modal = document.createElement('div');
    modal.id = 'admin-confirm-modal';
    modal.className = 'admin-confirm';
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
        <div class="admin-confirm__backdrop" data-admin-confirm-cancel></div>
        <div class="admin-confirm__panel" role="dialog" aria-modal="true" aria-labelledby="admin-confirm-title">
            <div class="admin-confirm__icon" data-admin-confirm-icon></div>
            <h3 class="admin-confirm__title" id="admin-confirm-title" data-admin-confirm-title>Are you sure?</h3>
            <p class="admin-confirm__message" data-admin-confirm-message></p>
            <div class="admin-confirm__actions">
                <button type="button" class="admin-confirm__btn admin-confirm__btn--ghost" data-admin-confirm-cancel>Cancel</button>
                <button type="button" class="admin-confirm__btn admin-confirm__btn--danger" data-admin-confirm-ok>Confirm</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function iconSvg(type) {
    if (type === 'success') {
        return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
    }
    if (type === 'error') {
        return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`;
    }
    if (type === 'warning') {
        return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`;
    }
    if (type === 'danger') {
        return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>`;
    }
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>`;
}

export function toast(message, type = 'success', options = {}) {
    if (!message) return;
    ensureDom();

    const host = document.getElementById('admin-toast-host');
    const el = document.createElement('div');
    el.className = `admin-toast admin-toast--${type}`;
    el.setAttribute('role', 'status');
    el.innerHTML = `
        <span class="admin-toast__icon">${iconSvg(type)}</span>
        <div class="admin-toast__body">
            <p class="admin-toast__text"></p>
        </div>
        <button type="button" class="admin-toast__close" aria-label="Dismiss">&times;</button>
    `;
    el.querySelector('.admin-toast__text').textContent = String(message);
    host.appendChild(el);

    requestAnimationFrame(() => el.classList.add('is-visible'));

    const ttl = options.duration ?? (type === 'error' ? 5200 : 3400);
    let timer = setTimeout(() => dismiss(el), ttl);

    el.querySelector('.admin-toast__close')?.addEventListener('click', () => {
        clearTimeout(timer);
        dismiss(el);
    });
    el.addEventListener('mouseenter', () => clearTimeout(timer));
    el.addEventListener('mouseleave', () => {
        timer = setTimeout(() => dismiss(el), 1600);
    });
}

function dismiss(el) {
    if (!el || el.classList.contains('is-leaving')) return;
    el.classList.add('is-leaving');
    el.classList.remove('is-visible');
    setTimeout(() => el.remove(), 220);
}

let confirmResolver = null;

export function confirmDialog(options = {}) {
    ensureDom();
    const modal = document.getElementById('admin-confirm-modal');
    const title = options.title || 'Please confirm';
    const message = options.message || 'Are you sure you want to continue?';
    const confirmText = options.confirmText || 'Confirm';
    const cancelText = options.cancelText || 'Cancel';
    const tone = options.tone || 'danger';

    modal.querySelector('[data-admin-confirm-title]').textContent = title;
    modal.querySelector('[data-admin-confirm-message]').textContent = message;
    const okBtn = modal.querySelector('[data-admin-confirm-ok]');
    const cancelBtns = modal.querySelectorAll('[data-admin-confirm-cancel]');
    okBtn.textContent = confirmText;
    okBtn.className = `admin-confirm__btn admin-confirm__btn--${tone === 'primary' ? 'primary' : tone === 'warning' ? 'warning' : 'danger'}`;
    cancelBtns.forEach((btn) => {
        if (btn.tagName === 'BUTTON') btn.textContent = cancelText;
    });

    const iconWrap = modal.querySelector('[data-admin-confirm-icon]');
    iconWrap.className = `admin-confirm__icon admin-confirm__icon--${tone}`;
    iconWrap.innerHTML = iconSvg(tone === 'primary' ? 'info' : tone);

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('admin-confirm-open');
    setTimeout(() => okBtn.focus(), 30);

    return new Promise((resolve) => {
        confirmResolver = resolve;
    });
}

function closeConfirm(result) {
    const modal = document.getElementById('admin-confirm-modal');
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('admin-confirm-open');
    const resolve = confirmResolver;
    confirmResolver = null;
    if (resolve) resolve(result);
}

function bindConfirmForms() {
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.confirmHandled === '1') {
            delete form.dataset.confirmHandled;
            return;
        }

        const message = form.getAttribute('data-confirm');
        if (!message) return;

        e.preventDefault();
        e.stopPropagation();

        const ok = await confirmDialog({
            title: form.getAttribute('data-confirm-title') || 'Please confirm',
            message,
            confirmText: form.getAttribute('data-confirm-ok') || 'Yes, continue',
            cancelText: form.getAttribute('data-confirm-cancel') || 'Cancel',
            tone: form.getAttribute('data-confirm-tone') || 'danger',
        });

        if (!ok) return;
        form.dataset.confirmHandled = '1';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }, true);

    document.addEventListener('click', (e) => {
        const modal = document.getElementById('admin-confirm-modal');
        if (!modal?.classList.contains('is-open')) return;
        if (e.target.closest?.('[data-admin-confirm-ok]')) {
            e.preventDefault();
            closeConfirm(true);
        } else if (e.target.closest?.('[data-admin-confirm-cancel]')) {
            e.preventDefault();
            closeConfirm(false);
        }
    });

    document.addEventListener('keydown', (e) => {
        const modal = document.getElementById('admin-confirm-modal');
        if (!modal?.classList.contains('is-open')) return;
        if (e.key === 'Escape') {
            e.preventDefault();
            closeConfirm(false);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            closeConfirm(true);
        }
    });
}

function bindConfirmButtons() {
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest?.('[data-confirm-click]');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        const ok = await confirmDialog({
            title: btn.getAttribute('data-confirm-title') || 'Please confirm',
            message: btn.getAttribute('data-confirm-click'),
            confirmText: btn.getAttribute('data-confirm-ok') || 'Yes, continue',
            cancelText: btn.getAttribute('data-confirm-cancel') || 'Cancel',
            tone: btn.getAttribute('data-confirm-tone') || 'danger',
        });
        if (!ok) return;

        const formId = btn.getAttribute('form');
        const form = formId ? document.getElementById(formId) : btn.closest('form');
        if (form) {
            form.dataset.confirmHandled = '1';
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.submit();
            return;
        }

        const href = btn.getAttribute('href') || btn.getAttribute('data-href');
        if (href) window.location.href = href;
    }, true);
}

function showFlashFromPage() {
    const flash = window.__ADMIN_FLASH__ || {};
    if (flash.success) toast(flash.success, 'success');
    if (flash.error) toast(flash.error, 'error');
    if (flash.warning) toast(flash.warning, 'warning');
    if (flash.info) toast(flash.info, 'info');

    // Hide duplicate inline banners when toast already showed them
    if (flash.success || flash.error || flash.warning || flash.info) {
        document.querySelectorAll('[data-admin-flash-banner]').forEach((el) => {
            el.style.display = 'none';
        });
    }
}
