<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                <a href="{{ route('profile.edit') }}" class="hover:text-gray-700">Business Settings</a> /
                <a href="{{ route('tax-rates.index') }}" class="hover:text-gray-700">Tax Rates</a>
            </p>
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Edit Tax Line</h2>
        </div>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('tax-rates.update', $taxRate) }}">
            @csrf
            @method('PUT')

            @include('tax-rates._form', ['taxRate' => $taxRate])

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Save Changes</x-primary-button>
                <a href="{{ route('tax-rates.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </x-card>

    <x-card class="max-w-xl mt-6">
        <form method="POST" action="{{ route('tax-rates.destroy', $taxRate) }}"
            onsubmit="return confirm('Delete this tax line? Past quotes already keep their own record of the tax that applied at the time — only future quotes are affected.');">
            @csrf
            @method('DELETE')
            <x-danger-button>Delete Tax Line</x-danger-button>
        </form>
    </x-card>
</x-app-layout>
