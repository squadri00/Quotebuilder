<h1 class="text-2xl font-bold text-gray-900">Complete Your Subscription</h1>
<p class="mt-1 text-sm text-gray-500">Your account is created once payment succeeds — no charge yet.</p>

@if (request('checkout') === 'cancelled')
    <div class="mt-6 rounded-xl border border-gray-200 bg-white px-4 py-3">
        <p class="text-sm text-gray-600">Checkout was cancelled — no charge was made. Try again whenever you're ready.</p>
    </div>
@endif

{{--
    No business-address form here — country/state_province are captured
    once, on the registration form itself (see
    partials/register-form.blade.php, required there whenever a paid plan
    is selected), and used as-is for the tax breakdown below. There's
    nothing left to edit on this page, so the whole thing is static PHP
    output rather than an Alpine-driven live preview.
--}}
<div class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-6">
    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Plan</p>
    <p class="mt-1 text-lg font-semibold text-gray-900">
        {{ $plan->name }} — ${{ number_format($plan->price, 2) }} / {{ $plan->billing_interval === 'yearly' ? 'yr' : 'mo' }}
    </p>

    <div class="mt-6 border-t border-gray-100 pt-4">
        <ul class="space-y-1 text-sm">
            <li class="flex justify-between gap-4 text-gray-600">
                <span>Subtotal</span>
                <span>${{ number_format($tax['base'], 2) }}</span>
            </li>
            @if ($tax['tax'] > 0)
                <li class="flex justify-between gap-4 text-gray-500">
                    <span>{{ $tax['label'] }} ({{ $tax['rate'] }}%)</span>
                    <span>${{ number_format($tax['tax'], 2) }}</span>
                </li>
            @endif
            <li class="flex justify-between gap-4 font-semibold text-gray-900 border-t border-gray-100 pt-1 mt-1">
                <span>Total</span>
                <span>${{ number_format($tax['total'], 2) }} / {{ $plan->billing_interval === 'yearly' ? 'yr' : 'mo' }}</span>
            </li>
        </ul>
    </div>

    {{--
        target="_top" — Stripe's hosted checkout page refuses to render
        inside an iframe (by design, for PCI reasons), so this always
        breaks out to the full browser tab.
    --}}
    <form method="POST" action="{{ route('checkout.confirm', $pending->token) }}" target="_top" class="mt-6">
        @csrf

        <div class="flex items-center gap-3">
            <x-primary-button type="submit">Proceed to Payment</x-primary-button>
            <a href="{{ route('pricing') }}" target="_top" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</div>

<div class="mt-4 space-y-2 text-xs leading-relaxed text-gray-500">
    <p class="font-semibold text-gray-600">Secure Payment Processing</p>
    <p>Runwrk is a product of Eformics Systems.</p>
    <p>You are now being redirected to Stripe, our trusted payment processing partner, to securely complete your purchase. Your payment information will be processed directly by Stripe using industry-standard security measures.</p>
    <p>By continuing, you acknowledge that you are leaving the Runwrk checkout environment and proceeding to Stripe to complete your payment.</p>
    <p>Continue to Stripe to complete your purchase.</p>
</div>
