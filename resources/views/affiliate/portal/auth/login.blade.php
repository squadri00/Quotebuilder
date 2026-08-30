<x-affiliate-layout title="Partner sign in" :auth="false">
    <h2 class="mb-4 text-lg font-semibold">Partner sign in</h2>

    <form method="POST" action="{{ route('affiliate.portal.login.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-medium">Email</label>
            <input name="email" type="email" required autofocus value="{{ old('email') }}"
                   class="w-full rounded-lg border-slate-300 text-sm focus:border-orange-500 focus:ring-orange-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Password</label>
            <input name="password" type="password" required
                   class="w-full rounded-lg border-slate-300 text-sm focus:border-orange-500 focus:ring-orange-500">
        </div>
        <button class="w-full rounded-lg bg-orange-600 py-2.5 text-sm font-semibold text-white hover:bg-orange-700">
            Sign in
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-500">
        New partner? <a href="{{ route('affiliate.portal.register') }}" class="text-orange-600 hover:underline">Apply to join</a>
    </p>
</x-affiliate-layout>
