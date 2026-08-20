<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SitePage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SitePageController extends Controller
{
    public function index(): View
    {
        $sitePages = SitePage::orderBy('title')->get();

        return view('superadmin.site-pages.index', compact('sitePages'));
    }

    public function create(): View
    {
        return view('superadmin.site-pages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $sitePage = SitePage::create($this->validated($request));

        return redirect()->route('superadmin.site-pages.index')->with('status', "\"{$sitePage->title}\" added.");
    }

    public function edit(SitePage $sitePage): View
    {
        return view('superadmin.site-pages.edit', compact('sitePage'));
    }

    public function update(Request $request, SitePage $sitePage): RedirectResponse
    {
        $sitePage->update($this->validated($request, $sitePage));

        return redirect()->route('superadmin.site-pages.index')->with('status', "\"{$sitePage->title}\" updated — the public page now shows today's date as the last updated date.");
    }

    public function destroy(SitePage $sitePage): RedirectResponse
    {
        $title = $sitePage->title;

        $sitePage->delete();

        return redirect()->route('superadmin.site-pages.index')->with('status', "\"{$title}\" deleted.");
    }

    private function validated(Request $request, ?SitePage $sitePage = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'slug' => [
                'required', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('site_pages', 'slug')->ignore($sitePage),
            ],
            'content' => ['required', 'string'],
        ]);

        return $validated;
    }
}
