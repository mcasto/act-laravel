<template>
  <q-dialog v-model="model">
    <q-card>
      <q-card-section>
        <div v-html="experience"></div>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
const model = defineModel();
const props = defineProps(["experience"]);
</script>
