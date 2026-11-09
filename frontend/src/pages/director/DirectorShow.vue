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
      <!-- Persistent show header (every tab) -->
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

      <q-tabs
        v-model="tab"
        dense
        align="justify"
        class="bg-white text-grey-8"
        active-color="secondary"
        indicator-color="secondary"
        narrow-indicator
      >
        <q-tab name="sales" :icon="matConfirmationNumber" label="Ticket sales" no-caps />
        <q-tab name="auditions" :icon="matRecordVoiceOver" label="Auditions" no-caps />
      </q-tabs>
      <q-separator />

      <q-tab-panels v-model="tab" animated class="bg-transparent">
        <!-- Sales -->
        <q-tab-panel name="sales" class="q-pa-md">
          <div class="row items-center q-mb-md">
            <div class="text-caption text-grey-7">
              <template v-if="salesUpdatedAt">
                Updated {{ salesUpdatedAt }} · refreshes every minute
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
              @click="loadSales"
            />
          </div>

          <q-banner v-if="salesError" dense rounded class="bg-red-1 text-negative q-mb-md">
            {{ salesError }}
          </q-banner>

          <div v-if="!sales && salesLoading" class="text-center q-pa-xl">
            <q-spinner size="2.5em" color="secondary" />
          </div>
          <div v-else-if="sales && sales.performances.length === 0" class="text-grey-7 text-center q-pa-lg">
            No performances are scheduled yet.
          </div>

          <div v-else-if="sales" class="row q-col-gutter-md">
            <div
              v-for="p in sales.performances"
              :key="p.id"
              class="col-12 col-sm-6 col-md-4"
            >
              <q-card flat bordered :class="{ 'performance-past': isPast(p) }">
                <q-card-section class="q-pb-sm">
                  <div class="row items-start no-wrap">
                    <div class="col">
                      <div class="text-subtitle1 text-weight-medium">{{ formatDate(p.date) }}</div>
                      <div class="text-caption text-grey-7">{{ formatTime(p.start_time) }}</div>
                    </div>
                    <q-badge v-if="p.sold_out" color="negative" class="text-weight-bold q-pa-sm">
                      SOLD OUT
                    </q-badge>
                    <q-badge v-else-if="isPast(p)" color="grey-6" class="q-pa-xs">Past</q-badge>
                  </div>
                </q-card-section>
                <q-card-section class="q-pt-none">
                  <div class="row items-baseline">
                    <span class="text-h4 text-weight-bold">{{ p.reserved }}</span>
                    <span class="text-body1 text-grey-7 q-ml-xs">/ {{ p.sold_out_target }} seats</span>
                  </div>
                  <q-linear-progress
                    :value="p.sold_out_target ? Math.min(1, p.reserved / p.sold_out_target) : 0"
                    :color="p.sold_out ? 'negative' : 'secondary'"
                    track-color="grey-3"
                    size="10px"
                    rounded
                    class="q-my-sm"
                  />
                  <div class="row text-body2">
                    <div class="col">
                      <b>{{ p.remaining }}</b> remaining
                    </div>
                    <div class="col text-right text-grey-7">
                      incl. {{ p.comps }} comp{{ p.comps === 1 ? "" : "s" }}
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>
        </q-tab-panel>

        <!-- Auditions -->
        <q-tab-panel name="auditions" class="q-pa-md">
          <div v-if="auditionsLoading && !auditionsLoaded" class="text-center q-pa-xl">
            <q-spinner size="2.5em" color="secondary" />
          </div>
          <q-banner v-else-if="auditionsError" dense rounded class="bg-red-1 text-negative">
            {{ auditionsError }}
          </q-banner>
          <div v-else-if="!audition" class="text-grey-7 text-center q-pa-lg">
            There's no audition notice for this show.
          </div>
          <template v-else>
            <q-card flat bordered class="q-mb-md">
              <q-card-section class="text-body2">
                <q-icon :name="matEvent" class="q-mr-xs" />
                Shown on the website {{ formatDate(audition.display_date, "MMM d, yyyy") }} –
                {{ formatDate(audition.end_display_date, "MMM d, yyyy") }}
              </q-card-section>
            </q-card>
            <q-card flat bordered>
              <!-- Same admin-authored notice the public audition page shows. -->
              <q-card-section class="audition-html" v-html="audition.html || ''" />
            </q-card>
          </template>
        </q-tab-panel>
      </q-tab-panels>
    </template>
  </q-page>
</template>

<script setup>
import {
  matArrowBack,
  matConfirmationNumber,
  matEvent,
  matLockClock,
  matRecordVoiceOver,
  matRefresh,
} from "@quasar/extras/material-icons";
import { Notify } from "quasar";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { fetchAuditions, fetchSales, fetchShows, getToken } from "src/director/api";
import { formatDate, formatTime, localToday, performanceRange } from "src/director/format";

const REFRESH_MS = 60 * 1000;

const route = useRoute();
const router = useRouter();
const showId = computed(() => Number(route.params.id));

const tab = ref(route.query.tab === "auditions" ? "auditions" : "sales");
watch(tab, (t) => router.replace({ query: t === "sales" ? {} : { tab: t } }));

const shows = ref([]);
const show = computed(() => shows.value.find((s) => s.id === showId.value) || null);
const otherShows = computed(() => shows.value.filter((s) => s.id !== showId.value));

const blocked = ref(null); // { title, message }

const sales = ref(null);
const salesLoading = ref(false);
const salesError = ref("");
const salesUpdatedAt = ref("");

const audition = ref(null);
const auditionsLoading = ref(false);
const auditionsLoaded = ref(false);
const auditionsError = ref("");

const isPast = (p) => p.date < localToday();

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
    shows.value = await fetchShows();
  } catch (e) {
    handleAuthError(e);
  }
};

const loadSales = async () => {
  if (blocked.value) return;
  salesLoading.value = true;
  try {
    sales.value = await fetchSales(showId.value);
    salesError.value = "";
    salesUpdatedAt.value = new Date().toLocaleTimeString([], { hour: "numeric", minute: "2-digit" });
  } catch (e) {
    if (!handleAuthError(e)) salesError.value = e.message;
  } finally {
    salesLoading.value = false;
  }
};

const loadAuditions = async () => {
  if (blocked.value) return;
  auditionsLoading.value = true;
  try {
    audition.value = (await fetchAuditions(showId.value)).audition;
    auditionsError.value = "";
    auditionsLoaded.value = true;
  } catch (e) {
    if (!handleAuthError(e)) auditionsError.value = e.message;
  } finally {
    auditionsLoading.value = false;
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
  if (document.visibilityState === "visible" && !blocked.value) {
    loadSales();
    if (tab.value === "auditions") loadAuditions();
  }
};

const loadAll = async () => {
  blocked.value = null;
  sales.value = null;
  auditionsLoaded.value = false;
  await loadShows();
  if (!getToken()) return;
  await loadSales();
  if (tab.value === "auditions") loadAuditions();
  if (!blocked.value) startTimer();
};

watch(tab, (t) => t === "auditions" && !auditionsLoaded.value && loadAuditions());
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

<style scoped>
.performance-past {
  opacity: 0.6;
}
.audition-html :deep(img) {
  max-width: 100%;
  height: auto;
}
</style>
