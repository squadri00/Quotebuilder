<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('General QR Code') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('For general marketing material — business cards, a storefront sticker, a poster. Scanning it lets the customer pick which product they want a quote for. (Product-specific QR codes, which skip straight to one product, are on each product\'s edit page.)') }}
        </p>
    </header>

    @php
        $publishedProductCount = $business->products()
            ->where('is_active', true)
            ->whereNotNull('published_snapshot')
            ->count();
    @endphp

    @if ($publishedProductCount === 0)
        <p class="mt-3 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            {{ __('No products are published yet, so this QR code would land on an empty page. Publish at least one product first.') }}
        </p>
    @endif

    <div class="mt-4 flex items-start gap-4">
        <img src="{{ route('business.qrcode') }}" alt="QR code linking to your quote picker page" class="h-32 w-32 rounded-lg border border-gray-200 dark:border-gray-700 bg-white p-1">
        <div class="min-w-0">
            <p class="text-xs text-gray-500 dark:text-gray-400 break-all">{{ route('quote.picker', $business) }}</p>
            <a href="{{ route('business.qrcode', ['download' => 1]) }}"
                class="mt-2 inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                {{ __('Download QR Code') }}
            </a>
        </div>
    </div>
</section>
