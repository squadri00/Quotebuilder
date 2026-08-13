<script>
    // Only does anything when this page is loaded inside someone else's
    // <iframe> (i.e. embedded via embed.js) — reports this page's actual
    // content height to the parent page so the iframe can resize to fit,
    // with no inner scrollbar. See public/embed.js for the other half.
    (function () {
        if (window.self === window.top) {
            return;
        }

        function sendHeight() {
            // Deliberately document.body.scrollHeight, not
            // document.documentElement.scrollHeight: the root <html>
            // element's scrollHeight is floored to the iframe's own current
            // viewport height by spec, so once the parent applies an
            // inflated height, every later reading off documentElement
            // reports at least that same inflated number forever — a
            // one-way ratchet that can never shrink back down. body's
            // scrollHeight reflects only the actual rendered content,
            // independent of however tall the iframe currently is.
            window.parent.postMessage({
                type: 'quotebuilder:resize',
                height: document.body.scrollHeight,
            }, '*');
        }

        if (window.ResizeObserver) {
            new ResizeObserver(sendHeight).observe(document.body);
        } else {
            window.addEventListener('resize', sendHeight);
            setInterval(sendHeight, 500);
        }

        sendHeight();

        // A transient reading taken before webfonts/layout settle can be
        // briefly inflated; ResizeObserver only reports further *changes*,
        // so if nothing resizes again afterwards a bad early reading never
        // gets corrected. These re-measure once things have definitely
        // settled, overriding any earlier bad value.
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(sendHeight);
        }
        setTimeout(sendHeight, 300);
        setTimeout(sendHeight, 1000);
    })();
</script>
