<template>
  <div class="flex justify-beetween banner">
    <q-img
      src="/images/logo-home-2025.jpeg"
      height="200px"
      width="215px"
      class="q-ml-sm"
      fit="cover"
      v-if="Screen.gt.sm"
    ></q-img>

    <div
      class="absolute-center text-no-wrap q-pb-xl text-black"
      :class="Screen.lt.sm ? 'text-h6 ' : 'text-h4 '"
    >
      Azuay Community Theater
    </div>
  </div>
</template>

<script setup>
import { Screen } from "quasar";
</script>
