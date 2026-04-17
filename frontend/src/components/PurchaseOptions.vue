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
      <component
        :is="paymentMethodForm"
        v-if="paymentMethodForm"
        :performance="performance"
        :is-flex="isFlex"
      ></component>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, watch } from "vue";
import PurchaseCredit from "./PurchaseCredit.vue";
import PayPalForm from "./PayPalForm.vue";
import TransferForm from "./TransferForm.vue";
import FlexForm from "./FlexForm.vue";

const props = defineProps([
  "fixrLink",
  "paymentMethods",
  "buttons",
  "separator",
  "performance",
  "isFlex",
]);

console.log({ isFlex: props.isFlex });

const paymentMethod = defineModel();

const paymentMethodForm = computed(() => {
  const types = {
    paypal: PayPalForm,
    transfer: TransferForm,
    flex: FlexForm,
  };

  const type = paymentMethod.value.label.match(/(paypal)|(transfer)|(flex)/i);

  if (!type) return null;

  const returnType = type[0].toLowerCase();

  return types[returnType];
});

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
