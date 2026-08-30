<x-superadmin-layout title="Affiliate Program Settings">
    <x-slot name="header">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Referral Program Settings</h2>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('superadmin.affiliate.settings.update') }}" class="max-w-2xl space-y-4">
        @csrf @method('PATCH')

        <x-card>
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Program</h3>
            <div class="space-y-3 text-sm">
                <label class="flex items-start gap-2 text-gray-700 dark:text-gray-300"><input type="checkbox" name="affiliate_program_enabled" value="1" @checked($settings->affiliate_program_enabled) class="mt-0.5 rounded border-gray-300"> Program enabled — attribution, accrual and the partner portal are live</label>
                <label class="flex items-start gap-2 text-gray-700 dark:text-gray-300"><input type="checkbox" name="affiliate_auto_approve_partners" value="1" @checked($settings->affiliate_auto_approve_partners) class="mt-0.5 rounded border-gray-300"> Auto-activate new partner signups (skip manual review)</label>
                <label class="flex items-start gap-2 text-gray-700 dark:text-gray-300"><input type="checkbox" name="affiliate_auto_approve_referrals" value="1" @checked($settings->affiliate_auto_approve_referrals) class="mt-0.5 rounded border-gray-300"> Auto-approve referrals that arrive through a valid active link</label>
                <div>
                    <x-input-label value="Partner agreement URL" />
                    <x-text-input name="affiliate_terms_url" type="url" class="mt-1 block w-full" :value="$settings->affiliate_terms_url" placeholder="https://…/partner-agreement" />
                </div>
            </div>
        </x-card>

        <x-card>
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Commission</h3>
            <div class="space-y-3">
                <div><x-input-label value="Default commission rate (%)" /><x-text-input name="affiliate_default_commission_rate" type="number" step="0.001" class="mt-1 w-40" :value="$settings->affiliate_default_commission_rate" required /></div>
                <div>
                    <x-input-label value="Payout currency (blank = platform default)" />
                    <x-text-input name="affiliate_payout_currency" class="mt-1 w-28 uppercase" maxlength="3" :value="$settings->affiliate_payout_currency" placeholder="{{ $settings->default_currency ?? 'CAD' }}" />
                </div>
                <div><x-input-label value="Minimum payout" /><x-text-input name="affiliate_min_payout" type="number" step="0.01" class="mt-1 w-40" :value="$settings->affiliate_min_payout" required /></div>
            </div>
        </x-card>

        <x-card>
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Prospect claims (territory lock)</h3>
            <div class="space-y-3 text-sm">
                <div><x-input-label value="Claim lock duration (days)" /><x-text-input name="affiliate_claim_days" type="number" class="mt-1 w-40" :value="$settings->affiliate_claim_days" required /></div>
                <label class="flex items-start gap-2 text-gray-700 dark:text-gray-300"><input type="checkbox" name="affiliate_claim_renew_on_activity" value="1" @checked($settings->affiliate_claim_renew_on_activity) class="mt-0.5 rounded border-gray-300"> Editing a claim pushes its expiry forward</label>
                <div><x-input-label value="Attribution cookie window (days)" /><x-text-input name="affiliate_cookie_days" type="number" class="mt-1 w-40" :value="$settings->affiliate_cookie_days" required /></div>
            </div>
        </x-card>

        <x-primary-button>Save settings</x-primary-button>
    </form>
</x-superadmin-layout>
