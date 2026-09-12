function normalizeForMatch(text) {
    return String(text ?? '')
        .toLowerCase()
        .normalize('NFKC')
        .replace(/\s+/g, '')
        .replace(/[^\p{L}\p{N}]/gu, '');
}

function answersMatch(studentAnswer, correctAnswer) {
    const student = normalizeForMatch(studentAnswer);
    const correct = normalizeForMatch(correctAnswer);

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

    if (lengthRatio >= 0.85 && (student.includes(correct) || correct.includes(student))) {
        return true;
    }

    return false;
}

function optionLabel(key, option) {
    if (typeof option === 'string' || typeof option === 'number') {
        if (typeof key === 'string' && key.length === 1) {
            return `${key.toUpperCase()}. ${option}`;
        }

        return String(option);
    }

    return JSON.stringify(option);
}

function optionValue(key, option) {
    if (typeof option === 'string' || typeof option === 'number') {
        if (typeof key === 'string' && key.length === 1) {
            return String(key).toUpperCase();
        }

        return String(option);
    }

    return JSON.stringify(option);
}

export function registerObjectiveAttempt(Alpine) {
    Alpine.data('objectivePaperScore', (config = {}) => ({
        earned: 0,
        correctCount: 0,
        answeredCount: 0,
        submitted: false,
        maxMarks: config.maxMarks ?? 0,
        totalObjective: config.totalObjective ?? 0,
        attempts: {},

        update(detail) {
            const number = detail.number;
            const isCorrect = !!detail.isCorrect;
            const marks = Number(detail.marks ?? 0);
            const previous = this.attempts[number];

            if (previous) {
                if (previous.isCorrect) {
                    this.correctCount -= 1;
                    this.earned -= previous.marks;
                }
            } else {
                this.answeredCount += 1;
            }

            this.attempts[number] = { isCorrect, marks };

            if (isCorrect) {
                this.correctCount += 1;
                this.earned += marks;
            }
        },

        submitAll() {
            this.submitted = true;
            this.$dispatch('objective-submit');
        },
    }));

    Alpine.data('objectiveQuestion', (config = {}) => ({
        selected: '',
        checked: false,
        submitted: false,
        isCorrect: false,
        textAnswer: '',
        number: config.number,
        marks: Number(config.marks ?? 1),
        correctAnswer: String(config.correctAnswer ?? ''),
        useTextInput: !!config.useTextInput,

        init() {
            this.$watch('textAnswer', (value) => {
                if (this.submitted) {
                    return;
                }
                this.selected = String(value ?? '').trim();
            });

            window.addEventListener('objective-submit', () => this.grade());
        },

        checkAnswer(value) {
            const answer = String(value ?? '').trim();
            this.selected = answer;
            this.isCorrect = answer !== '' && answersMatch(answer, this.correctAnswer);
            this.checked = true;
            this.submitted = true;

            this.$dispatch('objective-attempt', {
                number: this.number,
                isCorrect: this.isCorrect,
                marks: this.marks,
            });
        },

        selectOption(key, option) {
            if (this.submitted) {
                return;
            }

            const value = optionValue(key, option);
            this.selected = value;
        },

        grade() {
            if (this.submitted) {
                return;
            }

            if (this.useTextInput) {
                this.checkAnswer(this.textAnswer);
                return;
            }

            const answer = String(this.selected ?? '').trim();
            this.checked = true;
            this.submitted = true;
            this.isCorrect = answer !== '' && answersMatch(answer, this.correctAnswer);

            this.$dispatch('objective-attempt', {
                number: this.number,
                isCorrect: this.isCorrect,
                marks: this.marks,
            });
        },

        submitText() {
            // Kept for compatibility; grading happens on final submit.
        },

        isOptionCorrect(key, option) {
            const value = optionValue(key, option);
            const label = optionLabel(key, option);
            const text = String(option ?? '');

            return answersMatch(value, this.correctAnswer)
                || answersMatch(label, this.correctAnswer)
                || answersMatch(text, this.correctAnswer);
        },

        optionButtonClass(key, option) {
            const value = optionValue(key, option);
            const text = String(option ?? '');
            const isCorrectOption = this.isOptionCorrect(key, option);
            const isSelected = this.selected === value || this.selected === text;

            if (!this.submitted) {
                if (isSelected) {
                    return 'border-amber-400 bg-amber-50 cursor-pointer';
                }

                return 'border-slate-200 bg-white hover:border-amber-400 hover:bg-amber-50 cursor-pointer';
            }

            if (isSelected && this.isCorrect) {
                return 'border-brand-green bg-brand-green-50';
            }

            if (isSelected && !this.isCorrect) {
                return 'border-red-400 bg-red-50';
            }

            if (!this.isCorrect && isCorrectOption) {
                return 'border-brand-green bg-brand-green-50/70';
            }

            return 'border-slate-100 bg-slate-50 opacity-70';
        },

        optionLetterClass(key, option) {
            const value = optionValue(key, option);
            const text = String(option ?? '');
            const isCorrectOption = this.isOptionCorrect(key, option);
            const isSelected = this.selected === value || this.selected === text;

            if (!this.submitted) {
                if (isSelected) {
                    return 'border-amber-500 bg-amber-500 text-white';
                }

                return 'border-amber-300 bg-white text-amber-700 group-hover:bg-amber-500 group-hover:text-white group-hover:border-amber-500';
            }

            if (isSelected && this.isCorrect) {
                return 'border-brand-green bg-brand-green text-white';
            }

            if (isSelected && !this.isCorrect) {
                return 'border-red-500 bg-red-500 text-white';
            }

            if (!this.isCorrect && isCorrectOption) {
                return 'border-brand-green bg-brand-green text-white';
            }

            return 'border-slate-200 bg-white text-slate-400';
        },

        resultLabel() {
            if (!this.submitted) {
                return '';
            }

            return this.isCorrect ? 'Correct' : 'Incorrect';
        },

        awardedMarks() {
            return this.submitted && this.isCorrect ? this.marks : 0;
        },
    }));
}
