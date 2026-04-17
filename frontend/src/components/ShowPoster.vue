<template>
  <div>
    <q-img
      :src="POSTER_BASE_URL + show.poster"
      :width="width"
      :height="height"
      fit="contain"
    >
    </q-img>
  </div>
</template>

<script setup>
import { POSTER_BASE_URL } from "src/assets/constants";
const props = defineProps(["show", "width", "height"]);
</script>
