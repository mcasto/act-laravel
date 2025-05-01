<template>
  <q-splitter :model-value="50" :horizontal="Screen.lt.sm">
    <template #before>
      <current-show></current-show>
    </template>
    <template #after>
      <upcoming-carousel></upcoming-carousel>
    </template>
  </q-splitter>
</template>

<script setup>
import { Screen } from "quasar";
import CurrentShow from "src/components/CurrentShow.vue";
import UpcomingCarousel from "src/components/UpcomingCarousel.vue";
</script>
