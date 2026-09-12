@php
    $siteName = $settings['site_name'] ?? config('app.name', 'Gses Chaturji');
    $tagline = $settings['site_tagline'] ?? 'Gujarat School of Excellence System';
@endphp

<x-auth-direct-layout title="Install App">
    <div class="login-direct pwa-install-page" x-data="pwaInstall">
        <aside class="login-direct-side">
            <div class="login-direct-side-glow" aria-hidden="true"></div>
            <div class="login-direct-side-pattern" aria-hidden="true"></div>

            <div class="login-direct-side-top">
                <img src="{{ asset('images/brand/logo.png') }}" alt="{{ $siteName }}" class="login-direct-logo">
                <div>
                    <p class="login-direct-brand">{{ $siteName }}</p>
                    <p class="login-direct-tagline">{{ $tagline }}</p>
                </div>
            </div>

            <div class="login-direct-slider">
                <h2 class="login-direct-slide-title">Install on your device</h2>
                <p class="login-direct-slide-text">Open the app from your home screen — faster access to homework, exams, and progress.</p>

                <ul class="pwa-install-benefits">
                    <li>One-tap open from the home screen</li>
                    <li>Full-screen app experience</li>
                    <li>Works on phone, tablet, and desktop</li>
                </ul>
            </div>

            <p class="login-direct-motto">विद्या ददाति विनयम्</p>
        </aside>

        <section class="login-direct-form-wrap">
            <div class="login-direct-form-card">
                <div class="flex items-center gap-3 mb-6">
                    <img src="{{ asset('images/pwa/icon-192.png') }}" alt="{{ $siteName }}" class="h-14 w-14 rounded-2xl object-contain shadow-md">
                    <div>
                        <p class="font-bold text-brand-green text-lg leading-tight">{{ $siteName }}</p>
                        <p class="text-xs text-slate-500">{{ $tagline }}</p>
                    </div>
                </div>

                <span class="section-badge">PWA App</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Install the app</h1>
                <p class="text-sm text-slate-500 mt-2 mb-6">Add {{ $siteName }} to your home screen and open it like a native app.</p>

                <div x-show="status === 'checking'" x-cloak class="pwa-install-status">
                    <p>Checking if this device can install the app…</p>
                </div>

                <div x-show="status === 'ready' || status === 'dismissed' || status === 'installing'" x-cloak>
                    <p x-show="status === 'dismissed'" class="mb-4 rounded-xl bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-800">
                        Install was cancelled. Tap the button again when you are ready.
                    </p>
                    <button type="button"
                            class="auth-submit login-direct-submit disabled:opacity-70 disabled:pointer-events-none"
                            @click="install()"
                            :disabled="status === 'installing'">
                        <span x-text="status === 'installing' ? 'Opening install…' : 'Install App'"></span>
                    </button>
                </div>

                <div x-show="status === 'installed'" x-cloak class="pwa-install-status pwa-install-status-success">
                    <p class="font-semibold text-brand-green">App is installed</p>
                    <p class="mt-1">Open it from your home screen or app list. You can close this page.</p>
                    <a href="{{ route('home') }}" class="auth-submit login-direct-submit mt-5 inline-flex justify-center">Continue to login</a>
                </div>

                <div x-show="status === 'ios'" x-cloak class="pwa-install-guide">
                    <p class="font-semibold text-slate-900 mb-3">Install on iPhone / iPad</p>
                    <ol>
                        <li>Tap the <strong>Share</strong> button in Safari.</li>
                        <li>Scroll and tap <strong>Add to Home Screen</strong>.</li>
                        <li>Tap <strong>Add</strong> to install {{ $siteName }}.</li>
                    </ol>
                </div>

                <div x-show="status === 'manual'" x-cloak class="pwa-install-guide">
                    <p class="font-semibold text-slate-900 mb-3">Install from your browser</p>
                    <ol>
                        <li>Open this site in <strong>Chrome</strong> or <strong>Edge</strong>.</li>
                        <li>Tap the browser menu (⋮).</li>
                        <li>Choose <strong>Install app</strong> or <strong>Add to Home screen</strong>.</li>
                    </ol>
                    <p class="mt-3 text-xs text-slate-500">Install works on localhost or HTTPS. If the button does not appear, use the browser menu.</p>
                    <button type="button" class="auth-submit login-direct-submit mt-5" @click="install()">
                        Try Install App
                    </button>
                </div>

                <div class="auth-divider"><span>or</span></div>

                <p class="text-center text-sm text-slate-500">
                    <a href="{{ route('home') }}" class="auth-link">Back to login</a>
                </p>
            </div>
        </section>
    </div>
</x-auth-direct-layout>
