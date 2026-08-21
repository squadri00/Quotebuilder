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
 * @param {Object} initialAnswers Pre-fills the wizard's answers — used
 *   only when re-opening an existing quote for editing (see
 *   InternalQuoteController::edit()), so staff land back on question one
 *   with everything already filled in exactly as it was, free to change
 *   any answer via Back/Next or the Selections list, same wizard either
 *   way. Left {} for a normal brand-new quote.
 * @param {?string} draftKey When set, answers are continuously saved to
 *   localStorage under this key and restored on init — so a customer who
 *   accidentally refreshes or closes the tab mid-wizard doesn't lose their
 *   progress. Only the public wizard passes this (a per business+product
 *   key); the staff-facing wizards leave it null, since initialAnswers
 *   already covers their own "come back to this" case (editing a saved
 *   quote) and a shared/kiosk staff machine is exactly where you don't
 *   want quote drafts lingering in browser storage. The result page clears
 *   this same key once the quote is actually submitted.
 */
export default function quoteWizard(questions, priceUrl = null, autoFinish = false, initialAnswers = {}, draftKey = null) {
    return {
        questions,
        answers: {...initialAnswers},
        currentQuestionId: null,
        priceUrl,
        autoFinish,
        draftKey,
        runningTotal: null,
        priceLoading: false,
        _priceTimer: null,
        draftToastVisible: false,
        _draftToastShown: false,
        _draftToastTimer: null,

        init() {
            let resumingDraft = false;

            if (this.draftKey && Object.keys(initialAnswers).length === 0) {
                const saved = this.loadDraft();
                if (saved && Object.keys(saved).length) {
                    this.answers = saved;
                    resumingDraft = true;
                }
            }

            const visible = this.visibleQuestions();

            // Resuming a saved draft picks up at the first question that's
            // still unanswered (or DONE, if everything already was) —
            // editing an existing quote via initialAnswers deliberately
            // stays on question one instead, see the docblock above.
            if (resumingDraft) {
                const firstUnanswered = visible.find(q => {
                    const v = this.answers[q.id];
                    return v === undefined || v === null || v === '';
                });
                this.currentQuestionId = firstUnanswered ? firstUnanswered.id : 'DONE';
            } else {
                this.currentQuestionId = visible.length ? visible[0].id : 'DONE';
            }

            if (this.priceUrl) {
                this.fetchPrice();
                this.$watch('answers', () => this.scheduleFetchPrice());
            }

            if (this.draftKey) {
                this.$watch('answers', () => this.saveDraft());

                // Desktop "exit intent" — the mouse crossing above the
                // page (toward the tab bar/address bar) is the closest
                // thing a browser exposes to "about to leave." Doesn't
                // fire on mobile, which is exactly why saveDraft() below
                // also shows this the moment there's actually a draft to
                // talk about, so touch-only visitors still get told.
                document.addEventListener('mouseout', (event) => {
                    if (! event.relatedTarget && event.clientY <= 0 && Object.keys(this.answers).length > 0) {
                        this.showDraftToast();
                    }
                });
            }

            if (this.currentQuestionId === 'DONE' && this.autoFinish) {
                this.$nextTick(() => this.submitDoneForm());
            }
        },

        loadDraft() {
            try {
                const raw = localStorage.getItem(this.draftKey);
                return raw ? JSON.parse(raw) : null;
            } catch (e) {
                return null;
            }
        },
        saveDraft() {
            if (! this.draftKey) return;
            try {
                localStorage.setItem(this.draftKey, JSON.stringify(this.answers));

                // First answer saved is the first moment there's actually
                // something worth reassuring someone about — this is the
                // mobile-safe counterpart to the exit-intent listener
                // above, since "about to leave" can't be reliably detected
                // on a touchscreen.
                if (Object.keys(this.answers).length > 0) {
                    this.showDraftToast();
                }
            } catch (e) {
                // Storage full/disabled (private browsing, etc.) — the
                // draft is a convenience, never something the wizard
                // depends on to function.
            }
        },

        // Shown once per page load, from whichever trigger fires first
        // (exit-intent on desktop, first saved answer on mobile) — a
        // second trigger after that would just be noise.
        showDraftToast() {
            if (this._draftToastShown) return;
            this._draftToastShown = true;
            this.draftToastVisible = true;
            clearTimeout(this._draftToastTimer);
            this._draftToastTimer = setTimeout(() => { this.draftToastVisible = false; }, 6000);
        },
        dismissDraftToast() {
            clearTimeout(this._draftToastTimer);
            this.draftToastVisible = false;
        },

        // Manual escape hatch for the draft-resume behaviour above — a
        // customer who wants a genuinely blank form (e.g. quoting a second,
        // different job) rather than picking up where a saved draft left
        // off. Clears the saved draft too, so reloading afterward doesn't
        // just resurrect what was just cleared.
        resetWizard() {
            this.answers = {};
            if (this.draftKey) {
                try {
                    localStorage.removeItem(this.draftKey);
                } catch (e) {
                    // Storage disabled — nothing to clear.
                }
            }
            this.dismissDraftToast();
            this._draftToastShown = false;
            this.runningTotal = null;
            const visible = this.visibleQuestions();
            this.currentQuestionId = visible.length ? visible[0].id : 'DONE';
            this.focusCurrentHeading();
        },

        // Moves keyboard/screen-reader focus to the newly-current
        // question's heading — without this, a screen reader has no signal
        // that the "page" changed at all, since this is a single-page
        // wizard where steps are shown/hidden rather than navigated to.
        // Views that don't mark their headings with data-question-heading
        // simply get a no-op here.
        focusCurrentHeading() {
            this.$nextTick(() => {
                const el = this.$root.querySelector('[data-question-heading="' + this.currentQuestionId + '"]');
                if (el) el.focus();
            });
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
            this.focusCurrentHeading();

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
                this.focusCurrentHeading();
                return;
            }
            const idx = visible.findIndex(q => q.id === this.currentQuestionId);
            if (idx > 0) this.currentQuestionId = visible[idx - 1].id;
            this.focusCurrentHeading();
        },
        goToQuestion(questionId) {
            this.currentQuestionId = questionId;
            this.focusCurrentHeading();
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
