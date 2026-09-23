<template>
  <q-dialog v-model="store.showDetailsDialog" :maximized="Screen.lt.sm">
    <q-card style="width: 900px; max-width: 95vw;" class="overflow-hidden" v-if="store.show">
      <div class="hero-band text-white text-center q-py-md q-px-xl relative-position">
        <div class="text-h5 text-weight-bold">{{ store.show.name }}</div>
        <q-btn
          :icon="matClose"
          flat
          round
          dense
          color="white"
          class="absolute-top-right q-ma-sm"
          v-close-popup
        >
          <q-tooltip>Close</q-tooltip>
        </q-btn>
      </div>

      <q-card-section class="q-pa-md">
        <div class="row q-col-gutter-lg">
          <div class="col-12 col-sm-4 text-center">
            <poster-with-banner
              :src="POSTER_BASE_URL + store.show.poster"
              max-height="50vh"
              aspect-ratio="2/3"
              :sold-out="allSoldOut"
              class="rounded-borders shadow-2"
            />
          </div>

          <div class="col-12 col-sm-8 column justify-between">
            <div>
              <div v-if="store.show.tagline" class="text-subtitle1 text-italic q-mb-md">
                {{ store.show.tagline }}
              </div>

              <div class="row q-col-gutter-sm">
                <div class="col-12">
                  <div class="fact-chip">
                    <q-icon :name="matEvent" />
                    <div>
                      <div class="fact-label">{{ displayDate.label }}</div>
                      <div class="fact-value">{{ displayDate.date }}</div>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-sm-6" v-if="store.show.writer">
                  <div class="fact-chip">
                    <q-icon :name="matEditNote" />
                    <div>
                      <div class="fact-label">Written By</div>
                      <div class="fact-value">{{ store.show.writer }}</div>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-sm-6" v-if="store.show.director">
                  <div class="fact-chip">
                    <q-icon :name="matTheaterComedy" />
                    <div>
                      <div class="fact-label">Directed By</div>
                      <div class="fact-value">{{ store.show.director }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div
              v-if="ticketsStart && !store.showDetailsIsFlexAccess"
              class="text-center q-mt-md"
            >
              <span class="text-bold">Tickets On Sale:</span> {{ ticketsStart }}
            </div>
            <q-btn
              v-else-if="!store.show.tentative && !allSoldOut && isActiveShow"
              label="Reserve Tickets"
              color="secondary"
              size="lg"
              unelevated
              :icon="fasTicket"
              class="full-width q-mt-md"
              :to="
                store.showDetailsIsFlexAccess
                  ? { path: '/purchase-tickets', query: { flex: '1' } }
                  : '/purchase-tickets'
              "
              v-close-popup
            />
          </div>
        </div>

        <template v-if="store.show.info">
          <q-separator class="q-my-md" />
          <div class="about-content" v-html="store.show.info"></div>
        </template>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import {
  matClose,
  matEditNote,
  matEvent,
  matTheaterComedy,
} from "@quasar/extras/material-icons";
import { fasTicket } from "@quasar/extras/fontawesome-v6";
import { POSTER_BASE_URL } from "src/assets/constants";
import PosterWithBanner from "src/components/PosterWithBanner.vue";
import { format, isFuture, parseISO } from "date-fns";
import formatPerformanceDateRange from "src/assets/format-performance-date-range";
import { useStore } from "src/stores/store";
import { Screen } from "quasar";
import { computed } from "vue";

const store = useStore();

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
// here (this dialog can show any show, current or historical).
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

<style lang="scss" scoped>
.about-content {
  line-height: 1.6;
}
</style>
