<?php

namespace App\Http\Controllers;

use App\Mail\TeamInvitationMail;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Support\TeamPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Owner-only team management — every action here is guarded by
 * User::canManageTeam() (owner role only), enforced via the 'team.manage'
 * middleware on the whole route group (see routes/web.php).
 *
 * Every invitee joins as role=member. There's no separate preset "Admin"
 * role — the Owner grants exactly what a Member can touch, product by
 * product and permission by permission (see TeamPermissions), with a
 * "grant all" convenience in the UI. Team and Billing are never
 * grantable, by design (see User::canManageTeam()/canAccessBilling()).
 */
class TeamController extends Controller
{
    public function index(): View
    {
        $business = Auth::user()->business;

        $members = $business->users()->with('products')->orderBy('name')->get();

        $invitations = TeamInvitation::with('invitedBy')
            ->whereNull('accepted_at')
            ->latest()
            ->get();

        $products = $business->products()->orderBy('name')->get();

        return view('team.index', compact('members', 'invitations', 'products'));
    }

    public function create(): View
    {
        $products = Auth::user()->business->products()->orderBy('name')->get();

        return view('team.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $business = Auth::user()->business;

        if ($business->hasReachedUserLimit()) {
            return redirect()->route('team.index')->with(
                'error',
                "You've reached your plan's limit of {$business->userLimit()} user(s). Upgrade your plan to add more."
            );
        }

        $validated = $this->validateInvitation($request, $business);

        if (User::where('email', $validated['email'])->exists()) {
            return back()->withErrors(['email' => 'Someone with this email already has an account.'])->withInput();
        }

        TeamInvitation::where('business_id', $business->id)
            ->where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->delete();

        $invitation = TeamInvitation::create([
            'business_id' => $business->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => User::ROLE_MEMBER,
            'product_ids' => $validated['product_ids'],
            'permissions' => $validated['permissions'],
            'invited_by' => Auth::id(),
        ]);

        Mail::to($invitation->email)->send(new TeamInvitationMail($invitation));

        return redirect()->route('team.index')->with('status', "Invitation sent to {$invitation->email}.");
    }

    public function resend(TeamInvitation $invitation): RedirectResponse
    {
        abort_if($invitation->isAccepted(), 404);

        $invitation->update(['expires_at' => now()->addDays(7)]);

        Mail::to($invitation->email)->send(new TeamInvitationMail($invitation));

        return back()->with('status', "Invitation resent to {$invitation->email}.");
    }

    public function revoke(TeamInvitation $invitation): RedirectResponse
    {
        abort_if($invitation->isAccepted(), 404);

        $invitation->delete();

        return back()->with('status', 'Invitation revoked.');
    }

    public function edit(User $member): View
    {
        $business = Auth::user()->business;

        abort_unless($member->business_id === $business->id, 404);
        abort_if($member->isOwner(), 404, "The account owner's access can't be changed.");

        $products = $business->products()->orderBy('name')->get();
        $grantedProductIds = $member->products()->pluck('products.id')->all();

        return view('team.edit', compact('member', 'products', 'grantedProductIds'));
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($member->business_id === $business->id, 404);
        abort_if($member->isOwner(), 404, "The account owner's access can't be changed.");

        $validated = $this->validatePermissions($request);

        $validProductIds = $business->products()->whereIn('id', $validated['product_ids'])->pluck('id');
        $member->products()->sync($validProductIds);

        $member->update(['permissions' => $validated['permissions']]);

        return redirect()->route('team.index')->with('status', "{$member->name}'s access updated.");
    }

    /**
     * Deactivation, not deletion — several tables (support tickets,
     * announcement reads) cascade-delete on a real user removal, which
     * would silently wipe that history. Deactivating blocks login and
     * hides them from active-member views while keeping records intact.
     */
    public function toggleActive(User $member): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless($member->business_id === $business->id, 404);
        abort_if($member->isOwner(), 404, 'The account owner cannot be deactivated.');

        $member->update(['is_active' => ! $member->is_active]);

        return back()->with('status', $member->is_active ? "{$member->name} reactivated." : "{$member->name} deactivated.");
    }

    private function validateInvitation(Request $request, $business): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        return array_merge($validated, $this->validatePermissions($request, $business));
    }

    /**
     * Shared by both invite (store) and edit (update) — the product/
     * permission checklists are identical in both forms.
     */
    private function validatePermissions(Request $request, $business = null): array
    {
        $business ??= Auth::user()->business;

        $request->validate([
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:'.implode(',', TeamPermissions::keys())],
        ]);

        $validProductIds = $business->products()->whereIn('id', $request->input('product_ids', []))->pluck('id')->all();
        $validPermissions = array_values(array_intersect($request->input('permissions', []), TeamPermissions::keys()));

        return [
            'product_ids' => $validProductIds,
            'permissions' => $validPermissions,
        ];
    }
}
