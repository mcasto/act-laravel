<template>
  <q-layout view="hHh lpR fFf" class="bg-grey-2">
    <q-header elevated class="bg-secondary text-white">
      <q-toolbar>
        <img src="/director-icons/icon-192.png" alt="" class="director-logo q-mr-sm" />
        <q-toolbar-title class="text-weight-medium">ACT Director</q-toolbar-title>
        <q-btn
          v-if="installPrompt && !offline"
          flat
          dense
          no-caps
          :icon="matInstallMobile"
          label="Install"
          class="q-mr-xs"
          @click="install"
        />
        <q-btn v-if="paired" flat round dense :icon="matMoreVert" :disable="offline">
          <q-menu auto-close>
            <q-list style="min-width: 200px">
              <q-item-label header>{{ user?.name }}</q-item-label>
              <q-item clickable @click="confirmSignOut">
                <q-item-section avatar><q-icon :name="matLogout" /></q-item-section>
                <q-item-section>Disconnect this device</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </q-toolbar>
      <q-banner v-if="offline" dense class="bg-grey-9 text-white">
        <template #avatar><q-icon :name="matCloudOff" /></template>
        You're offline<template v-if="shownAtLabel">. Showing the last info received
        {{ shownAtLabel }}</template> — it may be out of date. This will update
        automatically when you're back online.
      </q-banner>
      <q-banner v-else-if="updateAvailable" dense class="bg-amber-2 text-black">
        A new version of this page is available.
        <template #action>
          <q-btn flat dense no-caps color="secondary" label="Reload" @click="reload" />
        </template>
      </q-banner>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { matCloudOff, matInstallMobile, matLogout, matMoreVert } from "@quasar/extras/material-icons";
import { Dialog } from "quasar";
import { computed, onBeforeUnmount, onMounted, provide, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { getToken, getUser, unpair } from "src/director/api";
import { offline, shownAt } from "src/director/state";

// "7:29 PM" today, otherwise "Nov 7, 7:29 PM".
const shownAtLabel = computed(() => {
  if (!shownAt.value) return "";
  const d = new Date(shownAt.value);
  const time = d.toLocaleTimeString([], { hour: "numeric", minute: "2-digit" });
  return d.toDateString() === new Date().toDateString()
    ? `at ${time}`
    : `on ${d.toLocaleDateString([], { month: "short", day: "numeric" })}, ${time}`;
});

const onOffline = () => (offline.value = true);
// Coming back online: pages watch `offline` and reload their data, which
// flips it back to false on the first successful response.
const onOnline = () => (offline.value = false);

const router = useRouter();
const route = useRoute();

// Re-read on every navigation so the menu appears right after pairing and
// disappears after a 401 clears the token.
const paired = computed(() => (route.fullPath, !!getToken()));
const user = computed(() => (route.fullPath, getUser()));

// ── Scoped PWA ───────────────────────────────────────────────────────────
// The manifest and theme tags only exist while a /director page is open,
// and the service worker's scope is /director, so installing applies to this
// section alone — never the public site or the admin.
const headTags = [];
const addHeadTag = (tag, attrs) => {
  const el = document.createElement(tag);
  Object.entries(attrs).forEach(([k, v]) => el.setAttribute(k, v));
  document.head.appendChild(el);
  headTags.push(el);
};

const installPrompt = ref(null);
const onBeforeInstallPrompt = (event) => {
  event.preventDefault();
  installPrompt.value = event;
};
const install = async () => {
  const prompt = installPrompt.value;
  installPrompt.value = null;
  await prompt.prompt();
};
provide("directorInstallPrompt", installPrompt);
provide("directorInstall", install);

// ── Update banner ────────────────────────────────────────────────────────
// An installed page can stay open for days. Every few minutes (and whenever
// it comes back to the foreground) compare the deployed index.html's entry
// script(s) with the ones this page loaded; a different build hash means a
// deploy happened, so offer a reload.
const updateAvailable = ref(false);
const entryScripts = (root) =>
  [...root.querySelectorAll('script[type="module"][src]')]
    .map((s) => new URL(s.getAttribute("src"), location.origin).pathname)
    .sort()
    .join("|");
const loadedBuild = entryScripts(document);

const checkForUpdate = async () => {
  if (updateAvailable.value || !loadedBuild) return;
  try {
    const html = await (await fetch("/director", { cache: "no-store" })).text();
    const deployed = entryScripts(new DOMParser().parseFromString(html, "text/html"));
    if (deployed && deployed !== loadedBuild) updateAvailable.value = true;
  } catch {
    // offline — try again next time
  }
};
const onVisible = () => document.visibilityState === "visible" && checkForUpdate();
let updateTimer = null;

const reload = () => location.reload();

// Hand the service worker the files this page has loaded so far (and the
// shell) so it can open offline — the first visit loads them before the
// worker takes control. Re-sent after each navigation for lazy-loaded pages.
const rememberLoadedFiles = () => {
  navigator.serviceWorker.ready
    .then((registration) => {
      const urls = performance
        .getEntriesByType("resource")
        .map((entry) => entry.name)
        .filter((name) => name.startsWith(location.origin));
      registration.active?.postMessage({ type: "cache-urls", urls: ["/director", ...urls] });
    })
    .catch(() => {});
};
const removeAfterEach = router.afterEach(
  () => "serviceWorker" in navigator && setTimeout(rememberLoadedFiles, 1500),
);

onMounted(() => {
  document.title = "ACT Director";
  addHeadTag("link", { rel: "manifest", href: "/director-manifest.json" });
  addHeadTag("meta", { name: "theme-color", content: "#2A2670" });
  addHeadTag("link", { rel: "apple-touch-icon", href: "/director-icons/icon-180.png" });
  addHeadTag("meta", { name: "apple-mobile-web-app-capable", content: "yes" });
  addHeadTag("meta", { name: "mobile-web-app-capable", content: "yes" });
  addHeadTag("meta", { name: "apple-mobile-web-app-title", content: "ACT Director" });

  if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("/director-sw.js", { scope: "/director" }).catch(() => {});
    rememberLoadedFiles();
  }
  window.addEventListener("offline", onOffline);
  window.addEventListener("online", onOnline);
  window.addEventListener("beforeinstallprompt", onBeforeInstallPrompt);

  updateTimer = setInterval(checkForUpdate, 5 * 60 * 1000);
  document.addEventListener("visibilitychange", onVisible);
});

onBeforeUnmount(() => {
  headTags.forEach((el) => el.remove());
  window.removeEventListener("beforeinstallprompt", onBeforeInstallPrompt);
  removeAfterEach();
  window.removeEventListener("offline", onOffline);
  window.removeEventListener("online", onOnline);
  clearInterval(updateTimer);
  document.removeEventListener("visibilitychange", onVisible);
});

const confirmSignOut = () => {
  Dialog.create({
    title: "Disconnect this device?",
    message:
      "You'll need a new setup code from the theater to see your show here again.",
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await unpair();
    } catch {
      // token already gone server-side — local copy is cleared either way
    }
    router.replace({ name: "director-setup" });
  });
};
</script>

<style scoped>
.director-logo {
  width: 32px;
  height: 32px;
  border-radius: 6px;
}
</style>
