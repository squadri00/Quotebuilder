<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Edit {{ $member->name }}</h2>
    </x-slot>

    <div
        class="max-w-xl"
        x-data="{
            grantAll: false,
            toggleGrantAll() {
                this.grantAll = !this.grantAll;
                document.querySelectorAll('input[name=\'product_ids[]\'], input[name=\'permissions[]\']').forEach(el => el.checked = this.grantAll);
            },
        }"
    >
        <x-card>
            <form method="POST" action="{{ route('team.update', $member) }}">
                @csrf
                @method('PATCH')

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $member->name }} &middot; {{ $member->email }}</p>

                <div class="flex items-center justify-between">
                    <x-input-label value="Access" />
                    <button type="button" @click="toggleGrantAll()" class="text-xs font-medium brand-text">
                        <span x-text="grantAll ? 'Clear all' : 'Grant all'"></span>
                    </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Team and Billing are always Owner-only and can never be granted.</p>

                <div class="mt-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Products</p>
                    @if ($products->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">This business has no products yet.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($products as $product)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id, old('product_ids', $grantedProductIds)))>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $product->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <x-input-error :messages="$errors->get('product_ids')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Other Access</p>
                    <div class="space-y-2">
                        @foreach (\App\Support\TeamPermissions::all() as $key => $permission)
                            <label class="flex items-start gap-2">
                                <input type="checkbox" name="permissions[]" value="{{ $key }}" class="mt-1" @checked(in_array($key, old('permissions', $member->permissions ?? [])))>
                                <span>
                                    <span class="block text-sm text-gray-700 dark:text-gray-300">{{ $permission['label'] }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $permission['description'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('permissions')" class="mt-2" />
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <x-primary-button type="submit">Save Changes</x-primary-button>
                    <a href="{{ route('team.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Cancel</a>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
