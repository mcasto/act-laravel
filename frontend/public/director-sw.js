// Service worker for the director page, registered with scope "/director"
// from DirectorLayout.vue — it controls nothing else on the site.
//
// It keeps just enough to open the page with no connection: the page shell
// (the /director HTML) and the built, content-hashed files under /assets/.
// API responses are never cached here — the page keeps its own last-known
// copy of the show data (src/director/api.js) so it can label it as possibly
// out of date and wipe it when the device is disconnected.
const CACHE = "act-director-v1";
const SHELL = "/director";
const MAX_ENTRIES = 200;

// Static files worth keeping for offline use: built (content-hashed) assets,
// the page's icons, and show posters (public images, not show data).
const isCacheable = (pathname) =>
  pathname.startsWith("/assets/") ||
  pathname.startsWith("/director-icons/") ||
  pathname.startsWith("/api/storage/posters");

self.addEventListener("install", () => self.skipWaiting());

self.addEventListener("activate", (event) =>
  event.waitUntil(
    (async () => {
      for (const key of await caches.keys()) {
        if (key.startsWith("act-director-") && key !== CACHE) await caches.delete(key);
      }
      await self.clients.claim();
    })(),
  ),
);

const trim = async (cache) => {
  const keys = await cache.keys();
  for (const key of keys.slice(0, Math.max(0, keys.length - MAX_ENTRIES))) {
    if (new URL(key.url).pathname !== SHELL) await cache.delete(key);
  }
};

const offlinePage = () =>
  new Response(
    '<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1">' +
      '<title>ACT Director</title><body style="font-family:sans-serif;text-align:center;padding:3em 1em">' +
      "<h2>You're offline</h2><p>Connect to the internet to open ACT Director.</p>" +
      '<button onclick="location.reload()">Try again</button></body>',
    { headers: { "Content-Type": "text/html; charset=utf-8" } },
  );

// The first visit loads its files before this worker takes control, so the
// page posts the list of what it already loaded (plus the shell) for us to
// keep — otherwise the very first offline launch would have nothing to show.
self.addEventListener("message", (event) => {
  if (event.data?.type !== "cache-urls") return;
  event.waitUntil(
    (async () => {
      const cache = await caches.open(CACHE);
      for (const raw of event.data.urls || []) {
        const url = new URL(raw, self.location.origin);
        if (url.origin !== self.location.origin) continue;
        const isShell = url.pathname === SHELL;
        if (!isShell && !isCacheable(url.pathname)) continue;
        if (!isShell && (await cache.match(url.pathname))) continue;
        try {
          const response = await fetch(url.pathname, { cache: isShell ? "no-store" : "default" });
          if (response.ok) await cache.put(url.pathname, response);
        } catch {
          // offline right now — the next visit will try again
        }
      }
      await trim(cache);
    })(),
  );
});

self.addEventListener("fetch", (event) => {
  const request = event.request;
  if (request.method !== "GET") return;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;
  if (url.pathname.startsWith("/api/") && !isCacheable(url.pathname)) return; // API data: never

  // Page loads: network first (always the latest build when online), falling
  // back to the last shell we saw. Every /director/* URL is the same SPA shell.
  if (request.mode === "navigate") {
    event.respondWith(
      (async () => {
        const cache = await caches.open(CACHE);
        try {
          const response = await fetch(request);
          if (response.ok) await cache.put(SHELL, response.clone());
          return response;
        } catch {
          return (await cache.match(SHELL)) || offlinePage();
        }
      })(),
    );
    return;
  }

  if (!isCacheable(url.pathname)) return; // dev-server modules etc.: never cached

  // Built assets are content-hashed, so a cached copy is never stale: serve
  // it if we have it. Posters can be replaced under the same name (?t=…
  // changes), so for those use the network when we can and the copy offline.
  const isAsset = !url.pathname.startsWith("/api/");
  event.respondWith(
    (async () => {
      const cache = await caches.open(CACHE);
      const cached = await cache.match(request);
      if (cached && isAsset) return cached;
      try {
        const response = await fetch(request);
        if (response.ok) {
          // A failed cache write must never cost the page the file itself.
          cache
            .put(request, response.clone())
            .then(() => trim(cache))
            .catch(() => {});
        }
        return response;
      } catch (error) {
        if (cached) return cached;
        throw error;
      }
    })(),
  );
});
