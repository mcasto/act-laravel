<template>
  <q-dialog v-model="model">
    <q-card style="width: 100vw;">
      <div class="flex justify-end">
        <q-btn icon="close" flat round size="sm" @click="model = false"></q-btn>
      </div>

      <q-separator spaced></q-separator>

      <div class="q-ma-xs text-center">
        {{ title }}
      </div>

      <q-separator spaced></q-separator>

      <q-card-section>
        <purchase-options
          :fixr-link="fixrLink"
          :payment-methods="paymentMethods"
          :buttons="buttons"
          v-model="paymentMethod"
        ></purchase-options>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import PurchaseOptions from "./PurchaseOptions.vue";

const model = defineModel();
const paymentMethod = defineModel("paymentMethod");
const props = defineProps(["buttons", "fixrLink", "paymentMethods", "title"]);
</script>
