<?php

namespace App\Http\Controllers;

use App\Models\BusinessTrainingArtifact;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * A business's own read-only view of the build sheets that came
 * installed with its products — see BusinessTrainingArtifact's docblock.
 * Nothing here is authored or edited by the business; content only ever
 * arrives via TemplateCloner::clone() and disappears when its product
 * does.
 */
class TrainingArtifactController extends Controller
{
    public function index(): View
    {
        $artifacts = Auth::user()->business->trainingArtifacts()
            ->with('product')
            ->get()
            ->sortBy(fn ($artifact) => $artifact->product?->name ?? $artifact->title);

        return view('training.index', compact('artifacts'));
    }

    public function show(BusinessTrainingArtifact $artifact): View
    {
        return view('training.show', compact('artifact'));
    }

    /**
     * Served raw, same approach as Super Admin's own copy — see
     * SuperAdmin\TrainingArtifactController::raw().
     */
    public function raw(BusinessTrainingArtifact $artifact): Response
    {
        return response($artifact->html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
