<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('business')->latest();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $users = $query->paginate(25)->withQueryString();

        return view('superadmin.users.index', compact('users'));
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        $verb = $user->is_active ? 'Activated' : 'Deactivated';

        AuditLog::record(
            $request->user('admin'),
            $user->is_active ? 'user.activated' : 'user.deactivated',
            $user,
            "{$verb} user \"{$user->name}\" ({$user->email})."
        );

        return back()->with('status', "\"{$user->name}\" is now ".($user->is_active ? 'active.' : 'deactivated.'));
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $newPassword = Str::password(16);

        $user->update(['password' => Hash::make($newPassword)]);

        // The password itself is deliberately not logged — the audit trail
        // records that a reset happened and who did it, never the secret.
        AuditLog::record(
            $request->user('admin'),
            'user.password_reset',
            $user,
            "Reset password for user \"{$user->name}\" ({$user->email})."
        );

        return back()->with('status', "Password reset for \"{$user->name}\".")
            ->with('generatedPassword', $newPassword);
    }
}
