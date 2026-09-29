import lottie from 'lottie-web/build/player/lottie_light';

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function mount(el) {
    if (el._bbLottie) return el._bbLottie;
    const hoverOnly = el.hasAttribute('data-lottie-hover');
    const anim = lottie.loadAnimation({
        container: el,
        renderer: 'svg',
        loop: el.dataset.lottieLoop !== 'false',
        autoplay: false,
        path: el.dataset.lottie,
        rendererSettings: { preserveAspectRatio: 'xMidYMid meet', progressiveLoad: true },
    });
    anim.addEventListener('DOMLoaded', () => {
        el.classList.add('is-lottie-ready');
        if (reduceMotion || hoverOnly) {
            anim.goToAndStop(0, true);
        } else if (el._bbVisible) {
            anim.play();
        }
    });
    if (hoverOnly && !reduceMotion) {
        const host = el.closest('a, button, .bb-lottie-host') || el;
        host.addEventListener('mouseenter', () => anim.play());
        host.addEventListener('mouseleave', () => anim.stop());
    }
    el._bbLottie = anim;
    return anim;
}

const observer = 'IntersectionObserver' in window
    ? new IntersectionObserver((entries) => {
        entries.forEach(({ target, isIntersecting }) => {
            target._bbVisible = isIntersecting;
            const anim = isIntersecting ? mount(target) : target._bbLottie;
            if (!anim || reduceMotion || target.hasAttribute('data-lottie-hover')) return;
            if (isIntersecting && target.classList.contains('is-lottie-ready')) anim.play();
            if (!isIntersecting) anim.pause();
        });
    }, { rootMargin: '120px' })
    : null;

function bind(el) {
    if (el.hasAttribute('data-lottie-bound')) return;
    el.setAttribute('data-lottie-bound', '');
    if (observer) observer.observe(el);
    else { el._bbVisible = true; mount(el); }
}

function scan(root = document) {
    if (root.nodeType === 1 && root.hasAttribute('data-lottie')) bind(root);
    root.querySelectorAll('[data-lottie]').forEach(bind);
}

scan();
document.addEventListener('DOMContentLoaded', () => scan());
new MutationObserver((mutations) => {
    for (const m of mutations) {
        for (const node of m.addedNodes) {
            if (node.nodeType === 1) scan(node);
        }
    }
}).observe(document.documentElement, { childList: true, subtree: true });
