<x-superadmin-layout :title="'Preview — '.$product->name">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <a href="{{ route('superadmin.products.index', $business) }}" class="hover:text-gray-700 dark:hover:text-gray-200">{{ $business->name }}</a> / Quote Builder
                </p>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Preview — {{ $product->name }}</h2>
            </div>
            <a href="{{ route('superadmin.products.edit', [$business, $product]) }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Back to Product</a>
        </div>
    </x-slot>

    <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
            This is the exact wizard a customer would see, running live below — nothing here is a mockup.
        </p>

        <iframe
            id="preview-frame"
            src="{{ route('quote.show', [$business, $product]) }}{{ Auth::guard('admin')->user()->theme === 'dark' ? '?theme=dark' : '' }}"
            title="{{ $product->name }} preview"
            class="w-full rounded-lg border border-gray-100 dark:border-gray-700"
            style="height: 700px;"
            scrolling="no"
            frameborder="0"
        ></iframe>
    </div>

    <script>
        // Same auto-resize protocol as the public embed widget — see
        // public/embed.js and resources/views/public/_embed-resize.blade.php.
        // Trusted here without an origin check since the iframe always
        // points at this same app, never an external site.
        (function () {
            var frame = document.getElementById('preview-frame');

            window.addEventListener('message', function (event) {
                if (event.source !== frame.contentWindow) {
                    return;
                }

                if (! event.data || event.data.type !== 'quotebuilder:resize') {
                    return;
                }

                var height = parseInt(event.data.height, 10);

                // Clamped as a safety net against a runaway resize-feedback
                // loop (e.g. viewport-relative CSS inside the iframe reacting
                // to the height we just set) turning into an absurd iframe
                // height that pushes real content off-screen.
                if (height > 0) {
                    frame.style.height = Math.min(height, 4000) + 'px';
                }
            });

            // Keep the wizard in step with the admin panel's own dark/light
            // toggle (see layouts/superadmin.blade.php's toggleSuperAdminTheme)
            // — since the public pages render their theme server-side from a
            // ?theme= query param, catching up means reloading the iframe to
            // the same URL it's already on, just with that param swapped.
            var observer = new MutationObserver(function () {
                var isDark = document.documentElement.classList.contains('dark');
                var url = new URL(frame.contentWindow.location.href);

                if ((url.searchParams.get('theme') === 'dark') === isDark) {
                    return;
                }

                if (isDark) {
                    url.searchParams.set('theme', 'dark');
                } else {
                    url.searchParams.delete('theme');
                }

                frame.contentWindow.location.href = url.toString();
            });

            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        })();
    </script>
</x-superadmin-layout>
