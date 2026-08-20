<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ImplementationOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ImplementationOrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = ImplementationOrder::with('business')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        } else {
            // Abandoned checkouts (never paid, Stripe's own link expired)
            // are noise in the default queue — still reachable via the
            // status filter for anyone who wants to see them.
            $query->where('status', '!=', 'abandoned');
        }

        $orders = $query->paginate(20)->withQueryString();

        $abandonedCount = $request->filled('status') ? 0 : ImplementationOrder::where('status', 'abandoned')->count();

        return view('superadmin.implementation-orders.index', compact('orders', 'abandonedCount'));
    }

    public function updateStatus(Request $request, ImplementationOrder $implementationOrder): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(ImplementationOrder::STATUSES)],
        ]);

        $implementationOrder->update(['status' => $validated['status']]);

        return back()->with('status', "Order #{$implementationOrder->id} is now \"{$implementationOrder->statusLabel()}\".");
    }
}
