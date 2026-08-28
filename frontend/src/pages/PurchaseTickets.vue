<template>
  <div class="q-pa-md">
    <div v-if="!show" class="text-center q-pa-xl">
      <div class="text-h6">No tickets are available for purchase right now.</div>
      <div class="text-body2 q-mt-sm">
        Please check back soon, or see the Season page for what's coming up.
      </div>
    </div>

    <div
      v-else-if="performances.length > 0 && !hasAvailablePerformance"
      class="text-center q-pa-xl"
    >
      <poster-with-banner
        :src="POSTER_BASE_URL + show.poster"
        max-width="300px"
        sold-out
        class="q-mb-md"
      />
      <div class="text-h6">{{ show.name }}</div>
      <div class="text-subtitle1 q-mb-md">All performances are sold out.</div>
    </div>

    <div v-else class="row q-gutter-y-md">
      <div class="col-12 col-md-3 text-center">
        <poster-with-banner
          :src="POSTER_BASE_URL + show.poster"
          max-width="400px"
          :sold-out="allSoldOut"
        />
      </div>
      <div class="col-12 col-md-8 offset-md-1">
        <div class="text-h6 text-center">
          {{ show?.name }}
        </div>
        <div class="text-caption q-mb-sm text-center">
          {{ performanceDates }}
        </div>

        <div class="text-subtitle1 text-center text-bold q-mb-sm">
          {{ show?.tagline }}
        </div>

        <div v-if="show?.writer" class="text-center">
          <span class="text-bold">
            Written By:
          </span>
          {{ show?.writer }}
        </div>

        <div class="text-center">
          <span class="text-bold">
            Directed By:
          </span>
          {{ show?.director }}
        </div>

        <div v-html="show.info" class="q-mt-md"></div>

        <q-separator spaced></q-separator>
        <q-select
          label="Purchase Tickets For:"
          :options="performances"
          v-model="performance"
          option-label="displayDate"
          :option-disable="(opt) => opt.soldOut || opt.isPast"
          dense
          outlined
        ></q-select>
        <purchase-options
          class="q-mt-md"
          v-if="performance"
          :fixr-link="performance.fixr_link"
          :payment-methods="paymentMethods"
          :buttons="show.buttons"
          :performance="performance"
          v-model="paymentMethod"
        ></purchase-options>
      </div>
    </div>
  </div>
</template>

<script setup>
import { POSTER_BASE_URL } from "src/assets/constants";
import PosterWithBanner from "src/components/PosterWithBanner.vue";
import { format, parseISO } from "date-fns";
import { useStore } from "src/stores/store";
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import PurchaseOptions from "src/components/PurchaseOptions.vue";
import { sortBy } from "lodash-es";

const route = useRoute();
const store = useStore();

const show = computed(() => {
  return store.home.currentShow;
});

const allSoldOut = computed(() => {
  const perfs = show.value?.performances ?? [];
  return perfs.length > 0 && perfs.every((p) => p.sold_out);
});

const performance = ref(null);
const paymentMethod = ref(null);

const performanceDates = computed(() => {
  if (
    !show.value ||
    show.value.performances.length == 0 ||
    show.value.tentative == 1
  ) {
    return false;
  }

  const performances = show.value.performances.map(({ date }) => date).sort();
  let first = performances.shift();
  let last = performances.length > 0 ? performances.pop() : first;

  return `${format(first, "PP")} - ${format(last, "PP")}`;
});

const performances = computed(() => {
  if (!show.value) return [];

  const now = new Date();
  return sortBy(
    show.value.performances.map((performance) => {
      let displayDate = format(
        parseISO(`${performance.date} ${performance.start_time}`),
        "PPp",
      );
      const soldOut = performance.sold_out == 1;
      const isPast = new Date(`${performance.date}T${performance.start_time}`) < now;

      if (isPast && soldOut) {
        displayDate = `${displayDate} (Past - Sold Out)`;
      } else if (soldOut) {
        displayDate = `${displayDate} (Sold Out)`;
      } else if (isPast) {
        displayDate = `${displayDate} (Past)`;
      }

      return {
        ...performance,
        displayDate,
        soldOut,
        isPast,
      };
    }),
    "date",
  );
});

const hasAvailablePerformance = computed(() =>
  performances.value.some(({ soldOut, isPast }) => !soldOut && !isPast),
);

const paymentMethods = computed(() => {
  if (!show.value) return [];

  return [
    {
      label: show.value.fixrLabel,
      value: "fixr",
    },
    ...show.value.buttons.map((button) => {
      return {
        label: button.label,
        value: button.id,
      };
    }),
  ];
});

onMounted(() => {
  if (!show.value) return;

  performance.value = performances.value.find(
    ({ soldOut, isPast }) => !soldOut && !isPast,
  ) ?? null;

  if (!performance.value) return;

  const flexButton = route.query.flex
    ? show.value.buttons.find((b) => b.key === "flex")
    : null;

  paymentMethod.value = flexButton
    ? { id: null, label: flexButton.label, value: flexButton.id }
    : { id: performance.value.fixr_link, label: show.value.fixrLabel, value: "fixr" };
});
</script>
