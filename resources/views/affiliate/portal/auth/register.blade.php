<x-affiliate-layout title="Become a partner" :auth="false">
    <h2 class="mb-1 text-lg font-semibold">Become a referral partner</h2>
    <p class="mb-4 text-sm text-slate-500">Earn recurring commission for every business you bring to {{ \App\Models\PlatformSetting::get()->platform_name ?: config('app.name') }}.</p>

    <form method="POST" action="{{ route('affiliate.portal.register.store') }}" class="space-y-3">
        @csrf
        @php
            $field = fn($name, $label, $type = 'text', $required = false) => null;
        @endphp
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            @foreach ([
                ['name', 'Full name', 'text', true],
                ['email', 'Email', 'email', true],
                ['password', 'Password', 'password', true],
                ['password_confirmation', 'Confirm password', 'password', true],
                ['phone', 'Phone', 'text', false],
                ['company_name', 'Company / trading name', 'text', false],
                ['tax_id', 'Tax / business number', 'text', false],
                ['city', 'City', 'text', false],
                ['state_province', 'State / province', 'text', false],
                ['postal_code', 'Postal code', 'text', false],
                ['payout_method', 'Preferred payout method', 'text', false],
            ] as [$n, $l, $t, $req])
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ $l }} @if($req)<span class="text-red-500">*</span>@endif</label>
                    <input name="{{ $n }}" type="{{ $t }}" @if($req) required @endif value="{{ $t === 'password' ? '' : old($n) }}"
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-orange-500 focus:ring-orange-500">
                    @error($n) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium">Address</label>
                <input name="address" value="{{ old('address') }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-orange-500 focus:ring-orange-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Country</label>
                <select name="country" class="w-full rounded-lg border-slate-300 text-sm focus:border-orange-500 focus:ring-orange-500">
                    <option value="">—</option>
                    @foreach ($countries as $c)
                        <option value="{{ $c }}" @selected(old('country') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium">Payout details</label>
                <input name="payout_details" value="{{ old('payout_details') }}" placeholder="PayPal email / IBAN / etc."
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-orange-500 focus:ring-orange-500">
            </div>
        </div>

        <label class="flex items-start gap-2 pt-2 text-sm">
            <input type="checkbox" name="agree" value="1" required class="mt-0.5 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
            <span>I have read and accept the partner agreement @if($termsUrl)(<a href="{{ $termsUrl }}" target="_blank" class="text-orange-600 underline">read</a>) @endif.</span>
        </label>
        @error('agree') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <button class="rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-700">Submit application</button>
        <a href="{{ route('affiliate.portal.login') }}" class="ml-2 text-sm text-slate-500 hover:underline">Sign in instead</a>
    </form>
</x-affiliate-layout>
