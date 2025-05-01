<template>
  <q-toolbar class="justify-end fixed">
    <q-btn
      icon="fa-regular fa-circle-left"
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
      <div class="col-12 col-md-4">
        <q-img
          :src="`/api/storage/images/${store.show.poster}`"
          height="60vh"
          fit="contain"
        ></q-img>
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
      </div>

      <div class="col-12 q-px-xl q-pt-md" v-html="store.show.info"></div>
    </div>
  </div>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { useStore } from "src/stores/store";
import { computed } from "vue";

const store = useStore();

const displayDate = computed(() => {
  const performances = store.show.performances;
  if (performances.length == 0) {
    return {
      label: "Tickets On Sale",
      date: format(parseISO(store.show.ticket_sales_start), PP),
    };
  }

  const dates = performances.map(({ date }) => format(parseISO(date), "PP"));
  const startDate = dates.shift();
  const endDate = dates.length > 0 ? dates.pop() : startDate;

  return {
    label: "Performance Dates",
    date: `${startDate} - ${endDate}`,
  };
});
</script>
