{{-- Full-page Ganpati loader shown while navigating to a topic/material page --}}
<div id="page-nav-loader"
     class="fixed inset-0 z-[9999] hidden items-center justify-center bg-gradient-to-b from-brand-green-50 via-white to-amber-50"
     aria-live="polite"
     aria-busy="true"
     role="status">
    <div class="flex flex-col items-center gap-3 px-6 text-center">
        <img src="{{ asset('images/brand/ganpati.png') }}"
             alt="Shree Ganpati"
             class="h-24 w-24 sm:h-28 sm:w-28 object-contain drop-shadow-lg animate-pulse">
        <p class="text-base font-bold text-brand-green">જય શ્રી ગણેશ</p>
        <p class="text-sm font-semibold text-slate-700">Loading…</p>
        <p class="text-xs text-slate-500">Please wait while the page opens</p>
    </div>
</div>

<script>
(function () {
    var loader = document.getElementById('page-nav-loader');
    if (!loader) return;

    function showLoader() {
        loader.classList.remove('hidden');
        loader.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function hideLoader() {
        loader.classList.add('hidden');
        loader.classList.remove('flex');
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
    window.addEventListener('pagehide', function () { /* keep visible during unload */ });
    document.addEventListener('DOMContentLoaded', hideLoader);
})();
</script>
