@props(['plan' => null, 'features', 'selectedFeatureIds' => []])

<div>
    <x-input-label for="name" value="Name" />
    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" required autofocus
        :value="old('name', $plan?->name)" />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="grid grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="price" value="Price ($)" />
        <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="block mt-1 w-full" required
            :value="old('price', $plan?->price)" />
        <x-input-error :messages="$errors->get('price')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="billing_interval" value="Billing Interval" />
        <select id="billing_interval" name="billing_interval" required
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">
            @foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly'] as $value => $label)
                <option value="{{ $value }}" @selected(old('billing_interval', $plan?->billing_interval) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('billing_interval')" class="mt-2" />
    </div>
</div>

<div class="mt-6 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
    <label class="flex items-center gap-2">
        <input type="checkbox" name="show_discount" value="1" class="rounded border-gray-300 text-indigo-600 dark:text-indigo-400 shadow-sm focus:ring-indigo-500"
            @checked(old('show_discount', $plan?->show_discount ?? false))>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Show a sale price on the public pricing page</span>
    </label>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">When on, the pricing page shows the price below struck through, then the actual price below as the sale price — the savings shown are always calculated live from the two, so they can never end up out of sync. Doesn't affect what's actually charged.</p>

    <div class="grid grid-cols-2 gap-4 mt-3">
        <div>
            <x-input-label for="compare_at_price" value="Was Price ($)" />
            <x-text-input id="compare_at_price" name="compare_at_price" type="number" step="0.01" min="0" class="block mt-1 w-full"
                placeholder="e.g. 99.00" :value="old('compare_at_price', $plan?->compare_at_price)" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Must be higher than the real Price above.</p>
            <x-input-error :messages="$errors->get('compare_at_price')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="discount_display" value="Show Savings As" />
            <select id="discount_display" name="discount_display"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">
                <option value="fixed" @selected(old('discount_display', $plan?->discount_display ?? 'percentage') === 'fixed')>Dollar amount — e.g. "Save $20"</option>
                <option value="percentage" @selected(old('discount_display', $plan?->discount_display ?? 'percentage') === 'percentage')>Percentage — e.g. "20% off"</option>
            </select>
            <x-input-error :messages="$errors->get('discount_display')" class="mt-2" />
        </div>
    </div>
</div>

<div class="mt-4">
    <x-input-label for="stripe_price_id" value="Stripe Price ID" />
    <x-text-input id="stripe_price_id" name="stripe_price_id" type="text" class="block mt-1 w-full font-mono text-sm"
        placeholder="price_..." :value="old('stripe_price_id', $plan?->stripe_price_id)" />
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">The Stripe Price object this plan charges — from your Stripe Dashboard (Test mode) → Product catalog. Leave blank if this plan isn't sold through Stripe (e.g. a free or comped plan).</p>
    <x-input-error :messages="$errors->get('stripe_price_id')" class="mt-2" />
</div>

<div class="grid grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="max_products" value="Max Products" />
        <x-text-input id="max_products" name="max_products" type="number" min="0" class="block mt-1 w-full"
            placeholder="Unlimited" :value="old('max_products', $plan?->max_products)" />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank for unlimited.</p>
        <x-input-error :messages="$errors->get('max_products')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="max_quotes_per_month" value="Max Quotes / Month" />
        <x-text-input id="max_quotes_per_month" name="max_quotes_per_month" type="number" min="0" class="block mt-1 w-full"
            placeholder="Unlimited" :value="old('max_quotes_per_month', $plan?->max_quotes_per_month)" />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank for unlimited.</p>
        <x-input-error :messages="$errors->get('max_quotes_per_month')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="max_users" value="Max Users" />
        <x-text-input id="max_users" name="max_users" type="number" min="0" class="block mt-1 w-full"
            placeholder="Unlimited" :value="old('max_users', $plan?->max_users)" />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Owner + team members. Leave blank for unlimited.</p>
        <x-input-error :messages="$errors->get('max_users')" class="mt-2" />
    </div>
</div>


<div class="mt-4">
    <x-input-label for="marketing_bullets" value="Pricing Page Bullets" />
    <textarea id="marketing_bullets" name="marketing_bullets" rows="6"
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm font-mono"
        placeholder="1 product/calculator&#10;300 quotes/month&#10;Email notifications to business">{{ old('marketing_bullets', $plan?->marketing_bullets) }}</textarea>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">One per line — shown as the bullet list on the public /pricing page. Leave blank to show no bullets.</p>
    <x-input-error :messages="$errors->get('marketing_bullets')" class="mt-2" />
</div>

<div class="mt-4 space-y-2">
    <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 dark:text-indigo-400 shadow-sm focus:ring-indigo-500"
            @checked(old('is_active', $plan?->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-300">Active (available to assign to businesses)</span>
    </label>

    <label class="flex items-center gap-2">
        <input type="checkbox" name="is_highlighted" value="1" class="rounded border-gray-300 text-indigo-600 dark:text-indigo-400 shadow-sm focus:ring-indigo-500"
            @checked(old('is_highlighted', $plan?->is_highlighted ?? false))>
        <span class="text-sm text-gray-700 dark:text-gray-300">Highlight on pricing page (e.g. "Most Popular")</span>
    </label>
</div>

<div class="mt-6">
    <x-input-label value="Included Features" />
    @if ($features->isEmpty())
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            No features exist yet. <a href="{{ route('superadmin.features.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Create one first</a>.
        </p>
    @else
        <div class="mt-2 space-y-2 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            @foreach ($features as $feature)
                <label class="flex items-start gap-2">
                    <input type="checkbox" name="features[]" value="{{ $feature->id }}"
                        class="mt-0.5 rounded border-gray-300 text-indigo-600 dark:text-indigo-400 shadow-sm focus:ring-indigo-500"
                        @checked(in_array($feature->id, old('features', $selectedFeatureIds)))>
                    <span>
                        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ $feature->name }}</span>
                        @if ($feature->description)
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $feature->description }}</span>
                        @endif
                    </span>
                </label>
            @endforeach
        </div>
    @endif
</div>
