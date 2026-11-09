<template>
  <q-page class="q-pa-md">
    <div v-if="loading" class="text-center q-pa-xl">
      <q-spinner size="3em" color="secondary" />
    </div>

    <div v-else-if="error" class="text-center q-pa-xl" style="max-width: 460px; margin: 0 auto">
      <div class="text-h6 q-mb-sm">Couldn't load your shows</div>
      <div class="text-body2 text-grey-8 q-mb-md">{{ error }}</div>
      <q-btn color="secondary" no-caps unelevated label="Try again" @click="load" />
    </div>

    <div
      v-else-if="shows.length === 0"
      class="text-center q-pa-xl"
      style="max-width: 460px; margin: 0 auto"
    >
      <q-icon :name="matTheaterComedy" size="64px" color="grey-5" />
      <div class="text-h6 q-mt-md q-mb-sm">No active shows</div>
      <div class="text-body2 text-grey-8 q-mb-md">
        You don't have access to any shows right now. Access opens and closes
        on the dates the theater set for your production. If you think this is
        wrong, contact the theater's admin team.
      </div>
      <q-btn flat color="secondary" no-caps label="Check again" @click="load" />
    </div>

    <template v-else>
      <div class="text-h6 q-mb-md">Your shows</div>
      <div class="row q-col-gutter-md">
        <div v-for="show in shows" :key="show.id" class="col-6 col-sm-4 col-md-3">
          <q-card
            class="cursor-pointer full-height"
            @click="router.push({ name: 'director-show', params: { id: show.id } })"
          >
            <q-img :src="show.poster_url" :ratio="2 / 3" fit="cover" />
            <q-card-section class="q-pa-sm">
              <div class="text-subtitle2 ellipsis-2-lines">{{ show.name }}</div>
              <div class="text-caption text-grey-7">{{ dateRange(show) }}</div>
              <div v-if="show.role" class="text-caption text-grey-7">{{ show.role }}</div>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </template>
  </q-page>
</template>

<script setup>
import { matTheaterComedy } from "@quasar/extras/material-icons";
import { onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { fetchShows, getToken } from "src/director/api";
import { performanceRange } from "src/director/format";
import { offline, shownAt } from "src/director/state";

const router = useRouter();

const loading = ref(true);
const error = ref("");
const shows = ref([]);

const dateRange = (show) => performanceRange(show.first_performance, show.last_performance);

const load = async () => {
  loading.value = true;
  error.value = "";
  try {
    const result = await fetchShows();
    shows.value = result.data;
    shownAt.value = result.at;
    // One show: skip the picker entirely.
    if (shows.value.length === 1) {
      router.replace({ name: "director-show", params: { id: shows.value[0].id } });
      return;
    }
  } catch (e) {
    if (e.status === 401 || e.status === 403) {
      router.replace({ name: "director-setup" });
      return;
    }
    error.value = e.message;
  }
  loading.value = false;
};

// Back online: refresh (also clears the offline banner on success).
watch(offline, (isOffline) => !isOffline && load());

onMounted(() => {
  if (!getToken()) {
    router.replace({ name: "director-setup" });
    return;
  }
  load();
});
</script>
