<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The business's own customer directory — see App\Models\Customer's
 * docblock for how it's populated (every quote source finds-or-creates
 * one). This controller is purely for browsing/searching that directory
 * and hand-editing a record; route-model binding on {customer} is already
 * tenant-scoped by BelongsToBusiness, same as every other resource here.
 */
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $customers = Auth::user()->business->customers()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->withCount('quotes')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCustomer($request);

        $customer = Auth::user()->business->customers()->create($validated);

        return redirect()->route('customers.show', $customer)->with('status', 'Customer added.');
    }

    public function show(Customer $customer): View
    {
        $quotes = $customer->quotes()->with('product')->latest()->paginate(15);

        return view('customers.show', compact('customer', 'quotes'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validateCustomer($request, $customer));

        return redirect()->route('customers.show', $customer)->with('status', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        // Their past quotes aren't touched — customer_id just goes back to
        // null (see the FK's nullOnDelete), and customer_name/customer_email
        // frozen on each quote already shows who it was for regardless.
        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Customer deleted.');
    }

    /**
     * Blocks this customer's most-recently-seen IP from submitting any
     * more public quotes for this business — see PublicQuoteController::
     * store(). Scoped to this business only; has no effect on any other
     * business's own quote forms, even if the same IP shows up there too.
     */
    public function block(Customer $customer): RedirectResponse
    {
        $customer->update(['is_blocked' => true]);

        return redirect()->back()->with('status', "\"{$customer->name}\" is now blocked from submitting quotes.");
    }

    public function unblock(Customer $customer): RedirectResponse
    {
        $customer->update(['is_blocked' => false]);

        return redirect()->back()->with('status', "\"{$customer->name}\" can submit quotes again.");
    }

    private function validateCustomer(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                'unique:customers,email,'.($customer?->id).',id,business_id,'.Auth::user()->business_id,
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
