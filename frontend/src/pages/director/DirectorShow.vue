<template>
  <q-page>
    <!-- Access ended / not started / no access -->
    <div
      v-if="blocked"
      class="text-center q-pa-xl"
      style="max-width: 480px; margin: 0 auto"
    >
      <q-icon :name="matLockClock" size="64px" color="grey-5" />
      <div class="text-h6 q-mt-md q-mb-sm">{{ blocked.title }}</div>
      <div class="text-body2 text-grey-8 q-mb-lg">{{ blocked.message }}</div>
      <q-btn
        v-if="otherShows.length"
        color="secondary"
        no-caps
        unelevated
        label="Go to my other shows"
        @click="router.replace({ name: 'director-home' })"
      />
      <q-btn v-else flat color="secondary" no-caps label="Check again" @click="loadAll" />
    </div>

    <template v-else>
      <!-- Show header -->
      <div class="bg-white q-px-md q-py-sm row items-center no-wrap shadow-1">
        <q-btn
          v-if="otherShows.length"
          flat
          round
          dense
          :icon="matArrowBack"
          class="q-mr-sm"
          @click="router.push({ name: 'director-home' })"
        >
          <q-tooltip>My shows</q-tooltip>
        </q-btn>
        <q-img
          v-if="show?.poster_url"
          :src="show.poster_url"
          :ratio="2 / 3"
          fit="cover"
          class="rounded-borders q-mr-md"
          style="width: 56px; flex: none"
        />
        <div class="col">
          <div class="text-h6 ellipsis-2-lines" style="line-height: 1.25">
            {{ show?.name || "…" }}
          </div>
          <div class="text-caption text-grey-7">
            <span v-if="show">{{ performanceRange(show.first_performance, show.last_performance) }}</span>
            <span v-if="show?.role"> · {{ show.role }}</span>
          </div>
        </div>
      </div>

      <!-- Ticket sales -->
      <div class="q-pa-md">
        <div class="row items-center q-mb-md">
          <div class="text-caption text-grey-7">
            <template v-if="salesUpdatedAt">
              Updated {{ salesUpdatedAt }}<template v-if="!offline"> · refreshes every minute</template>
            </template>
          </div>
          <q-space />
          <q-btn
            flat
            dense
            no-caps
            color="secondary"
            :icon="matRefresh"
            label="Refresh"
            :loading="salesLoading"
            :disable="offline"
            @click="loadSales"
          />
        </div>

        <q-banner v-if="salesError" dense rounded class="bg-red-1 text-negative q-mb-md">
          {{ salesError }}
        </q-banner>

        <div v-if="!sales && salesLoading" class="text-center q-pa-xl">
          <q-spinner size="2.5em" color="secondary" />
        </div>
        <performance-sales-cards v-else-if="sales" :performances="sales.performances" />
      </div>
    </template>
  </q-page>
</template>

<script setup>
import { matArrowBack, matLockClock, matRefresh } from "@quasar/extras/material-icons";
import { Notify } from "quasar";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { fetchSales, fetchShows, getToken } from "src/director/api";
import { performanceRange } from "src/director/format";
import { offline, shownAt } from "src/director/state";
import PerformanceSalesCards from "src/components/PerformanceSalesCards.vue";

const REFRESH_MS = 60 * 1000;

const route = useRoute();
const router = useRouter();
const showId = computed(() => Number(route.params.id));

const shows = ref([]);
const show = computed(() => shows.value.find((s) => s.id === showId.value) || null);
const otherShows = computed(() => shows.value.filter((s) => s.id !== showId.value));

const blocked = ref(null); // { title, message }

const sales = ref(null);
const salesLoading = ref(false);
const salesError = ref("");
const salesUpdatedAt = ref("");

// Any 401 means this device was disconnected (revoked from the admin, or the
// person's account removed) — the API client has already dropped the token.
const handleAuthError = (e) => {
  if (e.status === 401) {
    Notify.create({ type: "warning", message: "This device was disconnected. Enter a new setup code." });
    router.replace({ name: "director-setup" });
    return true;
  }
  if (e.status === 403) {
    // The server's message names the show ("Your access to X ended on …"),
    // which matters here: an ended show is no longer in /director/shows.
    blocked.value =
      e.body.reason === "ended"
        ? { title: "Access ended", message: e.body.message + ". Thanks for all your work on the show!" }
        : e.body.reason === "not_started"
          ? { title: "Not open yet", message: e.body.message + "." }
          : { title: "No access", message: "You don't have access to this show." };
    stopTimer();
    return true;
  }
  return false;
};

const loadShows = async () => {
  try {
    shows.value = (await fetchShows()).data;
  } catch (e) {
    handleAuthError(e);
  }
};

const timeLabel = (ms) => {
  const d = new Date(ms);
  const time = d.toLocaleTimeString([], { hour: "numeric", minute: "2-digit" });
  return d.toDateString() === new Date().toDateString()
    ? time
    : `${d.toLocaleDateString([], { month: "short", day: "numeric" })}, ${time}`;
};

const loadSales = async () => {
  if (blocked.value) return;
  salesLoading.value = true;
  try {
    const result = await fetchSales(showId.value);
    sales.value = result.data;
    salesError.value = "";
    salesUpdatedAt.value = timeLabel(result.at);
    // The offline banner's "last info received" time.
    shownAt.value = result.at;
  } catch (e) {
    if (!handleAuthError(e)) salesError.value = e.message;
  } finally {
    salesLoading.value = false;
  }
};

// Sales refresh every minute while the page is visible, and immediately when
// it comes back to the foreground (phones suspend timers in the background).
let timer = null;
const startTimer = () => {
  stopTimer();
  timer = setInterval(() => document.visibilityState === "visible" && loadSales(), REFRESH_MS);
};
const stopTimer = () => {
  clearInterval(timer);
  timer = null;
};
const onVisible = () => {
  if (document.visibilityState === "visible" && !blocked.value) loadSales();
};

// Back online: refresh (this also clears the offline banner).
watch(offline, (isOffline) => {
  if (!isOffline && !blocked.value) loadSales();
});

const loadAll = async () => {
  blocked.value = null;
  sales.value = null;
  await loadShows();
  if (!getToken()) return;
  shownAt.value = null;
  await loadSales();
  if (!blocked.value) startTimer();
};

watch(showId, () => loadAll());

onMounted(() => {
  if (!getToken()) {
    router.replace({ name: "director-setup" });
    return;
  }
  loadAll();
  document.addEventListener("visibilitychange", onVisible);
});

onBeforeUnmount(() => {
  stopTimer();
  document.removeEventListener("visibilitychange", onVisible);
});
</script>
