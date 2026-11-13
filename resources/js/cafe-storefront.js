import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';

gsap.registerPlugin(ScrollTrigger);

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function initLenis() {
    if (prefersReducedMotion() || window.matchMedia('(max-width: 767px)').matches) {
        return null;
    }

    const lenis = new Lenis({
        duration: 1.1,
        smoothWheel: true,
        touchMultiplier: 1.2,
    });

    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => lenis.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);

    return lenis;
}

function syncCafeHeaderOffset() {
    const header = document.querySelector('[data-cafe-header]');
    if (! header) {
        return;
    }

    const demo = document.querySelector('.cafe-demo-banner');
    let height = header.offsetHeight;
    if (demo) {
        const rect = demo.getBoundingClientRect();
        if (rect.bottom > 0) {
            height += demo.offsetHeight;
        }
    }

    document.documentElement.style.setProperty('--cafe-header-height', `${height}px`);
}

function initHeader() {
    const header = document.querySelector('[data-cafe-header]');
    if (! header) {
        return;
    }

    const overlay = header.dataset.cafeHeaderOverlay === '1';

    const sync = () => {
        syncCafeHeaderOffset();

        if (! overlay) {
            header.classList.add('cafe-header--solid');
            header.classList.remove('cafe-header--hero');

            return;
        }

        const solid = window.scrollY > 32;
        header.classList.toggle('cafe-header--solid', solid);
        header.classList.toggle('cafe-header--hero', ! solid);
    };

    sync();
    window.addEventListener('scroll', sync, { passive: true });
    window.addEventListener('resize', sync, { passive: true });
}

function initHeroSplit() {
    if (prefersReducedMotion()) {
        document.querySelectorAll('.cafe-split-word > span').forEach((el) => {
            el.style.transform = 'none';
        });

        return;
    }

    const headline = document.querySelector('[data-hero-headline]');
    if (! headline) {
        return;
    }

    const text = headline.textContent.trim();
    const words = text.split(/\s+/);
    headline.textContent = '';
    const spans = [];
    words.forEach((word, i) => {
        const wrap = document.createElement('span');
        wrap.className = 'cafe-split-word';
        wrap.setAttribute('aria-hidden', i > 0 ? 'false' : 'false');
        const inner = document.createElement('span');
        inner.textContent = word + (i < words.length - 1 ? '\u00a0' : '');
        wrap.appendChild(inner);
        headline.appendChild(wrap);
        spans.push(inner);
    });

    gsap.to(spans, {
        y: 0,
        duration: 1,
        ease: 'power3.out',
        stagger: 0.06,
        delay: 0.2,
    });
}

function initParallax() {
    if (prefersReducedMotion()) {
        return;
    }

    document.querySelectorAll('[data-parallax]').forEach((el) => {
        const speed = parseFloat(el.dataset.parallaxSpeed || '0.2');
        gsap.to(el, {
            y: () => speed * 120,
            ease: 'none',
            scrollTrigger: {
                trigger: el.closest('[data-parallax-root]') || el,
                start: 'top bottom',
                end: 'bottom top',
                scrub: true,
            },
        });
    });
}

function initReveals() {
    if (prefersReducedMotion()) {
        document.querySelectorAll('.cafe-reveal, .cafe-clip-reveal').forEach((el) => {
            el.style.opacity = '1';
            el.style.transform = 'none';
            el.style.clipPath = 'none';
        });

        return;
    }

    gsap.utils.toArray('[data-reveal]').forEach((el) => {
        gsap.fromTo(
            el,
            { opacity: 0, y: 48 },
            {
                opacity: 1,
                y: 0,
                duration: 0.9,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 88%',
                },
            },
        );
    });

    gsap.utils.toArray('[data-reveal-stagger]').forEach((group) => {
        const children = group.querySelectorAll('[data-reveal-child]');
        gsap.fromTo(
            children,
            { opacity: 0, y: 36 },
            {
                opacity: 1,
                y: 0,
                duration: 0.75,
                stagger: 0.12,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: group,
                    start: 'top 85%',
                },
            },
        );
    });

    gsap.utils.toArray('[data-clip-reveal]').forEach((el) => {
        gsap.fromTo(
            el,
            { clipPath: 'inset(100% 0 0 0)' },
            {
                clipPath: 'inset(0% 0 0 0)',
                duration: 1.1,
                ease: 'power3.inOut',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 82%',
                },
            },
        );
    });
}

