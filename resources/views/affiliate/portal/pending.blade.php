<x-affiliate-layout title="Awaiting approval" :auth="false">
    <h2 class="mb-2 text-lg font-semibold">Your account is under review</h2>
    <p class="mb-4 text-sm text-slate-500">
        Thanks for joining, {{ $partner->name }}. A team member will approve your account shortly —
        you'll get an email when it's active.
    </p>
    <p class="mb-2 text-sm">You can already share your referral link; signups are credited once you're approved:</p>
    <div class="rounded-lg bg-slate-100 p-3 font-mono text-sm break-all">{{ $partner->referralUrl() }}</div>
    <form method="POST" action="{{ route('affiliate.portal.logout') }}" class="mt-4">@csrf
        <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Sign out</button>
    </form>
</x-affiliate-layout>
