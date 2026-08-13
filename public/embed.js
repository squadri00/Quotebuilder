/**
 * QuoteBuilder embeddable widget loader.
 *
 * Usage (paste this where the quote builder should appear on your site):
 *   <script src="https://YOUR-QUOTEBUILDER-DOMAIN/embed.js"
 *           data-business="your-business-slug"
 *           data-product="your-product-slug"></script>
 *
 * This script finds itself in the page, reads the business/product slugs
 * off its own <script> tag, and inserts an <iframe> pointing at the
 * public quote builder right where the tag is. It then listens for the
 * iframe telling it (via postMessage) how tall its content currently is,
 * and resizes the iframe to match — so there's never an inner scrollbar
 * and never awkward empty space, even as the wizard moves between steps.
 */
(function () {
    var thisScript = document.currentScript;

    if (! thisScript) {
        return;
    }

    var business = thisScript.getAttribute('data-business');
    var product = thisScript.getAttribute('data-product');

    if (! business || ! product) {
        console.error('QuoteBuilder embed: the <script> tag needs data-business and data-product attributes.');
        return;
    }

    // Work out where QuoteBuilder itself is from this script's own URL,
    // so the same embed.js works whether the app lives at a bare domain
    // or under a subfolder — nothing here is hard-coded to one install.
    var scriptUrl = new URL(thisScript.src, window.location.href);
    var quoteBuilderOrigin = scriptUrl.origin;
    var basePath = scriptUrl.pathname.replace(/\/embed\.js$/, '');
    var quoteUrl = quoteBuilderOrigin + basePath + '/quote/'
        + encodeURIComponent(business) + '/' + encodeURIComponent(product);

    var iframe = document.createElement('iframe');
    iframe.src = quoteUrl;
    iframe.title = 'Get a quote';
    iframe.style.width = '100%';
    iframe.style.maxWidth = '100%';
    iframe.style.border = 'none';
    iframe.style.display = 'block';
    iframe.style.height = '600px'; // replaced as soon as the first resize message arrives
    iframe.setAttribute('scrolling', 'no');
    iframe.setAttribute('frameborder', '0');

    thisScript.parentNode.insertBefore(iframe, thisScript.nextSibling);

    window.addEventListener('message', function (event) {
        // Only trust resize instructions that (a) come from QuoteBuilder's
        // own origin and (b) come from this exact iframe — not just any
        // frame that happens to share the origin, and not anything else
        // on the page pretending to be QuoteBuilder.
        if (event.origin !== quoteBuilderOrigin) {
            return;
        }

        if (event.source !== iframe.contentWindow) {
            return;
        }

        if (! event.data || event.data.type !== 'quotebuilder:resize') {
            return;
        }

        var height = parseInt(event.data.height, 10);

        // Clamped as a safety net against a runaway resize-feedback loop
        // (e.g. viewport-relative CSS inside the iframe reacting to the
        // height we just set) turning into an absurd iframe height that
        // pushes real content off-screen.
        if (height > 0) {
            iframe.style.height = Math.min(height, 4000) + 'px';
        }
    });
})();
