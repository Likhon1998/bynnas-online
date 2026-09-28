import './bootstrap';

import Alpine from 'alpinejs';
import { initAdminProgress } from './admin-progress';
import { initAdminFeedback } from './admin-feedback';

window.Alpine = Alpine;

function startAlpineWhenReady() {
    // Admin layout never defines storefrontCart — start immediately (no 2s delay / console error).
    const isAdmin = document.body?.classList?.contains('admin-panel');
    if (isAdmin || typeof window.storefrontCart === 'function') {
        Alpine.start();
        return;
    }

    // Storefront Blade defines storefrontCart later in <body>; retry briefly until it exists.
    let tries = 0;
    const timer = setInterval(() => {
        tries += 1;
        if (typeof window.storefrontCart === 'function') {
            clearInterval(timer);
            Alpine.start();
        } else if (tries > 100) {
            clearInterval(timer);
            Alpine.start();
        }
    }, 20);
}

startAlpineWhenReady();

document.addEventListener('DOMContentLoaded', () => {
    initAdminProgress();
    initAdminFeedback();
});
