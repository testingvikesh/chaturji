function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function animateCounter(element) {
    if (element.dataset.counted === 'true') {
        return;
    }

    element.dataset.counted = 'true';

    const raw = element.textContent.trim();
    const match = raw.match(/^([\d,.]+)(.*)$/);

    if (!match) {
        return;
    }

    const target = parseFloat(match[1].replace(/,/g, ''));
    const suffix = match[2] ?? '';
    const duration = 1400;
    const start = performance.now();

    const tick = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const value = Math.round(target * eased);
        element.textContent = `${value.toLocaleString()}${suffix}`;

        if (progress < 1) {
            requestAnimationFrame(tick);
        }
    };

    element.textContent = `0${suffix}`;
    requestAnimationFrame(tick);
}

function revealElement(element) {
    element.classList.add('is-visible');

    if (element.dataset.counter !== undefined) {
        animateCounter(element);
    }

    element.querySelectorAll('[data-counter]').forEach((counter, index) => {
        setTimeout(() => animateCounter(counter), 350 + index * 120);
    });
}

export function initAnimations() {
    if (prefersReducedMotion()) {
        document.querySelectorAll('[data-animate], .process-connector, [data-counter]').forEach((element) => {
            element.classList.add('is-visible');
        });
        document.body.classList.add('page-loaded');
        return;
    }

    document.body.classList.add('page-loaded');

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                revealElement(entry.target);
                observer.unobserve(entry.target);
            });
        },
        {
            threshold: 0.12,
            rootMargin: '0px 0px -50px 0px',
        }
    );

    document.querySelectorAll('[data-animate]').forEach((element) => {
        const delay = element.dataset.delay;

        if (delay) {
            element.style.transitionDelay = `${delay}ms`;
        }

        if (element.dataset.animateImmediate !== undefined) {
            requestAnimationFrame(() => revealElement(element));
            return;
        }

        observer.observe(element);
    });

    document.querySelectorAll('.process-connector').forEach((connector) => {
        observer.observe(connector);
    });

    document.querySelectorAll('[data-animate-stagger]').forEach((parent) => {
        const step = parseInt(parent.dataset.animateStagger || '100', 10);

        parent.querySelectorAll(':scope > [data-animate-item]').forEach((item, index) => {
            item.setAttribute('data-animate', item.dataset.animateItem || 'fade-up');
            item.style.transitionDelay = `${index * step}ms`;
            observer.observe(item);
        });
    });
}
