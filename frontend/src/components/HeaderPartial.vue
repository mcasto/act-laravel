<template>
  <div class="flex justify-beetween banner">
    <q-img
      src="/images/logo-home-2025.png"
      height="200px"
      width="215px"
      class="q-ml-sm"
      fit="cover"
      v-if="Screen.gt.sm"
    ></q-img>

    <div
      class="absolute-center text-no-wrap q-pb-xl text-blue-grey-2 text-center"
      :class="Screen.lt.sm ? 'text-h5' : 'text-h2'"
    >
      Azuay Community
      <div class="q-my-xs" />
      Theater
    </div>
  </div>
</template>

<script setup>
import { Screen } from "quasar";
</script>
