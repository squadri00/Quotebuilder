/**
 * The step-by-step question engine shared by both the public quote
 * builder (resources/views/public/quote-builder.blade.php) and the
 * internal, staff-facing ones (resources/views/quotes/builder.blade.php
 * and resources/views/superadmin/quotes/builder.blade.php). Registered as
 * an Alpine.data() component in app.js — views call
 * `x-data="quoteWizard(questions, priceUrl, autoFinish)"` so this logic
 * lives in exactly one place.
 *
 * Mirrors app/Support/QuestionVisibility.php server-side — kept in sync
 * so the wizard can evaluate visibility instantly, client-side, per step.
 * The server always re-derives visibility from the submitted answers
 * itself before pricing/saving, so this client copy only controls what
 * the person sees while stepping through — it's never trusted for pricing.
 *
 * @param {Array} questions
 * @param {?string} priceUrl POSTs the answers-so-far here and reads back
 *   `{ price }` to keep a running total visible at every step — the same
 *   RulesEngine calculation the final review screen uses, not a client-side
 *   estimate, so it's never wrong about a rule/discount applying. Only the
 *   two staff-facing wizards pass this; the public one leaves it null and
 *   running-total behaviour simply never activates.
 * @param {boolean} autoFinish Staff-facing wizards need nothing further
 *   from this step once every question is answered (customer info comes
 *   later, on the Review screen) — the running total already showed them
 *   the price the whole way through, so reaching the end submits straight
 *   into Review with no extra confirmation click. The public wizard still
 *   needs the customer to type their name/email here, so it leaves this
 *   false and keeps its own explicit submit button.
 */
export default function quoteWizard(questions, priceUrl = null, autoFinish = false) {
    return {
        questions,
        answers: {},
        currentQuestionId: null,
        priceUrl,
        autoFinish,
        runningTotal: null,
        priceLoading: false,
        _priceTimer: null,

        init() {
            const visible = this.visibleQuestions();
            this.currentQuestionId = visible.length ? visible[0].id : 'DONE';

            if (this.priceUrl) {
                this.fetchPrice();
                this.$watch('answers', () => this.scheduleFetchPrice());
            }

            if (this.currentQuestionId === 'DONE' && this.autoFinish) {
                this.$nextTick(() => this.submitDoneForm());
            }
        },

        scheduleFetchPrice() {
            clearTimeout(this._priceTimer);
            this._priceTimer = setTimeout(() => this.fetchPrice(), 400);
        },

        async fetchPrice() {
            if (! this.priceUrl) return;

            this.priceLoading = true;
            try {
                const response = await fetch(this.priceUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    },
                    body: JSON.stringify({ answers: JSON.stringify(this.filteredAnswers()) }),
                });
                if (response.ok) {
                    const data = await response.json();
                    this.runningTotal = data.price;
                }
            } catch (e) {
                // The running total is a convenience — never something the
                // wizard depends on to function, so a failed request here
                // just leaves the last-known total on screen.
            } finally {
                this.priceLoading = false;
            }
        },

        submitDoneForm() {
            if (this.$refs.doneForm) {
                this.submitForm();
                this.$refs.doneForm.submit();
            }
        },

        isVisible(displayConditions, answers) {
            const conditions = (displayConditions && displayConditions.conditions) || [];
            if (conditions.length === 0) return true;
            const logic = (displayConditions && displayConditions.logic) || 'and';
            const results = conditions.map(c => this.conditionMatches(c, answers));
            return logic === 'or' ? results.includes(true) : ! results.includes(false);
        },
        conditionMatches(condition, answers) {
            const questionId = condition.question_id;
            if (questionId === null || questionId === undefined || ! Object.prototype.hasOwnProperty.call(answers, questionId)) return false;
            const actual = answers[questionId];
            const expected = condition.value;
            if (condition.operator === 'equals') return String(actual) === String(expected);
            if (condition.operator === 'not_equals') return String(actual) !== String(expected);
            if (condition.operator === 'in') return Array.isArray(expected) && expected.map(String).includes(String(actual));
            return false;
        },
        // Walks questions in order, only ever judging a question's own
        // condition against answers to questions that were themselves
        // genuinely visible — never a forward reference.
        visibleQuestions() {
            const visible = [];
            const acc = {};
            for (const q of this.questions) {
                if (this.isVisible(q.display_conditions, acc)) {
                    visible.push(q);
                    const value = this.answers[q.id];
                    if (value !== undefined && value !== null && value !== '') acc[q.id] = value;
                }
            }
            return visible;
        },

        get currentIndex() { return this.visibleQuestions().findIndex(q => q.id === this.currentQuestionId); },
        get totalSteps() { return this.visibleQuestions().length + 1; },
        get stepNumber() { return this.currentQuestionId === 'DONE' ? this.totalSteps : this.currentIndex + 1; },
        get progressPercent() { return Math.round(((this.stepNumber - 1) / this.totalSteps) * 100); },

        // If an earlier answer changes, any later answers (which may have
        // been shown/computed based on the old value) are cleared so they
        // get freshly re-evaluated rather than left stale.
        clearDownstreamAnswers(questionId) {
            const idx = this.questions.findIndex(q => q.id === questionId);
            for (let i = idx + 1; i < this.questions.length; i++) {
                delete this.answers[this.questions[i].id];
            }
        },
        advanceFrom(questionId) {
            const visible = this.visibleQuestions();
            const idx = visible.findIndex(q => q.id === questionId);
            const nextQuestion = idx >= 0 ? visible[idx + 1] : undefined;
            this.currentQuestionId = nextQuestion ? nextQuestion.id : 'DONE';

            if (this.currentQuestionId === 'DONE' && this.autoFinish) {
                this.$nextTick(() => this.submitDoneForm());
            }
        },

        selectOption(questionId, optionId) {
            this.answers[questionId] = optionId;
            this.clearDownstreamAnswers(questionId);
            this.advanceFrom(questionId);
        },
        goNext() {
            this.clearDownstreamAnswers(this.currentQuestionId);
            this.advanceFrom(this.currentQuestionId);
        },
        back() {
            const visible = this.visibleQuestions();
            if (this.currentQuestionId === 'DONE') {
                this.currentQuestionId = visible.length ? visible[visible.length - 1].id : 'DONE';
                return;
            }
            const idx = visible.findIndex(q => q.id === this.currentQuestionId);
            if (idx > 0) this.currentQuestionId = visible[idx - 1].id;
        },
        goToQuestion(questionId) {
            this.currentQuestionId = questionId;
        },

        answeredQuestions() {
            return this.visibleQuestions()
                .map(q => ({ question: q, value: this.answers[q.id] }))
                .filter(item => item.value !== undefined && item.value !== null && item.value !== '');
        },
        answerLabel(question, value) {
            if (question.type === 'single_choice') {
                const option = question.options.find(o => String(o.id) === String(value));
                return option ? option.label : value;
            }
            return value;
        },

        // Only ever includes answers to questions actually visible under the
        // final answers given — the server re-checks this too.
        filteredAnswers() {
            const filtered = {};
            for (const q of this.visibleQuestions()) {
                const value = this.answers[q.id];
                if (value !== undefined && value !== null && value !== '') filtered[q.id] = value;
            }
            return filtered;
        },

        // Every view uses the same `x-ref="answersInput"` hidden field
        // convention.
        submitForm() {
            this.$refs.answersInput.value = JSON.stringify(this.filteredAnswers());
        },
    };
}
