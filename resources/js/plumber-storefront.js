function initPlumberHeader() {
    const header = document.querySelector('[data-plumber-header]');
    if (! header) {
        return;
    }

    const overlay = header.dataset.plumberHeaderOverlay === '1';
    if (! overlay) {
        return;
    }

    const onScroll = () => {
        if (window.scrollY > 48) {
            header.classList.remove('plumber-header--hero');
            header.classList.add('plumber-header');
            header.style.position = 'sticky';
        } else {
            header.classList.add('plumber-header--hero');
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
}

function initBranchMaps() {
    const maps = document.querySelectorAll('[data-cafe-branch-map]');
    if (maps.length === 0) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries, obs) => {
            entries.forEach((entry) => {
                if (! entry.isIntersecting) {
                    return;
                }
                obs.unobserve(entry.target);
                import('./cafe-branch-map.js').then((module) => {
                    module.mountBranchMap(entry.target);
                });
            });
        },
        { rootMargin: '160px 0px' },
    );

    maps.forEach((el) => observer.observe(el));
}

document.addEventListener('DOMContentLoaded', () => {
    initPlumberHeader();
    initBranchMaps();
    document.body.classList.remove('is-loading');
});
