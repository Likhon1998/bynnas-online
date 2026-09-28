/**
 * Admin top progress bar — fills while navigating between pages.
 */
export function initAdminProgress() {
    if (!document.body?.classList.contains('admin-panel')) {
        return;
    }

    const bar = document.getElementById('admin-progress-bar');
    const peg = document.getElementById('admin-progress-peg');
    if (!bar) return;

    let value = 0;
    let trickleTimer = null;
    let active = false;

    const setWidth = (n) => {
        value = Math.max(0, Math.min(100, n));
        bar.style.transform = `scaleX(${value / 100})`;
    };

    const show = () => {
        bar.classList.add('is-active');
        peg?.classList.add('is-active');
        document.documentElement.classList.add('admin-progress-busy');
    };

    const hide = () => {
        bar.classList.remove('is-active');
        peg?.classList.remove('is-active');
        document.documentElement.classList.remove('admin-progress-busy');
        setTimeout(() => {
            if (!active) setWidth(0);
        }, 220);
    };

    const trickle = () => {
        if (!active) return;
        if (value >= 92) return;
        const step = value < 30 ? 8 : value < 60 ? 4 : value < 80 ? 2 : 0.6;
        setWidth(value + step + Math.random() * 1.5);
        trickleTimer = setTimeout(trickle, 280 + Math.random() * 220);
    };

    const start = () => {
        if (active) return;
        active = true;
        clearTimeout(trickleTimer);
        setWidth(0);
        show();
        requestAnimationFrame(() => setWidth(12 + Math.random() * 8));
        trickleTimer = setTimeout(trickle, 200);
        try {
            sessionStorage.setItem('admin-progress-pending', '1');
        } catch (e) {}
    };

    const done = () => {
        if (!active && value === 0) {
            // finishing arrival from a previous page navigation
        }
        active = true;
        clearTimeout(trickleTimer);
        show();
        setWidth(100);
        active = false;
        try {
            sessionStorage.removeItem('admin-progress-pending');
        } catch (e) {}
        setTimeout(hide, 280);
    };

    const shouldTrackLink = (a) => {
        if (!a) return false;
        if (a.hasAttribute('data-no-progress') || a.closest?.('[data-no-progress]')) return false;
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return false;
        if (a.hasAttribute('download')) return false;
        if (a.target && a.target !== '_self') return false;
        try {
            const url = new URL(href, window.location.origin);
            if (url.origin !== window.location.origin) return false;
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
                return false;
            }
        } catch (e) {
            return false;
        }
        return true;
    };

    document.addEventListener('click', (e) => {
        if (e.defaultPrevented) return;
        if (e.button !== 0) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const a = e.target.closest?.('a[href]');
        if (!shouldTrackLink(a)) return;
        start();
    }, true);

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.target && form.target !== '_self') return;
        if (form.hasAttribute('data-no-progress') || form.closest?.('[data-no-progress]')) return;
        start();
    }, true);

    window.addEventListener('pageshow', (e) => {
        // bfcache restore
        if (e.persisted) {
            done();
        }
    });

    // Finish bar only when arriving from a tracked navigation — no flash on refresh/filter.
    let pending = false;
    try {
        pending = sessionStorage.getItem('admin-progress-pending') === '1';
    } catch (e) {}

    if (pending) {
        active = true;
        setWidth(78);
        show();
        requestAnimationFrame(() => done());
    }
}
