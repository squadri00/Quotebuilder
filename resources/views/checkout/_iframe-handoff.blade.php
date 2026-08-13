<script>
    // Only fires when this page has been loaded inside someone else's
    // <iframe> — the normal case is a paid signup that happened inside an
    // embedded register form (see resources/views/register-embed.blade.php
    // and its "$selectedPlan present" branch, which deliberately submits
    // same-frame instead of target="_blank" so this redirect lands here).
    // Rather than rendering Laravel's own checkout page inside someone
    // else's iframe, this hands the token up to the parent page so *it*
    // can do a real top-level navigation to its own checkout wrapper page
    // (e.g. checkout.php) — mirroring the auto-resize handshake in
    // public/_embed-resize.blade.php, just for a redirect instead of a
    // height.
    (function () {
        if (window.self === window.top) {
            return;
        }

        window.parent.postMessage({
            type: 'quotebuilder:checkout-redirect',
            token: @js($pending->token),
        }, '*');
    })();
</script>
