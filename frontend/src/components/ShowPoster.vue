<template>
  <div>
    <q-img
      :src="`/api/storage/images/${show.poster}`"
      :width="width"
      :height="height"
      fit="contain"
    >
    </q-img>
  </div>
</template>

<script setup>
const props = defineProps(["show", "width", "height"]);
</script>
