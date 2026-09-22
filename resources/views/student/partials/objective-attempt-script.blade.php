@once
<style>
    .js-objective-option:not(:disabled):hover {
        border-color: #fbbf24 !important;
        background-color: #fffbeb !important;
    }
    .js-objective-option:not(:disabled):hover .js-objective-letter {
        background-color: #f59e0b !important;
        border-color: #f59e0b !important;
        color: #ffffff !important;
    }
    .js-objective-option.is-objective-selected {
        border-color: #fbbf24 !important;
        border-width: 2px !important;
        background-color: #fffbeb !important;
    }
    .js-objective-option.is-objective-selected .js-objective-letter {
        background-color: #f59e0b !important;
        border-color: #f59e0b !important;
        color: #ffffff !important;
    }
</style>
<script>
(function () {
    function normalize(text) {
        return String(text ?? '')
            .toLowerCase()
            .normalize('NFKC')
            .replace(/\s+/g, '')
            .replace(/[^\p{L}\p{N}]/gu, '');
    }

    function answersMatch(studentAnswer, correctAnswer) {
        const student = normalize(studentAnswer);
        const correct = normalize(correctAnswer);

        if (student === '' || correct === '') {
            return false;
        }

        if (student === correct) {
            return true;
        }

        const studentLength = [...student].length;
        const correctLength = [...correct].length;
        const lengthRatio = Math.min(studentLength, correctLength) / Math.max(studentLength, correctLength);

        if (lengthRatio < 0.65) {
            return false;
        }

        return lengthRatio >= 0.85 && (student.includes(correct) || correct.includes(student));
    }

    function optionMatches(key, text, correctAnswer) {
        const label = key.length === 1 ? `${key.toUpperCase()}. ${text}` : text;

        return answersMatch(key, correctAnswer)
            || answersMatch(label, correctAnswer)
            || answersMatch(text, correctAnswer);
    }

    const scoreBoard = document.getElementById('objective-paper-score');
    const submitBar = document.getElementById('objective-submit-bar');
    const submitBtn = document.getElementById('objective-submit-btn');
    const statusHint = document.getElementById('objective-status-hint');
    const successModal = document.getElementById('objective-success-modal');
    const answeredEl = document.querySelector('[data-score-answered]');
    let submitted = false;

    function showSuccessModal(earned, correct) {
        if (!successModal) {
            return;
        }

        const earnedEl = successModal.querySelector('[data-success-earned]');
        const correctEl = successModal.querySelector('[data-success-correct]');

        if (earnedEl) {
            earnedEl.textContent = String(earned);
        }
        if (correctEl) {
            correctEl.textContent = String(correct);
        }

        successModal.classList.remove('hidden');
        successModal.classList.add('flex');
        successModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    }

    function hideSuccessModal() {
        if (!successModal) {
            return;
        }

        successModal.classList.add('hidden');
        successModal.classList.remove('flex');
        successModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        scoreBoard?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function allCards() {
        return Array.from(document.querySelectorAll('[data-objective-question]'));
    }

    function updateAnsweredCount() {
        const cards = allCards();
        let answered = 0;

        cards.forEach((card) => {
            if (card.dataset.selectedKey || String(card.dataset.selectedText || '').trim() !== '') {
                answered += 1;
            }
        });

        if (answeredEl) {
            answeredEl.textContent = String(answered);
        }

        if (submitBtn && !submitted) {
            submitBtn.disabled = answered === 0;
            submitBtn.classList.toggle('opacity-60', answered === 0);
            submitBtn.classList.toggle('cursor-not-allowed', answered === 0);
        }
    }

    function updateScore(earned, correct) {
        if (!scoreBoard) {
            return;
        }

        const earnedEl = scoreBoard.querySelector('[data-score-earned]');
        const correctEl = scoreBoard.querySelector('[data-score-correct]');

        if (earnedEl) {
            earnedEl.textContent = String(earned);
        }

        if (correctEl) {
            correctEl.textContent = String(correct);
        }
    }

    function resetOptionStyles(option) {
        option.disabled = false;
        option.className = 'js-objective-option group flex w-full items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-left text-sm transition hover:border-amber-400 hover:bg-amber-50 cursor-pointer';

        const letter = option.querySelector('.js-objective-letter');
        if (letter) {
            letter.className = 'js-objective-letter flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-amber-300 bg-white text-xs font-bold text-amber-700 group-hover:bg-amber-500 group-hover:text-white group-hover:border-amber-500';
        }
    }

    function styleOption(option, state) {
        const letter = option.querySelector('.js-objective-letter');

        if (state === 'selected') {
            option.className = 'js-objective-option is-objective-selected group flex w-full items-center gap-2 rounded-lg border-2 border-amber-400 bg-amber-50 px-2 py-1.5 text-left text-sm transition cursor-pointer';
            if (letter) {
                letter.className = 'js-objective-letter flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-amber-500 bg-amber-500 text-xs font-bold text-white';
            }
            return;
        }

        option.classList.remove('is-objective-selected');
        option.disabled = true;
        option.classList.remove('hover:border-amber-400', 'hover:bg-amber-50', 'cursor-pointer');

        if (state === 'selected-correct') {
            option.className = 'js-objective-option group flex w-full items-center gap-2 rounded-lg border-2 border-brand-green bg-brand-green-50 px-2 py-1.5 text-left text-sm transition disabled:cursor-default';
            if (letter) {
                letter.className = 'js-objective-letter flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-brand-green bg-brand-green text-xs font-bold text-white';
            }
            return;
        }

        if (state === 'selected-wrong') {
            option.className = 'js-objective-option group flex w-full items-center gap-2 rounded-lg border-2 border-red-400 bg-red-50 px-2 py-1.5 text-left text-sm transition disabled:cursor-default';
            if (letter) {
                letter.className = 'js-objective-letter flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-red-500 bg-red-500 text-xs font-bold text-white';
            }
            return;
        }

        if (state === 'correct-reveal') {
            option.className = 'js-objective-option group flex w-full items-center gap-2 rounded-lg border-2 border-brand-green bg-brand-green-50/70 px-2 py-1.5 text-left text-sm transition disabled:cursor-default';
            if (letter) {
                letter.className = 'js-objective-letter flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-brand-green bg-brand-green text-xs font-bold text-white';
            }
            return;
        }

        option.className = 'js-objective-option group flex w-full items-center gap-2 rounded-lg border border-slate-100 bg-slate-50 px-2 py-1.5 text-left text-sm opacity-70 transition disabled:cursor-default';
        if (letter) {
            letter.className = 'js-objective-letter flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-xs font-bold text-slate-400';
        }
    }

    function showResult(card, isCorrect, marks) {
        const result = card.querySelector('[data-objective-result]');
        if (!result) {
            return;
        }

        const title = result.querySelector('.js-objective-result-title');
        const marksLine = result.querySelector('.js-objective-result-marks');
        const answerLine = result.querySelector('.js-objective-result-answer');

        result.classList.remove('hidden', 'border-brand-green-200', 'bg-brand-green-50', 'text-brand-green-dark', 'border-red-200', 'bg-red-50', 'text-red-700', 'border');

        if (isCorrect) {
            result.classList.add('border', 'border-brand-green-200', 'bg-brand-green-50', 'text-brand-green-dark');
            if (title) {
                title.textContent = '✓ Correct';
            }
            if (marksLine) {
                marksLine.textContent = `+${marks} mark${marks > 1 ? 's' : ''} added`;
            }
            if (answerLine) {
                answerLine.classList.add('hidden');
            }
        } else {
            result.classList.add('border', 'border-red-200', 'bg-red-50', 'text-red-700');
            if (title) {
                title.textContent = '✗ Incorrect';
            }
            if (marksLine) {
                marksLine.textContent = '0 marks';
            }
            if (answerLine) {
                answerLine.classList.remove('hidden');
            }
        }
    }

    function selectChoice(card, key, text) {
        if (submitted || card.dataset.checked === '1') {
            return;
        }

        card.dataset.selectedKey = key;
        card.dataset.selectedText = text;

        card.querySelectorAll('[data-objective-option]').forEach((option) => {
            const isSelected = (option.dataset.optionKey || '') === key;
            if (isSelected) {
                styleOption(option, 'selected');
            } else {
                resetOptionStyles(option);
            }
        });

        updateAnsweredCount();
    }

    function saveText(card, value) {
        if (submitted || card.dataset.checked === '1') {
            return;
        }

        card.dataset.selectedKey = '';
        card.dataset.selectedText = String(value || '').trim();
        updateAnsweredCount();
    }

    function gradeCard(card) {
        const correctAnswer = card.dataset.correctAnswer || '';
        const marks = Number(card.dataset.marks || 1);
        const selectedKey = card.dataset.selectedKey || '';
        const selectedText = card.dataset.selectedText || '';
        const options = card.querySelectorAll('[data-objective-option]');
        const hasOptions = options.length > 0;

        let isCorrect = false;

        if (hasOptions) {
            if (selectedKey !== '' || selectedText !== '') {
                isCorrect = optionMatches(selectedKey, selectedText, correctAnswer);
            }

            options.forEach((option) => {
                const optionKey = option.dataset.optionKey || '';
                const optionText = option.dataset.optionText || '';
                const isSelected = optionKey === selectedKey;
                const isCorrectOption = optionMatches(optionKey, optionText, correctAnswer);

                if (selectedKey === '' && selectedText === '') {
                    styleOption(option, isCorrectOption ? 'correct-reveal' : 'dimmed');
                } else if (isSelected && isCorrect) {
                    styleOption(option, 'selected-correct');
                } else if (isSelected && !isCorrect) {
                    styleOption(option, 'selected-wrong');
                } else if (!isCorrect && isCorrectOption) {
                    styleOption(option, 'correct-reveal');
                } else {
                    styleOption(option, 'dimmed');
                }
            });
        } else {
            isCorrect = selectedText !== '' && answersMatch(selectedText, correctAnswer);

            const input = card.querySelector('[data-objective-text]');
            if (input) {
                input.disabled = true;
                input.classList.add(isCorrect ? 'border-brand-green' : 'border-red-400');
            }
        }

        card.dataset.checked = '1';
        showResult(card, isCorrect && (selectedKey !== '' || selectedText !== ''), marks);

        return {
            isCorrect: isCorrect && (selectedKey !== '' || selectedText !== ''),
            marks,
        };
    }

    function submitAll() {
        if (submitted) {
            return;
        }

        const cards = allCards();
        let answered = 0;
        cards.forEach((card) => {
            if (card.dataset.selectedKey || String(card.dataset.selectedText || '').trim() !== '') {
                answered += 1;
            }
        });

        if (answered === 0) {
            return;
        }

        if (!window.confirm('Submit answers? You will see results after submit.')) {
            return;
        }

        submitted = true;
        let earned = 0;
        let correct = 0;

        cards.forEach((card) => {
            const result = gradeCard(card);
            if (result.isCorrect) {
                correct += 1;
                earned += result.marks;
            }
        });

        updateScore(earned, correct);

        if (statusHint) {
            statusHint.textContent = 'Submitted — results shown below';
            statusHint.className = 'rounded-full bg-brand-green px-3 py-1.5 text-xs font-semibold text-white';
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitted';
            submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
        }

        saveWorkAttempt(answered, correct, earned);
        showSuccessModal(earned, correct);
    }

    function saveWorkAttempt(answered, correct, earned) {
        if (!submitBar) {
            return;
        }
        const url = submitBar.dataset.workStoreUrl || '';
        const paperType = submitBar.dataset.workPaperType || '';
        const paperId = submitBar.dataset.workPaperId || '';
        if (!url || !paperType || !paperId) {
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const maxMarks = Number(submitBar.dataset.workMaxMarks || 0);
        const totalObjective = Number(submitBar.dataset.workTotalObjective || 0);

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                paper_type: paperType,
                paper_id: Number(paperId),
                answered_count: answered,
                correct_count: correct,
                total_objective: totalObjective,
                earned_marks: earned,
                max_marks: maxMarks,
            }),
        }).catch(function () {
            // Keep UI success even if logging fails.
        });
    }

    document.addEventListener('click', function (event) {
        const option = event.target.closest('[data-objective-option]');
        if (option) {
            const card = option.closest('[data-objective-question]');
            if (!card) {
                return;
            }

            selectChoice(card, option.dataset.optionKey || '', option.dataset.optionText || '');
            return;
        }

        const submit = event.target.closest('#objective-submit-btn');
        if (submit) {
            submitAll();
            return;
        }

        if (event.target.closest('[data-objective-success-close]')) {
            hideSuccessModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && successModal && !successModal.classList.contains('hidden')) {
            hideSuccessModal();
        }
    });

    document.addEventListener('input', function (event) {
        if (!event.target.matches('[data-objective-text]')) {
            return;
        }

        const card = event.target.closest('[data-objective-question]');
        if (!card) {
            return;
        }

        saveText(card, event.target.value);
    });

    updateAnsweredCount();
})();
</script>
@endonce
