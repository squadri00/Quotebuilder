<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Plan;
use App\Services\TemplateCloner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function index(Request $request): View
    {
        $query = Business::withCount(['users', 'products', 'quotes'])
            ->where('is_template', false)
            ->with(['createdFromTemplate', 'industry', 'plan', 'owner']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        $businesses = $query->latest()->paginate(20)->withQueryString();

        return view('superadmin.businesses.index', compact('businesses'));
    }

    public function show(Business $business): View
    {
        $business->load(['createdFromTemplate', 'templatesUsed', 'industry', 'plan', 'users' => fn ($q) => $q->orderBy('id')]);
        $business->loadCount(['users', 'products', 'rules', 'quotes']);

        $plans = Plan::orderBy('price')->get();
        $subscription = $business->subscription('default');
        $supportSubscription = $business->subscription('support');
        $templates = Business::where('is_template', true)->orderBy('name')->get();

        return view('superadmin.businesses.show', compact('business', 'plans', 'subscription', 'supportSubscription', 'templates'));
    }

    /**
     * Temporarily off (2026-08-16) — every business is locked to a single
     * industry and running this a second time re-clones the WHOLE
     * template with no duplicate check, so a business that already has
     * products from that template ends up with two of everything. Left
     * in place, not deleted, so it's a one-line flip to bring back once
     * that's fixed (or removed for good, if it turns out nobody needs it
     * once the per-product self-service Templates page covers the same
     * job safely — see TemplateController::store()'s duplicate check).
     */
    private const COPY_TEMPLATE_ENABLED = false;

    /**
     * Pushes a template's entire catalog (products, questions, options,
     * rules, images) into an existing business — purely additive,
     * alongside whatever that business already has. Unlike the
     * registration-time clone (a brand new, empty business), this lets
     * Super Admin retroactively give an existing business a template's
     * starter data, e.g. after building it out post-signup, or to help
     * a business that started from scratch. Never touches the
     * business's users, plan, billing, or existing products — and never
     * writes to the template itself.
     */
    public function copyTemplate(Request $request, Business $business): RedirectResponse
    {
        abort_unless(self::COPY_TEMPLATE_ENABLED, 423, 'Copy Template Data is temporarily disabled.');

        $validated = $request->validate([
            'template_id' => ['required', Rule::exists('businesses', 'id')->where('is_template', true)],
        ]);

        $template = Business::findOrFail($validated['template_id']);

        (new TemplateCloner)->clone($template, $business);

        AuditLog::record(
            $request->user('admin'),
            'business.template_copied',
            $business,
            "Copied template \"{$template->name}\" (#{$template->id}) into \"{$business->name}\" (#{$business->id})."
        );

        return back()->with('status', "\"{$template->name}\"'s products have been added to \"{$business->name}\".");
    }

    public function assignPlan(Request $request, Business $business): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['nullable', Rule::exists('plans', 'id')],
        ]);

        $oldPlanName = $business->plan?->name ?? 'no plan';
        $business->update(['plan_id' => $validated['plan_id'] ?? null]);
        $newPlanName = $business->fresh()->plan?->name ?? 'no plan';

        AuditLog::record(
            $request->user('admin'),
            'business.plan_changed',
            $business,
            "Changed plan for \"{$business->name}\" (#{$business->id}) from \"{$oldPlanName}\" to \"{$newPlanName}\"."
        );

        return back()->with('status', "\"{$business->name}\" is now on the \"{$newPlanName}\" plan.");
    }

    public function toggleActive(Request $request, Business $business): RedirectResponse
    {
        $business->update(['is_active' => ! $business->is_active]);

        $verb = $business->is_active ? 'Activated' : 'Deactivated';

        AuditLog::record(
            $request->user('admin'),
            $business->is_active ? 'business.activated' : 'business.deactivated',
            $business,
            "{$verb} business \"{$business->name}\" (#{$business->id})."
        );

        return back()->with('status', "\"{$business->name}\" is now ".($business->is_active ? 'active.' : 'deactivated.'));
    }

    /**
     * Manual override — grants or revokes Priority Support access
     * regardless of any Stripe subscription, same pattern as plan_id.
     */
    public function toggleSupportAccess(Request $request, Business $business): RedirectResponse
    {
        $business->update(['support_access_granted' => ! $business->support_access_granted]);

        $verb = $business->support_access_granted ? 'Granted' : 'Revoked';

        AuditLog::record(
            $request->user('admin'),
            $business->support_access_granted ? 'business.support_access_granted' : 'business.support_access_revoked',
            $business,
            "{$verb} Priority Support access for \"{$business->name}\" (#{$business->id})."
        );

        return back()->with('status', "Priority Support for \"{$business->name}\" is now ".($business->support_access_granted ? 'granted.' : 'revoked.'));
    }

    /**
     * Deleting a business only ever wipes local rows — the `subscriptions`
     * table Cashier ships has no foreign key to businesses at all, and
     * even if it did, a cascade delete only removes our own database
     * record of a subscription, never tells Stripe to actually stop
     * billing it. Without this, deleting a business with an active paid
     * plan left Stripe still charging their card every month with no
     * account left in the app to even show it was happening. Cancelled
     * immediately (cancelNow(), not the grace-period cancel() the
     * business's own Billing page uses) since there's no account left
     * for a grace period to apply to.
     */
    public function destroy(Request $request, Business $business): RedirectResponse
    {
        $name = $business->name;
        $id = $business->id;

        $activeSubscriptions = $business->subscriptions->filter->active();

        if ($activeSubscriptions->isNotEmpty()) {
            try {
                $activeSubscriptions->each->cancelNow();
            } catch (\Throwable $e) {
                report($e);

                return back()->with('status', "Couldn't delete \"{$name}\" — cancelling its Stripe subscription failed, so nothing was deleted. Check Stripe and try again.");
            }
        }

        AuditLog::record(
            $request->user('admin'),
            'business.deleted',
            null,
            $activeSubscriptions->isNotEmpty()
                ? "Deleted business \"{$name}\" (#{$id}) and all its data — also cancelled {$activeSubscriptions->count()} active Stripe subscription(s)."
                : "Deleted business \"{$name}\" (#{$id}) and all its data."
        );

        $business->delete();

        return redirect()->route('superadmin.businesses.index')->with('status', "\"{$name}\" has been deleted.");
    }

    public function impersonate(Request $request, Business $business): RedirectResponse
    {
        $target = $business->users()->oldest('id')->first();

        abort_unless($target, 404, 'This business has no users to impersonate.');

        AuditLog::record(
            $request->user('admin'),
            'impersonation.started',
            $business,
            "Started impersonating \"{$target->name}\" ({$target->email}) at business \"{$business->name}\" (#{$business->id})."
        );

        Auth::guard('web')->login($target);

        $request->session()->put('impersonating_business_id', $business->id);
        $request->session()->put('impersonating_business_name', $business->name);

        return redirect()->route('dashboard');
    }

    public function exitImpersonation(Request $request): RedirectResponse
    {
        $businessId = $request->session()->get('impersonating_business_id');
        $businessName = $request->session()->get('impersonating_business_name');
        $business = $businessId ? Business::find($businessId) : null;

        AuditLog::record(
            $request->user('admin'),
            'impersonation.ended',
            $business,
            "Stopped impersonating business \"{$businessName}\" (#{$businessId})."
        );

        Auth::guard('web')->logout();

        $request->session()->forget(['impersonating_business_id', 'impersonating_business_name']);
        $request->session()->regenerate();

        return match (true) {
            $business?->is_template => redirect()->route('superadmin.templates.index'),
            (bool) $business => redirect()->route('superadmin.businesses.show', $business),
            default => redirect()->route('superadmin.dashboard'),
        };
    }
}
