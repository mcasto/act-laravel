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

      <!-- keep-alive so an unsaved audition edit survives switching tabs -->
      <q-tab-panels v-model="tab" animated keep-alive class="bg-transparent">
        <!-- Sales -->
        <q-tab-panel name="sales" class="q-pa-md">
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
                    <span class="text-h4 text-weight-bold">{{ p.seats_taken }}</span>
                    <span class="text-body1 text-grey-7 q-ml-xs">seats taken / {{ p.sold_out_target }} seat capacity</span>
                  </div>
                  <q-linear-progress
                    :value="p.sold_out_target ? Math.min(1, p.seats_taken / p.sold_out_target) : 0"
                    :color="p.sold_out ? 'negative' : 'secondary'"
                    track-color="grey-3"
                    size="10px"
                    rounded
                    class="q-my-sm"
                  />
                  <div class="text-body2">
                    <b>{{ p.remaining }}</b> seats remaining
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
          <director-audition-editor
            v-else-if="editingAudition"
            :show="show"
            :audition="audition"
            @saved="onAuditionSaved"
            @cancel="editingAudition = false"
          />
          <div v-else-if="!audition" class="text-center q-pa-lg">
            <div class="text-grey-7 q-mb-md">There's no audition notice for this show yet.</div>
            <q-btn
              color="secondary"
              unelevated
              no-caps
              :icon="matEdit"
              label="Create audition notice"
              :disable="offline || !show"
              @click="editingAudition = true"
            />
          </div>
          <template v-else>
            <q-card flat bordered class="q-mb-md">
              <q-card-section class="row items-center no-wrap text-body2">
                <div class="col">
                  <q-icon :name="matEvent" class="q-mr-xs" />
                  Shown on the website {{ formatDate(audition.display_date, "MMM d, yyyy") }} –
                  {{ formatDate(audition.end_display_date, "MMM d, yyyy") }}
                </div>
                <q-btn
                  color="secondary"
                  unelevated
                  no-caps
                  :icon="matEdit"
                  label="Edit"
                  :disable="offline || !show"
                  @click="editingAudition = true"
                />
              </q-card-section>
            </q-card>
            <q-card flat bordered>
              <!-- Same notice the public audition page shows. -->
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
  matEdit,
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
import { offline, shownAt } from "src/director/state";
import DirectorAuditionEditor from "src/components/DirectorAuditionEditor.vue";

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
const editingAudition = ref(false);

const onAuditionSaved = (result) => {
  audition.value = result.data.audition;
  auditionsAt.value = result.at;
  updateShownAt();
  editingAudition.value = false;
};

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
    shows.value = (await fetchShows()).data;
  } catch (e) {
    handleAuthError(e);
  }
};

// The offline banner's "last info received" time: the older of the two
// data sets on screen.
const salesAt = ref(null);
const auditionsAt = ref(null);
const updateShownAt = () => {
  const times = [salesAt.value, auditionsAt.value].filter(Boolean);
  shownAt.value = times.length ? Math.min(...times) : null;
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
    salesAt.value = result.at;
    salesUpdatedAt.value = timeLabel(result.at);
    updateShownAt();
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
    const result = await fetchAuditions(showId.value);
    audition.value = result.data.audition;
    auditionsError.value = "";
    auditionsLoaded.value = true;
    auditionsAt.value = result.at;
    updateShownAt();
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
    loadAuditions();
  }
};

// Back online: refresh both (this also clears the offline banner).
watch(offline, (isOffline) => {
  if (!isOffline && !blocked.value) {
    loadSales();
    loadAuditions();
  }
});

const loadAll = async () => {
  blocked.value = null;
  sales.value = null;
  auditionsLoaded.value = false;
  editingAudition.value = false;
  await loadShows();
  if (!getToken()) return;
  salesAt.value = auditionsAt.value = null;
  // Load both up front so the last-known copy of each is saved for offline.
  await Promise.all([loadSales(), loadAuditions()]);
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

<style scoped>
.performance-past {
  opacity: 0.6;
}
.audition-html :deep(img) {
  max-width: 100%;
  height: auto;
}
</style>
