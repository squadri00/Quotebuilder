<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Industry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Templates are just businesses flagged is_template=true. Their sample
 * products/questions/options/rules are managed natively, right here in
 * Super Admin — see SuperAdmin\ProductController and friends — not via
 * impersonation. (Impersonation is still how Super Admin reaches a real
 * customer business's own products, via BusinessController::impersonate.)
 */
class TemplateController extends Controller
{
    public function index(): View
    {
        $templates = Business::withoutGlobalScopes()
            ->withCount(['products', 'rules'])
            ->with('industry')
            ->where('is_template', true)
            ->latest()
            ->get();

        return view('superadmin.templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('superadmin.templates.create', ['industries' => Industry::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry_id' => ['nullable', 'exists:industries,id'],
        ]);

        $template = Business::create([
            'name' => $validated['name'],
            'industry_id' => $validated['industry_id'] ?? null,
            'is_template' => true,
        ]);

        // No one is meant to log in with this account directly — it exists
        // only so the business row has an owner, same as every other
        // business — so the password is random and never shared.
        User::create([
            'business_id' => $template->id,
            'name' => $template->name.' Template Admin',
            'email' => Str::slug($template->name).'-template-'.$template->id.'@templates.internal',
            'password' => Hash::make(Str::random(40)),
        ]);

        AuditLog::record(
            $request->user('admin'),
            'template.created',
            $template,
            "Created template \"{$template->name}\" (#{$template->id})."
        );

        return redirect()->route('superadmin.products.create', $template)
            ->with('status', "Template \"{$template->name}\" created — add its first product below.");
    }

    public function edit(Business $template): View
    {
        abort_unless($template->is_template, 404);

        return view('superadmin.templates.edit', [
            'template' => $template,
            'industries' => Industry::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Business $template): RedirectResponse
    {
        abort_unless($template->is_template, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry_id' => ['nullable', 'exists:industries,id'],
        ]);

        $template->update($validated);

        AuditLog::record(
            $request->user('admin'),
            'template.updated',
            $template,
            "Updated template \"{$template->name}\" (#{$template->id})."
        );

        return redirect()->route('superadmin.templates.index')->with('status', "Template \"{$template->name}\" updated.");
    }

    public function destroy(Request $request, Business $template): RedirectResponse
    {
        abort_unless($template->is_template, 404);

        $name = $template->name;
        $id = $template->id;

        AuditLog::record(
            $request->user('admin'),
            'template.deleted',
            null,
            "Deleted template \"{$name}\" (#{$id})."
        );

        $template->delete();

        return redirect()->route('superadmin.templates.index')->with('status', "Template \"{$name}\" deleted.");
    }
}
