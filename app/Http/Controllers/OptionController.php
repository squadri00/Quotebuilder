<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Question;
use App\Support\DeletionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Question $question): View
    {
        abort_unless(Auth::user()->canAccessProduct($question->product), 404);

        $options = $question->options()->orderBy('label')->get();

        return view('options.index', compact('question', 'options'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Question $question): View
    {
        abort_unless(Auth::user()->canAccessProduct($question->product), 404);

        return view('options.create', compact('question'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Question $question): RedirectResponse
    {
        abort_unless(Auth::user()->canAccessProduct($question->product), 404);

        $validated = $this->validateOption($request);

        $question->options()->create($validated);

        return redirect()->route('questions.options.index', $question)->with('status', 'Option created.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Option $option): View
    {
        abort_unless(Auth::user()->canAccessProduct($option->question->product), 404);

        return view('options.edit', compact('option'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Option $option): RedirectResponse
    {
        abort_unless(Auth::user()->canAccessProduct($option->question->product), 404);

        $validated = $this->validateOption($request);

        $option->update($validated);

        return redirect()->route('questions.options.index', $option->question_id)->with('status', 'Option updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Option $option): RedirectResponse
    {
        abort_unless(Auth::user()->canAccessProduct($option->question->product), 404);

        $blockers = DeletionGuard::blockersForOption($option);

        if (! empty($blockers)) {
            return redirect()->route('questions.options.index', $option->question_id)
                ->with('error', "Can't delete \"{$option->label}\" — it's used by ".DeletionGuard::joinList($blockers).'. Edit or remove that first, then try again.');
        }

        $questionId = $option->question_id;

        $option->delete();

        return redirect()->route('questions.options.index', $questionId)->with('status', 'Option deleted.');
    }

    private function validateOption(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'price_modifier' => ['required', 'numeric'],
        ]);
    }
}
