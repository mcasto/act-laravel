<template>
  <div v-if="store.admin.shows">
    <!-- Featured Current/Next Show -->
    <div v-if="currentShow" class="q-mb-lg">
      <q-toolbar>
        <q-toolbar-title>
          Current Show
        </q-toolbar-title>
      </q-toolbar>
      <q-card bordered class="q-ma-sm">
        <q-toolbar class="bg-primary text-white">
          <q-toolbar-title
            class="text-h6"
            v-html="displayDate(currentShow)"
          >
          </q-toolbar-title>
        </q-toolbar>
        <q-card-section class="row items-center">
          <div class="col-12 col-md-4">
            <router-link :to="`edit-show/${currentShow.id}`">
              <show-poster
                :show="currentShow"
                width="30vw"
                height="30vh"
              ></show-poster>
            </router-link>
          </div>
          <div class="col-12 col-md-8 q-pl-md">
            <div class="text-h5 q-mb-md">{{ currentShow.name }}</div>
            <div class="row q-gutter-sm">
              <q-btn
                label="Edit Show"
                color="primary"
                :to="`edit-show/${currentShow.id}`"
              ></q-btn>
              <q-btn
                label="Delete"
                color="negative"
                outline
                @click="store.deleteShow(currentShow.id)"
              ></q-btn>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- All Other Shows -->
    <q-table :rows="otherShows" grid :pagination="{ rowsPerPage: 6 }">
      <template #top>
        <q-toolbar>
          <q-toolbar-title>
            {{ currentShow ? 'Other Shows' : 'Shows' }}
          </q-toolbar-title>
          <q-btn icon="add" round color="primary" to="new-show"></q-btn>
        </q-toolbar>
      </template>
      <template #item="props">
        <q-card bordered class="q-ma-sm" style="width: 30%;">
          <q-toolbar>
            <q-toolbar-title
              class="text-subtitle2"
              v-html="displayDate(props.row)"
            >
            </q-toolbar-title>
          </q-toolbar>
          <q-card-section>
            <router-link :to="`edit-show/${props.row.id}`">
              <show-poster
                :show="props.row"
                width="20vw"
                height="20vh"
              ></show-poster>
            </router-link>
          </q-card-section>
          <q-card-section>
            <div class="flex justify-between items-center">
              <div class="ellipsis" style="width: 80%;">
                {{ props.row.name }}
              </div>
              <div>
                <q-btn
                  icon="delete"
                  round
                  flat
                  size="small"
                  @click="store.deleteShow(props.row.id)"
                ></q-btn>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </template>
    </q-table>
  </div>
</template>

<script setup>
import { add, format, formatISO9075, parseISO } from "date-fns";
import { clone } from "lodash-es";
import { computed } from "vue";
import ShowPoster from "src/components/ShowPoster.vue";
import { useStore } from "src/stores/store";

const store = useStore();

const displayDate = (show) => {
  // if performances, then return range of dates for performances
  if (show.performances?.length > 0) {
    const performances = clone(show.performances);
    const dateList = performances.map(({ date }) => date).sort();
    const first = format(parseISO(dateList.shift()), "LLL d, yyyy");
    const last =
      dateList.length == 0
        ? first
        : format(parseISO(dateList.pop()), "LLL d, yyyy");

    return `Performances: <div> ${first} - ${last}</div>`;
  }

  // else get ticket_sales_start
  return `Tickets On Sale: <div> ${format(
    parseISO(show.ticket_sales_start),
    "LLL d, yyyy"
  )}</div>`;
};

const getShowDateRange = (show) => {
  if (show.performances?.length > 0) {
    const dates = show.performances.map(({ date }) => parseISO(date).getTime());
    return {
      earliest: Math.min(...dates),
      latest: Math.max(...dates),
    };
  }
  // Otherwise use ticket sales start date
  const date = parseISO(show.ticket_sales_start).getTime();
  return { earliest: date, latest: date };
};

const currentShow = computed(() => {
  if (!store.admin.shows) return null;

  const now = new Date().getTime();
  const sevenDaysAgo = now - 7 * 24 * 60 * 60 * 1000;

  // Filter to shows that are current or upcoming (not ended more than 7 days ago)
  const activeShows = store.admin.shows.filter((show) => {
    const { latest } = getShowDateRange(show);
    return latest >= sevenDaysAgo;
  });

  // Among active shows, find the one with the earliest start date
  if (activeShows.length === 0) return null;

  return activeShows.reduce((closest, show) => {
    const { earliest } = getShowDateRange(show);
    const closestEarliest = getShowDateRange(closest).earliest;
    return earliest < closestEarliest ? show : closest;
  });
});

const otherShows = computed(() => {
  if (!store.admin.shows) return [];

  // Sort all other shows by latest date descending (most recent first)
  const others = currentShow.value
    ? store.admin.shows.filter((show) => show.id !== currentShow.value.id)
    : store.admin.shows;

  return [...others].sort((a, b) => {
    const dateA = getShowDateRange(a).latest;
    const dateB = getShowDateRange(b).latest;
    return dateB - dateA;
  });
});
</script>
