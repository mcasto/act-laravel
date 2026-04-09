<template>
  <div v-if="paymentMethod">
    <q-separator spaced v-if="separator"></q-separator>

    <q-select
      :options="paymentMethods"
      v-model="paymentMethod"
      dense
      outlined
      v-if="paymentMethods.length > 0"
      label="Payment Method"
      stack-label
    ></q-select>

    <purchase-credit
      :id="fixrLink"
      v-if="paymentMethod.value == 'fixr'"
    ></purchase-credit>

    <div v-else class="q-mt-md">
      <div v-html="details.popupText"></div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, watch } from "vue";
import PurchaseCredit from "./PurchaseCredit.vue";

const props = defineProps([
  "fixrLink",
  "paymentMethods",
  "buttons",
  "separator",
  "performance",
]);

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

watch([paymentMethod, () => props.performance], async () => {
  await nextTick();
  const field = document.querySelector('[name="performance_id"]');
  if (field && props.performance) {
    field.value = props.performance.id;
  }
});
</script>
