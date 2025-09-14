<template>
  <div>
    <router-view></router-view>
  </div>
</template>

<script setup>
import { useStore } from "./stores/store";

const store = useStore();

window.addEventListener("apiLoaded", store.apiLoaded);
</script>
