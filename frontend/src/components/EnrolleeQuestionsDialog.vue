<template>
  <q-dialog v-model="model">
    <q-card style="min-width: 300px;">
      <q-card-actions class="justify-between">
        Questions
        <q-btn icon="close" round flat @click="model = false" size="sm"></q-btn>
      </q-card-actions>
      <q-separator></q-separator>
      <q-card-section>
        <div v-html="questions"></div>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
const model = defineModel();
const props = defineProps(["questions"]);
</script>
