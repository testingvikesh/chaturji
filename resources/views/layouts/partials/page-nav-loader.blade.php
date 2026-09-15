{{-- Full-page Ganpati loader — centered in the main content area --}}
<div id="page-nav-loader"
     class="page-nav-loader"
     aria-live="polite"
     aria-busy="true"
     role="status"
     hidden>
    <div class="page-nav-loader-inner">
        <img src="{{ asset('images/brand/ganpati.png') }}"
             alt="Shree Ganpati"
             class="page-nav-loader-img">
        <p class="page-nav-loader-title">જય શ્રી ગણેશ</p>
        <p class="page-nav-loader-text">Loading…</p>
        <p class="page-nav-loader-hint">Please wait while the page opens</p>
    </div>
</div>

<style>
    .page-nav-loader {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        background: linear-gradient(to bottom, #ecfdf5 0%, #ffffff 45%, #fffbeb 100%);
    }
    .page-nav-loader.is-open {
        display: flex !important;
    }
    @media (min-width: 1024px) {
        /* Center in content area (right of 16rem sidebar) */
        .page-nav-loader {
            left: 16rem;
        }
    }
    .page-nav-loader-inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 1.5rem;
        text-align: center;
        transform: translateY(-4vh); /* optical vertical center */
    }
    .page-nav-loader-img {
        width: 7rem;
        height: 7rem;
        object-fit: contain;
        filter: drop-shadow(0 10px 15px rgba(0,0,0,0.12));
        animation: page-nav-loader-pulse 1.4s ease-in-out infinite;
    }
    @media (min-width: 640px) {
        .page-nav-loader-img {
            width: 8rem;
            height: 8rem;
        }
    }
    .page-nav-loader-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: #15803d;
    }
    .page-nav-loader-text {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #334155;
    }
    .page-nav-loader-hint {
        margin: 0;
        font-size: 0.75rem;
        color: #64748b;
    }
    @keyframes page-nav-loader-pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.85; transform: scale(1.04); }
    }
</style>

<script>
(function () {
    var loader = document.getElementById('page-nav-loader');
    if (!loader) return;

    function showLoader() {
        loader.hidden = false;
        loader.classList.add('is-open');
        document.body.classList.add('overflow-hidden');
    }

    function hideLoader() {
        loader.classList.remove('is-open');
        loader.hidden = true;
        document.body.classList.remove('overflow-hidden');
    }

    function shouldShowForLink(a) {
        if (!a || a.target === '_blank' || a.hasAttribute('download')) return false;
        if (a.getAttribute('data-no-loader') !== null) return false;
        if (a.getAttribute('data-page-loader') !== null) return true;
        if (a.classList.contains('book-index-topic--link')) return true;

        var href = a.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return false;
        if (href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) return false;

        try {
            var url = new URL(href, window.location.origin);
            if (url.origin !== window.location.origin) return false;
            var path = url.pathname;
            return path.indexOf('/material-topics/') !== -1
                || /\/books\/\d+\/topics\//.test(path)
                || /\/self-practice\/.*\/material-topics\//.test(path);
        } catch (e) {
            return false;
        }
    }

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var a = e.target.closest('a[href]');
        if (!shouldShowForLink(a)) return;
        showLoader();
    }, true);

    window.addEventListener('pageshow', hideLoader);
    document.addEventListener('DOMContentLoaded', hideLoader);
})();
</script>
