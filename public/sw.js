// Quotaire PWA service worker — shared by the business, superadmin and
// affiliate portal scopes (each registers it with its own `scope` option).
//
// Deliberately does NOT cache pages or API responses: every screen here
// shows live, per-business or per-partner data, and serving a stale cached
// copy of quotes/commissions/settings would be actively wrong. Its only job
// is to satisfy the browser's install criteria (a fetch handler must exist)
// and give a clear message if you're genuinely offline.

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request).catch(() => {
            if (event.request.mode === 'navigate') {
                return new Response(
                    '<!doctype html><meta charset="utf-8"><title>Offline</title>'
                    + '<body style="font-family:sans-serif;padding:2rem;text-align:center;color:#475569">'
                    + '<h1>You’re offline</h1><p>Quotaire needs a connection — reconnect and try again.</p></body>',
                    { status: 503, headers: { 'Content-Type': 'text/html' } },
                );
            }
            return Response.error();
        }),
    );
});
