<x-superadmin-layout title="Edit Rule">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Edit Rule</h2>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('superadmin.rules.update', $rule) }}">
            @csrf
            @method('PUT')

            @include('rules._form', ['products' => $products, 'initial' => $initial, 'rule' => $rule])

            <div class="flex items-center gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <x-primary-button>Save Changes</x-primary-button>
                <a href="{{ route('superadmin.rules.index', $business) }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>

        <form method="POST" action="{{ route('superadmin.rules.destroy', $rule) }}" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700"
            onsubmit="return confirm('Delete this rule?');">
            @csrf
            @method('DELETE')
            <x-danger-button>Delete Rule</x-danger-button>
        </form>
    </x-card>
</x-superadmin-layout>
