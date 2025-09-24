<template>
  <q-dialog v-model="model">
    <q-card class="full-width">
      <q-btn
        flat
        round
        icon="close"
        size="sm"
        @click="model = false"
        class="absolute-top-right z-max"
      ></q-btn>

      <q-toolbar>
        <q-toolbar-title>
          Message
        </q-toolbar-title>
      </q-toolbar>
      <q-card-section>
        {{ message }}
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
const model = defineModel();
const props = defineProps(["message"]);
</script>
