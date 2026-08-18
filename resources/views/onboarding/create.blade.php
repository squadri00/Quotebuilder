<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Setup Guide</h2>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <p class="max-w-xl mb-4 text-sm text-gray-500 dark:text-gray-400">
        A few things to set up before you send your first real quote. Come back to this page any time from the sidebar — it always reflects where your account actually stands.
    </p>

    <div class="max-w-xl mb-6">
        <div class="flex items-center justify-between mb-1">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $progress['completed'] }} of {{ $progress['total'] }} steps complete</span>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $progress['percent'] }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
            <div class="h-2 rounded-full brand-bg" style="width: {{ $progress['percent'] }}%"></div>
        </div>
        @if ($progress['allDone'])
            <p class="mt-2 text-sm font-medium text-green-700 dark:text-green-400">You're all set — everything a quote needs is in place.</p>
        @endif
    </div>

    <div class="max-w-xl space-y-4">
        @foreach ($steps as $step)
            <x-card class="{{ $step['done'] ? 'border-green-200 dark:border-green-900' : '' }}">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 mt-0.5">
                        @if ($step['done'])
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                        @else
                            <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-gray-300 dark:border-gray-600"></span>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">
                            {{ $step['label'] }}
                            @if ($step['optional'])
                                <span class="ml-1 text-xs font-normal text-gray-400 dark:text-gray-500">(optional)</span>
                            @endif
                        </p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $step['description'] }}</p>

                        @if ($step['key'] === 'industry')
                            @if ($step['done'])
                                <a href="{{ route('templates.index') }}" class="mt-2 inline-block text-sm font-medium brand-text">Browse more starter templates →</a>
                            @else
                                <div class="mt-4"
                                    x-data="{
                                        industryId: '{{ old('industry_id', '') }}',
                                        selectedProductIds: {{ Js::from(collect(old('product_ids', []))->map(fn ($id) => (string) $id)->values()) }},
                                        templates: {{ Js::from($templates->map(fn ($t) => [
                                            'id' => (string) $t->id,
                                            'industry_id' => $t->industry_id,
                                            'products' => $t->products->map(fn ($p) => [
                                                'id' => (string) $p->id,
                                                'name' => $p->name,
                                                'base_price' => $p->base_price,
                                            ]),
                                        ])) }},
                                        planLimit: {{ Js::from($business->plan?->max_products) }},
                                        get visibleProducts() {
                                            if (! this.industryId) return [];
                                            return this.templates
                                                .filter(t => String(t.industry_id) === String(this.industryId))
                                                .flatMap(t => t.products);
                                        },
                                        get totalProducts() {
                                            return this.selectedProductIds.length;
                                        },
                                        get exceedsLimit() {
                                            return this.planLimit !== null && this.totalProducts > this.planLimit;
                                        },
                                        toggle(id) {
                                            const i = this.selectedProductIds.indexOf(id);
                                            if (i === -1) this.selectedProductIds.push(id); else this.selectedProductIds.splice(i, 1);
                                        },
                                    }"
                                    x-effect="selectedProductIds = selectedProductIds.filter(id => visibleProducts.some(p => p.id === id))"
                                >
                                    <form method="POST" action="{{ route('onboarding.store') }}">
                                        @csrf

                                        <x-input-label for="industry_id" :value="__('Industry')" />
                                        <select id="industry_id" name="industry_id" x-model="industryId" required
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                            <option value="">— Select an industry —</option>
                                            @foreach ($industries as $industry)
                                                <option value="{{ $industry->id }}">{{ $industry->name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('industry_id')" class="mt-2" />

                                        <div class="mt-4" x-show="industryId" x-cloak>
                                            <x-input-label value="Starting Products" />

                                            <div class="mt-1 space-y-1 border border-gray-200 dark:border-gray-700 rounded-lg p-3" x-show="visibleProducts.length > 0">
                                                <template x-for="product in visibleProducts" :key="product.id">
                                                    <label class="flex items-center justify-between gap-2">
                                                        <span class="flex items-center gap-2">
                                                            <input type="checkbox" name="product_ids[]" :value="product.id"
                                                                :checked="selectedProductIds.includes(product.id)"
                                                                @change="toggle(product.id)"
                                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                            <span class="text-sm text-gray-700 dark:text-gray-300" x-text="product.name"></span>
                                                        </span>
                                                        <span class="text-xs text-gray-400 whitespace-nowrap" x-text="'$' + Number(product.base_price).toFixed(2)"></span>
                                                    </label>
                                                </template>
                                            </div>

                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="visibleProducts.length === 0" x-cloak>
                                                No starting products for this industry yet — you can still continue with a blank account.
                                            </p>

                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-show="visibleProducts.length > 0">Optional — pick as many as you like, or leave them unchecked to start from scratch.</p>

                                            <p class="mt-2 text-xs" x-show="selectedProductIds.length > 0" x-cloak>
                                                <span :class="exceedsLimit ? 'text-amber-700 dark:text-amber-400 font-medium' : 'text-gray-500 dark:text-gray-400'">
                                                    <span x-text="totalProducts"></span> product<span x-show="totalProducts !== 1">s</span> total from your selection<template x-if="planLimit !== null"><span> — your plan allows <span x-text="planLimit"></span></span></template>.
                                                    <span x-show="exceedsLimit">You can still continue; just trim products or upgrade your plan afterward.</span>
                                                </span>
                                            </p>

                                            <x-input-error :messages="$errors->get('product_ids')" class="mt-2" />
                                        </div>

                                        <div class="flex items-center justify-end gap-3 mt-4">
                                            <button type="submit" formaction="{{ route('onboarding.dismiss') }}" formnovalidate
                                                class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                Skip — I'll start from scratch
                                            </button>

                                            <x-primary-button>Continue</x-primary-button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        @elseif ($step['url'])
                            <a href="{{ $step['url'] }}" class="mt-2 inline-block text-sm font-medium brand-text">{{ $step['cta'] }} →</a>
                        @endif
                    </div>
                </div>
            </x-card>
        @endforeach
    </div>
</x-app-layout>
