<template>
  <q-toolbar class="justify-end fixed z-max">
    <q-btn
      :icon="farCircleLeft"
      dense
      flat
      round
      color="primary"
      @click="store.router.go(-1)"
    >
      <q-tooltip>
        Back
      </q-tooltip>
    </q-btn>
  </q-toolbar>
  <div class="q-py-md">
    <div class="row">
      <div class="col-12 col-md-4 text-center">
        <poster-with-banner
          :src="POSTER_BASE_URL + store.show.poster"
          max-height="60vh"
          :sold-out="allSoldOut"
        />
      </div>
      <div class="col-12 col-md-8 text-center">
        <div class="text-h6">
          {{ store.show.name }}
        </div>
        <div class="text-caption">
          <span class="text-bold">{{ displayDate.label }}:</span>
          {{ displayDate.date }}
        </div>

        <q-separator class="q-my-xl"></q-separator>

        <div class="text-subtitle1">
          {{ store.show.tagline }}
        </div>

        <q-separator class="q-my-xl"></q-separator>

        <div>
          <span class="text-bold">Written By:</span>
          {{ store.show.writer }}
        </div>

        <div>
          <span class="text-bold">Directed By:</span>
          {{ store.show.director }}
        </div>

        <q-separator class="q-my-xl" />

        <div v-if="ticketsStart && !isFlexAccess">
          <span class="text-bold">Tickets On Sale:</span> {{ ticketsStart }}
        </div>
        <div v-else-if="!store.show.tentative && !allSoldOut && isActiveShow">
          <q-btn
            label="Reserve Tickets"
            color="primary"
            :icon="fasTicket"
            :to="isFlexAccess ? { path: '/purchase-tickets', query: { flex: '1' } } : '/purchase-tickets'"
          />
        </div>
      </div>

      <div class="col-12 q-px-xl q-pt-md" v-html="store.show.info"></div>
    </div>
  </div>
</template>

<script setup>
import { farCircleLeft } from "@quasar/extras/fontawesome-v6";
import { fasTicket } from "@quasar/extras/fontawesome-v6";
import { POSTER_BASE_URL } from "src/assets/constants";
import PosterWithBanner from "src/components/PosterWithBanner.vue";
import { format, isFuture, parseISO } from "date-fns";
import formatPerformanceDateRange from "src/assets/format-performance-date-range";
import { useStore } from "src/stores/store";
import { useRoute } from "vue-router";
import { computed } from "vue";

const store = useStore();
const route  = useRoute();

const isFlexAccess = route.name === "flex-show-details";

const ticketsStart = computed(() => {
  if (!store.show?.ticket_sales_start) return false;
  if (!isFuture(parseISO(store.show.ticket_sales_start))) return false;
  return format(parseISO(store.show.ticket_sales_start), "PP");
});

const allSoldOut = computed(() => {
  const perfs = store.show?.performances ?? [];
  return perfs.length > 0 && perfs.every((p) => p.sold_out);
});

// /purchase-tickets only ever sells store.home.currentShow — Reserve
// Tickets should only appear when that's actually the show being viewed
// here (this page can now show any show, current or historical).
const isActiveShow = computed(
  () => !!store.home?.currentShow && store.show?.id === store.home.currentShow.id,
);

const displayDate = computed(() => {
  const performances = store.show.performances;

  if (performances.length == 0) {
    return {
      label: "Tickets On Sale",
      date: format(parseISO(store.show.ticket_sales_start), "PP"),
    };
  }

  if (store.show.tentative) {
    return {
      label: "Performance Dates",
      date: format(parseISO(performances[0].date), "MMM y"),
    };
  }

  const dates = performances.map(({ date }) => parseISO(date));

  return {
    label: "Performance Dates",
    date: formatPerformanceDateRange(dates),
  };
});
</script>
