@props([
    'url' => null,
    'important' => false,
])

@if ($url)
    <button
        type="button"
        class="js-question-imp inline-flex h-9 shrink-0 items-center rounded-lg border px-2.5 text-xs font-bold transition {{ $important ? 'border-amber-400 bg-amber-400 text-white' : 'border-amber-300 bg-white text-amber-700 hover:bg-amber-50' }}"
        data-imp-url="{{ $url }}"
        data-important="{{ $important ? '1' : '0' }}"
        aria-pressed="{{ $important ? 'true' : 'false' }}"
        title="{{ $important ? 'Important' : 'Mark important' }}"
    >IMP</button>
@endif

@once
    <script>
        document.addEventListener('click', function (event) {
            const button = event.target.closest('.js-question-imp');
            if (!button || button.dataset.loading === '1') {
                return;
            }
            const url = button.getAttribute('data-imp-url') || '';
            if (!url) {
                return;
            }
            const next = button.getAttribute('data-important') !== '1';
            button.dataset.loading = '1';
            const token = document.querySelector('meta[name="csrf-token"]');
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ important: next })
            }).then(function (res) {
                if (!res.ok) {
                    throw new Error('save failed');
                }
                return res.json();
            }).then(function (data) {
                const on = !!(data && data.important);
                button.setAttribute('data-important', on ? '1' : '0');
                button.setAttribute('aria-pressed', on ? 'true' : 'false');
                button.title = on ? 'Important' : 'Mark important';
                button.className = 'js-question-imp inline-flex h-9 shrink-0 items-center rounded-lg border px-2.5 text-xs font-bold transition '
                    + (on ? 'border-amber-400 bg-amber-400 text-white' : 'border-amber-300 bg-white text-amber-700 hover:bg-amber-50');
            }).catch(function () {
            }).finally(function () {
                button.dataset.loading = '0';
            });
        });
    </script>
@endonce
