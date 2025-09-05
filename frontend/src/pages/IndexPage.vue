<template>
  <div>
    <div v-if="store.announcement">
      <div v-html="store.announcement"></div>
    </div>
    <q-separator class="q-mt-sm" v-if="store.announcement"></q-separator>

    <q-splitter :model-value="50" :horizontal="Screen.lt.sm">
      <template #before>
        <current-show></current-show>
      </template>
      <template #after>
        <upcoming-carousel></upcoming-carousel>
      </template>
    </q-splitter>
  </div>
</template>

<script setup>
import { Screen } from "quasar";
import CurrentShow from "src/components/CurrentShow.vue";
import UpcomingCarousel from "src/components/UpcomingCarousel.vue";
import { useStore } from "src/stores/store";

const store = useStore();
</script>
