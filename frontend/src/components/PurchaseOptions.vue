<template>
  <div v-if="paymentMethod">
    <q-separator spaced v-if="separator"></q-separator>

    <q-toolbar flat>
      <q-toolbar-title>
        Purchase Options
      </q-toolbar-title>
    </q-toolbar>
    <q-select
      :options="paymentMethods"
      v-model="paymentMethod"
      dense
      outlined
      v-if="paymentMethods.length > 0"
    ></q-select>

    <purchase-credit
      :id="fixrId"
      v-if="paymentMethod.value == 'fixr'"
    ></purchase-credit>

    <div v-else class="q-mt-md">
      <div v-html="details.popupText"></div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import PurchaseCredit from "./PurchaseCredit.vue";

const props = defineProps(["fixrId", "paymentMethods", "buttons", "separator"]);

const paymentMethod = defineModel();

const details = computed(() => {
  if (paymentMethod.value == "fixr") {
    return { label: "", value: "" };
  }

  const config = props.buttons.find(({ id }) => {
    return id == paymentMethod.value.value;
  });

  return config;
});
</script>
