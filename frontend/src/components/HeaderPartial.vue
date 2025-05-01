<template>
  <div class="flex justify-beetween banner items-center">
    <q-img
      src="/images/logo-home.png"
      :height="Screen.lt.sm ? '8vh' : '15vh'"
      width="10vw"
      class="q-ma-sm"
      fit="contain"
    ></q-img>

    <div
      class="absolute-center text-no-wrap q-pb-xl"
      :class="Screen.lt.sm ? 'text-h6' : 'text-h4 '"
    >
      Azuay Community Theater
    </div>
  </div>
</template>

<script setup>
import { Screen } from "quasar";
</script>

<style lang="scss" scoped>
.banner {
  background-image: url(/images/background.jpeg);
}

@media screen and (max-width: 600px) {
  .banner {
    height: 10vh;
  }
}
</style>
