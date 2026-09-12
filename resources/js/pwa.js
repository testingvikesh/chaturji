function isIosDevice() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}

function isStandaloneMode() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true
        || document.referrer.startsWith('android-app://');
}

export function initPwa() {
    if ('serviceWorker' in navigator) {
        const swUrl = document.querySelector('meta[name="pwa-sw"]')?.content;
        if (swUrl) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register(swUrl).catch(() => {});
            });
        }
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        window.__pwaDeferredPrompt = event;
        window.dispatchEvent(new Event('pwa:available'));
    });

    window.addEventListener('appinstalled', () => {
        window.__pwaDeferredPrompt = null;
        window.dispatchEvent(new Event('pwa:installed'));
    });
}

export function registerPwaInstall(Alpine) {
    Alpine.data('pwaInstall', () => ({
        status: 'checking',
        get canInstall() {
            return this.status === 'ready' || this.status === 'dismissed';
        },
        init() {
            if (isStandaloneMode()) {
                this.status = 'installed';
                return;
            }

            if (isIosDevice()) {
                this.status = 'ios';
                return;
            }

            if (window.__pwaDeferredPrompt) {
                this.status = 'ready';
            }

            window.addEventListener('pwa:available', () => {
                this.status = 'ready';
            });

            window.addEventListener('pwa:installed', () => {
                this.status = 'installed';
            });

            setTimeout(() => {
                if (this.status === 'checking') {
                    this.status = 'manual';
                }
            }, 2500);
        },
        async install() {
            const promptEvent = window.__pwaDeferredPrompt;

            if (!promptEvent) {
                this.status = 'manual';
                return;
            }

            this.status = 'installing';
            promptEvent.prompt();

            const { outcome } = await promptEvent.userChoice;
            window.__pwaDeferredPrompt = null;

            this.status = outcome === 'accepted' ? 'installed' : 'dismissed';
        },
    }));
}