function initCounters() {
    document.querySelectorAll('[data-counter]').forEach((el) => {
        const target = el.dataset.counter;
        if (! target || prefersReducedMotion()) {
            return;
        }

        const numeric = target.replace(/[^\d]/g, '');
        if (numeric === '') {
            return;
        }

        const suffix = target.replace(numeric, '');
        const obj = { val: 0 };
        ScrollTrigger.create({
            trigger: el,
            start: 'top 88%',
            once: true,
            onEnter: () => {
                gsap.to(obj, {
                    val: parseInt(numeric, 10),
                    duration: 1.6,
                    ease: 'power2.out',
                    onUpdate: () => {
                        el.textContent = Math.round(obj.val) + suffix;
                    },
                });
            },
        });
    });
}

function initCursorGlow() {
    const glow = document.querySelector('[data-cursor-glow]');
    if (! glow || ! window.matchMedia('(hover: hover)').matches) {
        return;
    }

    let raf = null;
    window.addEventListener(
        'pointermove',
        (e) => {
            if (raf) {
                cancelAnimationFrame(raf);
            }
            raf = requestAnimationFrame(() => {
                glow.style.transform = `translate3d(${e.clientX}px, ${e.clientY}px, 0)`;
                glow.classList.add('is-on');
            });
        },
        { passive: true },
    );
}

function initMenuPage() {
    const root = document.querySelector('[data-cafe-menu]');
    if (! root) {
        return;
    }

    const chips = root.querySelectorAll('[data-menu-chip]');
    const sections = root.querySelectorAll('[data-menu-section]');

    const setActive = (slug) => {
        chips.forEach((chip) => {
            chip.classList.toggle('cafe-chip--active', chip.dataset.menuChip === slug);
        });
    };

    const chipsBar = root.querySelector('.cafe-menu-sticky-chips');

    const menuScrollOffset = () => {
        const headerPx = parseFloat(
            getComputedStyle(document.documentElement).getPropertyValue('--cafe-header-height'),
        ) || 64;
        const chipsPx = chipsBar?.offsetHeight ?? 52;

        return headerPx + chipsPx + 12;
    };

    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            const id = chip.dataset.menuChip;
            const el = document.getElementById(`menu-${id}`);
            if (el) {
                const top = el.getBoundingClientRect().top + window.scrollY - menuScrollOffset();
                window.scrollTo({
                    top: Math.max(0, top),
                    behavior: prefersReducedMotion() ? 'auto' : 'smooth',
                });
            }
            setActive(id);
        });
    });

    if (! prefersReducedMotion() && sections.length) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        setActive(entry.target.dataset.menuSection);
                    }
                });
            },
            { rootMargin: '-40% 0px -50% 0px' },
        );
        sections.forEach((s) => observer.observe(s));
    }
}

function initLightbox() {
    const root = document.querySelector('[data-cafe-gallery]');
    if (! root) {
        return;
    }

    const dialog = root.querySelector('[data-lightbox]');
    const img = root.querySelector('[data-lightbox-img]');
    if (! dialog || ! img) {
        return;
    }

    root.querySelectorAll('[data-gallery-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            img.src = btn.dataset.galleryOpen;
            img.alt = btn.dataset.galleryAlt || '';
            dialog.showModal();
        });
    });

    dialog.querySelector('[data-lightbox-close]')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) {
            dialog.close();
        }
    });
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

function boot() {
    const body = document.body;
    if (! body.hasAttribute('data-cafe-storefront')) {
        return;
    }

    initLenis();
    initHeader();
    initHeroSplit();
    initParallax();
    initReveals();
    initCounters();
    initCursorGlow();
    initMenuPage();
    initLightbox();
    initBranchMaps();

    body.classList.remove('is-loading');
    body.classList.add('is-ready');
}

document.addEventListener('DOMContentLoaded', boot);
