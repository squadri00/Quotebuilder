<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * See SuperAdmin\ProductController's docblock.
 */
class OptionController extends Controller
{
    public function index(Question $question): View
    {
        $business = $question->product->business;
        $options = $question->options()->orderBy('label')->get();

        return view('superadmin.options.index', compact('business', 'question', 'options'));
    }

    public function create(Question $question): View
    {
        $business = $question->product->business;

        return view('superadmin.options.create', compact('business', 'question'));
    }

    public function store(Request $request, Question $question): RedirectResponse
    {
        $business = $question->product->business;

        $validated = $this->validateOption($request);
        $validated['business_id'] = $business->id;

        $option = $question->options()->create($validated);

        AuditLog::record(
            $request->user('admin'),
            'option.created',
            $business,
            "Created option \"{$option->label}\" on question \"{$question->question_text}\"."
        );

        return redirect()->route('superadmin.questions.options.index', $question)->with('status', 'Option created.');
    }

    public function edit(Option $option): View
    {
        $business = $option->question->product->business;

        return view('superadmin.options.edit', compact('business', 'option'));
    }

    public function update(Request $request, Option $option): RedirectResponse
    {
        $business = $option->question->product->business;

        $validated = $this->validateOption($request);

        $option->update($validated);

        AuditLog::record(
            $request->user('admin'),
            'option.updated',
            $business,
            "Updated option \"{$option->label}\" (#{$option->id})."
        );

        return redirect()->route('superadmin.questions.options.index', $option->question_id)->with('status', 'Option updated.');
    }

    public function destroy(Request $request, Option $option): RedirectResponse
    {
        $business = $option->question->product->business;
        $questionId = $option->question_id;
        $label = $option->label;

        $option->delete();

        AuditLog::record(
            $request->user('admin'),
            'option.deleted',
            $business,
            "Deleted option \"{$label}\"."
        );

        return redirect()->route('superadmin.questions.options.index', $questionId)->with('status', 'Option deleted.');
    }

    private function validateOption(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'price_modifier' => ['required', 'numeric'],
        ]);
    }
}
