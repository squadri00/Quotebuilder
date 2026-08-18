<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Lets a Super Admin change their own login password — separate from
 * SuperAdmin\UserController::resetPassword(), which resets a *business*
 * owner's password instead. There was previously no self-service way to
 * do this at all; the only admin-guard auth screens were Login/Logout.
 */
class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('superadmin.password.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $admin = Auth::guard('admin')->user();

        $admin->update(['password' => Hash::make($validated['password'])]);

        // Same "record that it happened, never the secret" pattern as
        // UserController::resetPassword().
        AuditLog::record(
            $admin,
            'admin.password_changed',
            null,
            "\"{$admin->name}\" ({$admin->email}) changed their own Super Admin password."
        );

        return back()->with('status', 'Password updated.');
    }
}
