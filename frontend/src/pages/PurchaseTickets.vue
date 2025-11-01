<template>
  <div class="q-pa-md">
    <div class="row q-gutter-y-md">
      <div class="col-12 col-md-3">
        <q-img :src="`/api/storage/images/${show.poster}`"></q-img>
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

        <q-select
          label="Purchase Tickets For:"
          :options="performances"
          v-model="performance"
          option-label="displayDate"
          :option-disable="(opt) => opt.soldOut"
          dense
          outlined
        ></q-select>
        <purchase-options
          class="q-mt-md"
          v-if="performance"
          :fixr-id="performance.fixrId"
          :payment-methods="paymentMethods"
          :buttons="show.buttons"
          v-model="paymentMethod"
        ></purchase-options>
      </div>
    </div>
  </div>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { useStore } from "src/stores/store";
import { computed, onMounted, ref } from "vue";
import PurchaseOptions from "src/components/PurchaseOptions.vue";
import { sortBy } from "lodash-es";

const store = useStore();

const show = computed(() => {
  return store.home.currentShow;
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
  return sortBy(
    show.value.performances.map((performance) => {
      const fixrId = performance.fixr_link
        ? new URL(performance.fixr_link).pathname.split("/").pop()
        : "";

      let displayDate = format(parseISO(performance.date), "PP");
      const soldOut = performance.sold_out == 1;

      if (soldOut) {
        displayDate = `${displayDate} (Sold Out)`;
      }

      return {
        ...performance,
        displayDate,
        fixrId,
        soldOut,
      };
    }),
    "date"
  );
});

const paymentMethods = computed(() => {
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
  const firstPerformance = (performance.value = performances.value.find(
    ({ sold_out }) => sold_out == 0
  ));

  performance.value = firstPerformance || [...performances.value].shift();

  paymentMethod.value = {
    id: performance.value.fixrId,
    label: show.value.fixrLabel,
    value: "fixr",
  };
});
</script>
