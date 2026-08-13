<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Edit Product</h2>
    </x-slot>

    <x-auth-session-status class="max-w-xl mb-4" :status="session('status')" />

    @if ($errors->has('display_conditions'))
        <div class="max-w-xl mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-semibold text-red-800">Can't publish yet</p>
            <p class="text-xs text-red-700 mt-0.5">{{ $errors->first('display_conditions') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="max-w-xl mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </div>
    @endif

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
            <form method="POST" action="{{ route('products.publish', $product) }}">
                @csrf
                <x-primary-button>Publish</x-primary-button>
            </form>
        </div>
    @else
        <div class="max-w-xl mb-4 flex items-center justify-between gap-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3">
            <div>
                <p class="text-sm font-semibold text-green-800">Published — up to date</p>
                <p class="text-xs text-green-700 mt-0.5">
                    The public quote builder matches this exactly, published {{ $product->published_at->diffForHumans() }}.
                </p>
            </div>
        </div>
    @endif

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('products.update', $product) }}">
            @csrf
            @method('PUT')

            @include('products._form', ['product' => $product])

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Save Changes</x-primary-button>
                <a href="{{ route('products.questions.index', $product) }}" class="text-sm font-medium brand-text">Manage Questions</a>
                <a href="{{ route('products.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700">Cancel</a>
            </div>
        </form>

        <form method="POST" action="{{ route('products.destroy', $product) }}" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700"
            onsubmit="return confirm('Delete this product? This also deletes its questions and options.');">
            @csrf
            @method('DELETE')
            <x-danger-button>Delete Product</x-danger-button>
        </form>
    </x-card>

    <x-card class="max-w-xl mt-6">
        <p class="font-semibold text-gray-900 dark:text-gray-100">Product Images</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Shown on the side of the quote form for both staff and customers. Up to 5 images.</p>

        @if ($product->images->isNotEmpty())
            <div class="mt-4 grid grid-cols-3 sm:grid-cols-5 gap-3">
                @foreach ($product->images as $image)
                    <div class="relative group">
                        <img src="{{ $image->url }}" alt="" class="w-full aspect-square object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                        <form method="POST" action="{{ route('products.images.destroy', [$product, $image]) }}"
                            class="absolute top-1 right-1" onsubmit="return confirm('Remove this image?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="flex items-center justify-center h-6 w-6 rounded-full bg-white/90 dark:bg-gray-900/90 text-gray-600 dark:text-gray-300 hover:text-red-600 shadow"
                                title="Remove image">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($product->images->count() < 5)
            <form method="POST" action="{{ route('products.images.store', $product) }}" enctype="multipart/form-data" class="mt-4">
                @csrf
                <input type="file" name="images[]" accept="image/*" multiple
                    class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-100 dark:file:bg-gray-700 file:text-gray-700 dark:file:text-gray-200 hover:file:bg-gray-200 dark:hover:file:bg-gray-600">
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ 5 - $product->images->count() }} slot{{ 5 - $product->images->count() === 1 ? '' : 's' }} remaining &middot; JPG/PNG, up to 4MB each.</p>
                <x-input-error :messages="$errors->get('images')" class="mt-2" />
                <x-input-error :messages="$errors->get('images.*')" class="mt-2" />
                <x-primary-button type="submit" class="mt-3">Upload</x-primary-button>
            </form>
        @else
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">Maximum reached — remove an image above to upload a different one.</p>
        @endif

        @if (! $product->images->isEmpty() && $product->hasUnpublishedChanges())
            <p class="mt-4 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                Publish this product (above) to make image changes visible on the public and internal quote builders.
            </p>
        @endif
    </x-card>

    <x-card class="max-w-xl mt-6">
        <div x-data="{ open: false, copied: false }">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Embed on your website</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Paste this on your own site to show this quote builder there.</p>
                </div>
                <x-secondary-button type="button" @click="open = !open" x-text="open ? 'Hide' : 'Get Embed Code'">Get Embed Code</x-secondary-button>
            </div>

            <div x-show="open" x-cloak class="mt-4">
                @unless ($product->published_snapshot)
                    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-3">
                        This product hasn't been published yet — publish it first, or the embedded widget won't have anything to show.
                    </p>
                @endunless

                <textarea x-ref="snippet" readonly rows="2" @click="$event.target.select()"
                    class="w-full font-mono text-xs border-gray-300 rounded-lg shadow-sm bg-gray-50 dark:bg-gray-900/50 dark:border-gray-600 dark:text-gray-300 resize-none">{{ '<script src="'.url('/embed.js').'" data-business="'.$product->business->slug.'" data-product="'.$product->slug.'"></script>' }}</textarea>

                <button type="button"
                    @click="navigator.clipboard.writeText($refs.snippet.value); copied = true; setTimeout(() => copied = false, 2000)"
                    class="mt-2 inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <span x-text="copied ? 'Copied!' : 'Copy to Clipboard'">Copy to Clipboard</span>
                </button>
            </div>
        </div>
    </x-card>

    <x-card class="max-w-xl mt-6">
        <div x-data="{ open: false }">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">QR code for print &amp; signage</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Scan to land straight on this product's quote page — no website required.</p>
                </div>
                <x-secondary-button type="button" @click="open = !open" x-text="open ? 'Hide' : 'Get QR Code'">Get QR Code</x-secondary-button>
            </div>

            <div x-show="open" x-cloak class="mt-4">
                @unless ($product->published_snapshot)
                    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-3">
                        This product hasn't been published yet — publish it first, or the page the QR code links to won't have anything to show.
                    </p>
                @endunless

                <div class="flex items-start gap-4">
                    <img src="{{ route('products.qrcode', $product) }}" alt="QR code linking to this product's quote page" class="h-32 w-32 rounded-lg border border-gray-200 dark:border-gray-700 bg-white p-1">
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500 dark:text-gray-400 break-all">{{ route('quote.show', [$product->business, $product]) }}</p>
                        <a href="{{ route('products.qrcode', ['product' => $product, 'download' => 1]) }}"
                            class="mt-2 inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Download QR Code
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </x-card>
</x-app-layout>
