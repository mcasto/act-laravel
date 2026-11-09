// Service worker for the director page (registered with scope "/director"
// from DirectorLayout.vue — it controls nothing else on the site). It's
// network-only on purpose: sales numbers must always be live, so nothing is
// cached. It exists so browsers treat /director as an installable app.
self.addEventListener("install", () => self.skipWaiting());
self.addEventListener("activate", (event) => event.waitUntil(self.clients.claim()));
self.addEventListener("fetch", (event) => {
  if (event.request.mode !== "navigate") return; // let the browser handle everything else
  event.respondWith(
    fetch(event.request).catch(
      () =>
        new Response(
          '<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1">' +
            '<title>ACT Director</title><body style="font-family:sans-serif;text-align:center;padding:3em 1em">' +
            "<h2>You're offline</h2><p>Connect to the internet to see your show's numbers.</p>" +
            '<button onclick="location.reload()">Try again</button></body>',
          { headers: { "Content-Type": "text/html; charset=utf-8" } },
        ),
    ),
  );
});
