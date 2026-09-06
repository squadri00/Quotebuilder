<x-affiliate-layout title="Profile" active="profile">
    @php $v = fn($k) => old($k, $partner->$k); @endphp

    <div class="mb-5 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-3 text-sm font-semibold">Contact &amp; payout details</h2>
        <form method="POST" action="{{ route('affiliate.portal.profile.update') }}">
            @csrf @method('PATCH')
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ([
                    'name' => 'Full name', 'phone' => 'Phone', 'company_name' => 'Company / trading name',
                    'tax_id' => 'Tax / business number', 'city' => 'City', 'state_province' => 'State / province',
                    'postal_code' => 'Postal code', 'payout_method' => 'Payout method',
                ] as $n => $l)
                    <div>
                        <label class="mb-1 block text-xs font-medium">{{ $l }}</label>
                        <input name="{{ $n }}" value="{{ $v($n) }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
                    </div>
                @endforeach
                <div>
                    <label class="mb-1 block text-xs font-medium">Email (contact support to change)</label>
                    <input value="{{ $partner->email }}" disabled class="w-full rounded-lg border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/60 text-sm text-slate-400 dark:text-slate-500">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Country</label>
                    <select name="country" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
                        <option value="">—</option>
                        @foreach ($countries as $c)<option value="{{ $c }}" @selected($v('country') === $c)>{{ $c }}</option>@endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium">Address</label>
                    <input name="address" value="{{ $v('address') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium">Payout details</label>
                    <input name="payout_details" value="{{ $v('payout_details') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
                </div>
            </div>
            <button class="mt-3 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Save profile</button>
        </form>
    </div>

    <div class="mb-5 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-2 text-sm font-semibold">Referral code &amp; commission</h2>
        <p class="text-sm">
            Code: <span class="rounded bg-orange-100 px-2 py-0.5 font-mono text-xs font-semibold text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">{{ $partner->partner_code }}</span> ·
            Rate: <strong>{{ rtrim(rtrim(number_format((float) $partner->commission_rate, 3), '0'), '.') }}%</strong> ·
            Joined {{ $partner->created_at->format('d/m/Y') }}
        </p>
        <div class="mt-2 rounded-lg bg-slate-100 p-3 dark:bg-slate-800 font-mono text-sm break-all">{{ $partner->referralUrl() }}</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-3 text-sm font-semibold">Change password</h2>
        <form method="POST" action="{{ route('affiliate.portal.profile.password') }}" class="max-w-sm space-y-3">
            @csrf @method('PATCH')
            <div>
                <label class="mb-1 block text-xs font-medium">Current password</label>
                <input type="password" name="current" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
                @error('current') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">New password</label>
                <input type="password" name="password" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">Confirm new password</label>
                <input type="password" name="password_confirmation" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
            </div>
            <button class="rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">Update password</button>
        </form>
    </div>
</x-affiliate-layout>
