<x-superadmin-layout :title="'Edit '.$product->name">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <a href="{{ route('superadmin.products.index', $business) }}" class="hover:text-gray-700 dark:hover:text-gray-200">{{ $business->name }}</a> / Quote Builder
                </p>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Edit Product</h2>
            </div>
            @if ($product->published_snapshot)
                <x-secondary-button onclick="window.location='{{ route('superadmin.products.preview', [$business, $product]) }}'">
                    Preview
                </x-secondary-button>
            @endif
        </div>
    </x-slot>

    @if ($errors->has('display_conditions'))
        <div class="max-w-xl mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-semibold text-red-800">Can't publish yet</p>
            <p class="text-xs text-red-700 mt-0.5">{{ $errors->first('display_conditions') }}</p>
        </div>
    @endif

    <x-auth-session-status class="max-w-xl mb-4" :status="session('status')" />

    @if ($product->hasUnpublishedChanges())
        <div class="max-w-xl mb-4 flex items-center justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
            <div>
                <p class="text-sm font-semibold text-amber-800">Draft changes not yet published</p>
                <p class="text-xs text-amber-700 mt-0.5">
                    @if ($product->published_at)
                        The public quote builder still shows the version from {{ $product->published_at->diffForHumans() }}.
                    @else
                        This product has never been published — the public quote builder page doesn't exist yet.
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('superadmin.products.publish', [$business, $product]) }}">
                @csrf
                <x-primary-button>Publish</x-primary-button>
            </form>
        </div>
    @else
        <div class="max-w-xl mb-4 flex items-center justify-between gap-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3">
            <div>
                <p class="text-sm font-semibold text-green-800">Published — up to date</p>
                <p class="text-xs text-green-700 mt-0.5">
                    Published {{ $product->published_at->diffForHumans() }}.
                </p>
            </div>
        </div>
    @endif

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('superadmin.products.update', [$business, $product]) }}">
            @csrf
            @method('PUT')

            @include('products._form', ['product' => $product])

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Save Changes</x-primary-button>
                <a href="{{ route('superadmin.products.questions.index', [$business, $product]) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Manage Questions</a>
                <a href="{{ route('superadmin.products.index', $business) }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>

        <form method="POST" action="{{ route('superadmin.products.destroy', [$business, $product]) }}" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700"
            onsubmit="return confirm('Delete this product? This also deletes its questions and options.');">
            @csrf
            @method('DELETE')
            <x-danger-button>Delete Product</x-danger-button>
        </form>
    </x-card>
</x-superadmin-layout>
