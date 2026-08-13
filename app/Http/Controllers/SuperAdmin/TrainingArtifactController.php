<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Industry;
use App\Models\TrainingArtifact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Super Admin's private training/reference library — see
 * App\Models\TrainingArtifact's docblock. Every route here sits behind
 * the same auth:admin middleware as the rest of routes/superadmin.php;
 * nothing here is ever reachable by a business user or the public.
 */
class TrainingArtifactController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $industryId = $request->query('industry_id', '');

        $artifacts = TrainingArtifact::with('industry', 'creator')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($industryId !== '', fn ($query) => $query->where('industry_id', $industryId))
            ->latest()
            ->get()
            ->sortBy(fn ($artifact) => $artifact->industry?->name ?? '')
            ->groupBy(fn ($artifact) => $artifact->industry?->name ?? 'General')
            ->sortKeys();

        $industries = Industry::orderBy('name')->get();

        return view('superadmin.training.index', compact('artifacts', 'search', 'industryId', 'industries'));
    }

    public function create(): View
    {
        return view('superadmin.training.create', ['industries' => Industry::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateArtifact($request);

        $artifact = TrainingArtifact::create([
            'title' => $validated['title'],
            'industry_id' => $validated['industry_id'] ?: null,
            'html' => $this->resolveHtml($request),
            'created_by' => Auth::guard('admin')->id(),
        ]);

        AuditLog::record(
            $request->user('admin'),
            'training_artifact.created',
            $artifact,
            "Added training artifact \"{$artifact->title}\"."
        );

        return redirect()->route('superadmin.training.show', $artifact)->with('status', "\"{$artifact->title}\" added.");
    }

    public function show(TrainingArtifact $artifact): View
    {
        return view('superadmin.training.show', compact('artifact'));
    }

    /**
     * The artifact's stored HTML, output raw — no admin layout, no
     * wrapping chrome — so it renders exactly as authored (scripts and
     * all), the same way a standalone HTML page or a Claude Artifact
     * would. Used as the show page's iframe source. Still behind
     * auth:admin like every other route here — never reachable without
     * being logged in as Super Admin.
     */
    public function raw(TrainingArtifact $artifact): Response
    {
        return response($artifact->html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function edit(TrainingArtifact $artifact): View
    {
        return view('superadmin.training.edit', [
            'artifact' => $artifact,
            'industries' => Industry::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, TrainingArtifact $artifact): RedirectResponse
    {
        $validated = $this->validateArtifact($request, requireContent: false);

        $artifact->update([
            'title' => $validated['title'],
            'industry_id' => $validated['industry_id'] ?: null,
            'html' => $this->hasNewContent($request) ? $this->resolveHtml($request) : $artifact->html,
        ]);

        AuditLog::record(
            $request->user('admin'),
            'training_artifact.updated',
            $artifact,
            "Updated training artifact \"{$artifact->title}\"."
        );

        return redirect()->route('superadmin.training.show', $artifact)->with('status', "\"{$artifact->title}\" updated.");
    }

    public function destroy(Request $request, TrainingArtifact $artifact): RedirectResponse
    {
        $title = $artifact->title;
        $artifact->delete();

        AuditLog::record(
            $request->user('admin'),
            'training_artifact.deleted',
            null,
            "Deleted training artifact \"{$title}\"."
        );

        return redirect()->route('superadmin.training.index')->with('status', "\"{$title}\" deleted.");
    }

    /**
     * @return array{title: string, industry_id: ?string, html: ?string}
     */
    private function validateArtifact(Request $request, bool $requireContent = true): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'industry_id' => ['nullable', Rule::exists('industries', 'id')],
            'html' => ['nullable', 'string', 'max:2000000'],
            'html_file' => ['nullable', 'file', 'mimes:html,htm', 'max:5120'],
        ]);

        if ($requireContent && ! $this->hasNewContent($request)) {
            throw ValidationException::withMessages([
                'html' => 'Paste the HTML, or upload an .html file.',
            ]);
        }

        return $validated;
    }

    private function hasNewContent(Request $request): bool
    {
        return $request->hasFile('html_file') || trim((string) $request->input('html', '')) !== '';
    }

    private function resolveHtml(Request $request): string
    {
        if ($request->hasFile('html_file')) {
            return $request->file('html_file')->get();
        }

        return $request->input('html', '');
    }
}
