<x-superadmin-layout title="Plans">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Plans</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.plans.create') }}'">New Plan</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($plans->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No plans yet.
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($plans as $plan)
                <x-card class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $plan->name }}</p>
                            @if ($plan->is_active)
                                <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Deactivated</span>
                            @endif
                            @if ($plan->stripe_price_id)
                                <span class="inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:text-indigo-400 font-mono">{{ $plan->stripe_price_id }}</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/30 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">No Stripe price</span>
                            @endif
                            @if ($plan->hasDiscount())
                                <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">
                                    On sale — {{ $plan->discount_display === 'fixed' ? '$'.number_format($plan->discountAmount(), 0).' off' : $plan->discountPercent().'% off' }}
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            ${{ number_format($plan->price, 2) }} / {{ $plan->billing_interval }}
                            @if ($plan->hasDiscount())
                                <span class="line-through text-gray-400 dark:text-gray-500">${{ number_format($plan->compare_at_price, 2) }}</span>
                            @endif
                            &middot; {{ $plan->features_count }} feature(s)
                            &middot; {{ $plan->businesses_count }} business(es) on this plan
                            &middot; {{ $plan->max_products === null ? 'unlimited' : $plan->max_products }} products
                            &middot; {{ $plan->max_quotes_per_month === null ? 'unlimited' : $plan->max_quotes_per_month }} quotes/mo
                            &middot; {{ $plan->max_users === null ? 'unlimited' : $plan->max_users }} users
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <form method="POST" action="{{ route('superadmin.plans.toggle-active', $plan) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        <a href="{{ route('superadmin.plans.edit', $plan) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Edit</a>
                        <form method="POST" action="{{ route('superadmin.plans.destroy', $plan) }}"
                            onsubmit="return confirm('Delete plan &quot;{{ $plan->name }}&quot;? Businesses on this plan will be left with no plan assigned.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Delete</button>
                        </form>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-superadmin-layout>
