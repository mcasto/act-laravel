<template>
  <div class="q-pa-md">
    <div v-html="contents"></div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { computed } from "vue";

const store = useStore();
const contents = computed(() => {
  const slug = store.router.currentRoute.value.meta.slug;
  return store.snippets[slug].html;
});
</script>
