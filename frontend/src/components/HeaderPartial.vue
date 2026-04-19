<template>
  <div class="flex banner" style="position: relative;">
    <q-img
      src="/images/logo-home-2025.png"
      height="200px"
      width="215px"
      class="q-ml-sm"
      fit="cover"
      v-if="Screen.gt.sm"
    ></q-img>

    <div
      class="col flex flex-center text-no-wrap text-blue-grey-2 text-center"
      :class="Screen.lt.sm ? 'text-h5' : 'text-h3'"
    >
      Azuay Community Theater
    </div>
  </div>
</template>

<script setup>
import { Screen } from "quasar";
</script>
