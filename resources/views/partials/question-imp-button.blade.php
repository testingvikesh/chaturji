@props([
    'url' => null,
    'important' => false,
])

@if ($url)
    <button
        type="button"
        class="js-question-imp inline-flex h-9 shrink-0 items-center rounded-lg border px-2.5 text-xs font-bold transition"
        data-imp-url="{{ $url }}"
        data-important="{{ $important ? '1' : '0' }}"
        aria-pressed="{{ $important ? 'true' : 'false' }}"
        title="{{ $important ? 'Important' : 'Mark important' }}"
    >IMP</button>
@endif

@once
    <style>
        .js-question-imp {
            border-color: #c4a030;
            background: #ffffff;
            color: #8a6a12;
            box-shadow: inset 0 0 0 1px #f3e2a4;
        }
        .js-question-imp:hover {
            background: #fff6d8;
            color: #6d5210;
        }
        .js-question-imp[data-important="1"] {
            background: #d4af37;
            border-color: #a68520;
            color: #1a367c;
            box-shadow: 0 1px 3px rgba(166, 133, 32, 0.45);
        }
        .js-question-imp[data-important="1"]:hover {
            background: #c49e2a;
            color: #1a367c;
        }
        .js-imp-message {
            position: fixed;
            top: 1rem;
            left: 50%;
            z-index: 80;
            transform: translateX(-50%);
            max-width: calc(100% - 2rem);
            border-radius: 0.9rem;
            padding: 0.7rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.16);
        }
        .js-imp-message[data-kind="on"] {
            background: #fff6d8;
            border: 1px solid #d4af37;
            color: #6d5210;
        }
        .js-imp-message[data-kind="off"] {
            background: #f0f3fa;
            border: 1px solid #a8b8dc;
            color: #1a367c;
        }
        .js-imp-message[data-kind="error"] {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }
        .js-question-imp.is-imp-pulse {
            animation: imp-pop 0.28s ease;
        }
        @keyframes imp-pop {
            0% { transform: scale(0.92); }
            60% { transform: scale(1.08); }
            100% { transform: scale(1); }
        }
    </style>
    <script>
        function paintQuestionImp(button, on) {
            button.setAttribute('data-important', on ? '1' : '0');
            button.setAttribute('aria-pressed', on ? 'true' : 'false');
            button.title = on ? 'Important' : 'Mark important';
            button.classList.remove('is-imp-pulse');
            void button.offsetWidth;
            button.classList.add('is-imp-pulse');
        }
        function showQuestionImpMessage(kind, text) {
            let note = document.querySelector('.js-imp-message');
            if (!note) {
                note = document.createElement('div');
                note.className = 'js-imp-message';
                note.setAttribute('role', 'status');
                document.body.appendChild(note);
            }
            note.setAttribute('data-kind', kind);
            note.textContent = text;
            clearTimeout(showQuestionImpMessage.timer);
            showQuestionImpMessage.timer = setTimeout(function () {
                if (note.parentNode) {
                    note.parentNode.removeChild(note);
                }
            }, 2200);
        }
        document.addEventListener('click', function (event) {
            const button = event.target.closest('.js-question-imp');
            if (!button || button.dataset.loading === '1') {
                return;
            }
            const url = button.getAttribute('data-imp-url') || '';
            if (!url) {
                return;
            }
            const wasOn = button.getAttribute('data-important') === '1';
            const next = !wasOn;
            paintQuestionImp(button, next);
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
                paintQuestionImp(button, on);
                showQuestionImpMessage(on ? 'on' : 'off', on ? 'Question marked important.' : 'Important mark removed.');
            }).catch(function () {
                paintQuestionImp(button, wasOn);
                showQuestionImpMessage('error', 'Could not update this question.');
            }).finally(function () {
                button.dataset.loading = '0';
            });
        });
    </script>
@endonce
