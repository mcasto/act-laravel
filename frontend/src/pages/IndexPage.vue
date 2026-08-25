<template>
  <div>
    <div v-if="store.announcement?.status">
      <div v-html="store.announcement?.html"></div>
    </div>

    <q-separator class="q-mt-sm" v-if="store.announcement"></q-separator>

    <q-splitter v-if="hasCurrent" :model-value="50" :horizontal="Screen.lt.sm">
      <template #before>
        <current-show></current-show>
      </template>
      <template #after>
        <upcoming-carousel></upcoming-carousel>
      </template>
    </q-splitter>

    <upcoming-carousel v-else-if="hasUpcoming"></upcoming-carousel>

    <div v-else>
      <q-img
        src="/images/saving-your-seat.jpeg"
        fit="cover"
        class="full-height"
        :ratio="1508 / 944"
      />
    </div>
  </div>
</template>

<script setup>
import { Screen } from "quasar";
import CurrentShow from "src/components/CurrentShow.vue";
import UpcomingCarousel from "src/components/UpcomingCarousel.vue";
import { useStore } from "src/stores/store";
import { computed } from "vue";

const store = useStore();

const hasCurrent = computed(() => !!store.home.currentShow);
const hasUpcoming = computed(() => (store.home.upcomingShows?.length ?? 0) > 0);
</script>
