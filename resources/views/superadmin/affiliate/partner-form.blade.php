@php $editing = $partner->exists; $v = fn($k, $d = '') => old($k, $partner->$k ?? $d); @endphp
<x-superadmin-layout :title="$editing ? 'Edit partner' : 'New partner'">
    <x-slot name="header">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $editing ? 'Edit partner' : 'New referral partner' }}</h2>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST"
          action="{{ $editing ? route('superadmin.affiliate.partners.update', $partner) : route('superadmin.affiliate.partners.store') }}">
        @csrf
        @if ($editing) @method('PATCH') @endif

        <x-card class="mb-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label value="Full name *" />
                    <x-text-input name="name" class="mt-1 block w-full" :value="$v('name')" required />
                </div>
                <div>
                    <x-input-label :value="'Email' . ($editing ? ' (read-only)' : ' *')" />
                    <x-text-input name="email" type="email" class="mt-1 block w-full" :value="$v('email')"
                        :disabled="$editing" :required="! $editing" />
                </div>
                <div>
                    <x-input-label :value="$editing ? 'Reset password (optional)' : 'Password *'" />
                    <x-text-input :name="$editing ? 'new_password' : 'password'" type="text" class="mt-1 block w-full"
                        autocomplete="off" :required="! $editing" placeholder="{{ $editing ? 'leave blank to keep' : 'min 8 characters' }}" />
                </div>
                <div>
                    <x-input-label value="Commission rate %" />
                    <x-text-input name="commission_rate" type="number" step="0.001" class="mt-1 block w-full" :value="$v('commission_rate', 20)" required />
                </div>
                @unless ($editing)
                    <div>
                        <x-input-label value="Initial status" />
                        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-gray-100">
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                @endunless
                <div><x-input-label value="Phone" /><x-text-input name="phone" class="mt-1 block w-full" :value="$v('phone')" /></div>
                <div><x-input-label value="Company / trading name" /><x-text-input name="company_name" class="mt-1 block w-full" :value="$v('company_name')" /></div>
                <div><x-input-label value="Tax / business number" /><x-text-input name="tax_id" class="mt-1 block w-full" :value="$v('tax_id')" /></div>
                <div class="sm:col-span-2"><x-input-label value="Address" /><x-text-input name="address" class="mt-1 block w-full" :value="$v('address')" /></div>
                <div><x-input-label value="City" /><x-text-input name="city" class="mt-1 block w-full" :value="$v('city')" /></div>
                <div><x-input-label value="State / province" /><x-text-input name="state_province" class="mt-1 block w-full" :value="$v('state_province')" /></div>
                <div><x-input-label value="Postal code" /><x-text-input name="postal_code" class="mt-1 block w-full" :value="$v('postal_code')" /></div>
                <div>
                    <x-input-label value="Country" />
                    <select name="country" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-gray-100">
                        <option value="">—</option>
                        @foreach ($countries as $c)<option value="{{ $c }}" @selected($v('country') === $c)>{{ $c }}</option>@endforeach
                    </select>
                </div>
                <div><x-input-label value="Payout method" /><x-text-input name="payout_method" class="mt-1 block w-full" :value="$v('payout_method')" placeholder="paypal / bank / wise" /></div>
                <div><x-input-label value="Payout details" /><x-text-input name="payout_details" class="mt-1 block w-full" :value="$v('payout_details')" /></div>
                @if ($editing)
                    <div class="sm:col-span-2">
                        <x-input-label value="Internal notes (partner never sees these)" />
                        <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-gray-100">{{ $v('notes') }}</textarea>
                    </div>
                @endif
            </div>
        </x-card>

        <x-primary-button>{{ $editing ? 'Save changes' : 'Create partner' }}</x-primary-button>
        <a href="{{ route('superadmin.affiliate.index') }}" class="ml-2 text-sm text-gray-500 hover:underline">Cancel</a>
    </form>
</x-superadmin-layout>
