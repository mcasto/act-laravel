<template>
  <div class="row q-gutter-x-sm q-pa-md">
    <div class="col-12 col-md-4">
      <q-img
        :src="`/api/storage/images/${show.poster}`"
        fit="contain"
        style="max-height: 50vh;"
      ></q-img>
    </div>
    <div class="col-12 col-md-7">
      <div class="text-h6 text-center">
        {{ show.name }}
      </div>
      <div class="text-caption q-mb-sm text-center">
        {{ performanceDates }}
      </div>

      <div class="text-subtitle1 text-center text-bold q-mb-sm">
        {{ show.tagline }}
      </div>

      <div v-if="show.writer" class="text-center">
        <span class="text-bold">
          Written By:
        </span>
        {{ show.writer }}
      </div>

      <div class="text-center">
        <span class="text-bold">
          Directed By:
        </span>
        {{ show.director }}
      </div>

      <div class="text-center q-mt-md">
        <div v-if="ticketsStart">
          <span class="text-bold">
            Tickets On Sale:
          </span>
          {{ ticketsStart }}
        </div>

        <div v-else>
          <q-btn
            label="Reserve Tickets"
            color="primary"
            icon="fa-solid fa-ticket"
          ></q-btn>
        </div>
      </div>
    </div>

    <div
      class="col-10 offset-1 q-pt-md"
      :class="Screen.lt.md ? 'q-mt-md q-mb-md' : ''"
      v-html="show.info"
    ></div>
  </div>
</template>

<script setup>
import { Screen } from "quasar";
import { format, isFuture, parseISO } from "date-fns";
import { useStore } from "src/stores/store";
import { computed } from "vue";

const store = useStore();

// saves some typing
const show = computed(() => {
  return store.home.currentShow;
});

const performanceDates = computed(() => {
  const performances = show.value.performances.map(({ date }) => date).sort();
  if (performances.length == 0) {
    return false;
  }

  let first = performances.shift();
  let last = performances.length > 0 ? performances.pop() : first;

  first = format(parseISO(first), "PP");
  last = format(parseISO(last), "PP");

  return `${first} - ${last}`;
});

const ticketsStart = computed(() => {
  // if ticket sales have started, no need to display ticket start
  if (!isFuture(parseISO(show.value.ticket_sales_start))) {
    return false;
  }

  return format(parseISO(show.value.ticket_sales_start), "PP");
});
</script>
