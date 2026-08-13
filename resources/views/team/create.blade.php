<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Invite Teammate</h2>
    </x-slot>

    <div
        class="max-w-xl"
        x-data="{
            allProducts: @js($products->pluck('id')->all()),
            allPermissions: @js(array_keys(\App\Support\TeamPermissions::all())),
            grantAll: false,
            toggleGrantAll() {
                this.grantAll = !this.grantAll;
                document.querySelectorAll('input[name=\'product_ids[]\'], input[name=\'permissions[]\']').forEach(el => el.checked = this.grantAll);
            },
        }"
    >
        <x-card>
            <form method="POST" action="{{ route('team.store') }}">
                @csrf

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name')" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" :value="old('email')" required />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">We'll send them a link to set their own password — you don't set one for them.</p>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <x-input-label value="Access" />
                    <button type="button" @click="toggleGrantAll()" class="text-xs font-medium brand-text">
                        <span x-text="grantAll ? 'Clear all' : 'Grant all'"></span>
                    </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Team and Billing are always Owner-only and can never be granted.</p>

                <div class="mt-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Products</p>
                    @if ($products->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">You don't have any products yet.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($products as $product)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id, old('product_ids', [])))>
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
                                <input type="checkbox" name="permissions[]" value="{{ $key }}" class="mt-1" @checked(in_array($key, old('permissions', [])))>
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
                    <x-primary-button type="submit">Send Invitation</x-primary-button>
                    <a href="{{ route('team.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Cancel</a>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
