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
        v-bind="{ ...angelFormProps, ...flexFormProps }"
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
import MessageUsForm from "./MessageUsForm.vue";
import AngelDonationForm from "./AngelDonationForm.vue";
import FlexPurchaseForm from "./FlexPurchaseForm.vue";

const props = defineProps([
  "fixrLink",
  "paymentMethods",
  "buttons",
  "separator",
  "performance",
  "isFlex",
  "isAngelDonation",
  "angelLevelId",
  "donationAmount",
]);

const paymentMethod = defineModel();

const paymentMethodForm = computed(() => {
  // Angel donations always use their own form, regardless of which payment
  // method (PayPal/transfer) was picked — it collects donor info, not
  // ticket-purchase details, and posts to a different endpoint entirely.
  if (props.isAngelDonation) return AngelDonationForm;

  // Buying a Flex package (isFlex, set only by FlexPurchase.vue — never by
  // the redemption contexts on PurchaseTickets.vue/SupportUs.vue) is a
  // season-level purchase with no performance attached, so it always uses
  // its own form + endpoint too, regardless of which payment method was
  // picked — same reasoning as the Angel donation case above.
  if (props.isFlex) return FlexPurchaseForm;

  const types = {
    paypal: PayPalForm,
    transfer: TransferForm,
    flex: FlexForm,
    "message us": MessageUsForm,
  };

  const type = paymentMethod.value.label.match(
    /(paypal)|(transfer)|(flex)|(message us)/i,
  );

  if (!type) return null;

  const returnType = type[0].toLowerCase();

  return types[returnType];
});

const angelFormProps = computed(() => {
  if (!props.isAngelDonation) return {};

  // paymentMethod.value here is a standard_buttons id, not a payment_methods
  // id — those tables aren't related, so derive the actual payment_methods
  // "value" string (paypal/transfer) from the label text instead, the same
  // way paymentMethodForm above picks which form component to render.
  const match = paymentMethod.value?.label?.match(/(paypal)|(transfer)/i);

  return {
    angelLevelId: props.angelLevelId,
    donationAmount: props.donationAmount,
    paymentMethodValue: match ? match[0].toLowerCase() : null,
  };
});

const flexFormProps = computed(() => {
  if (!props.isFlex) return {};

  // Same reasoning as angelFormProps above — paymentMethod.value here is a
  // standard_buttons id, not a payment_methods id, so derive the actual
  // payment_methods "value" string (paypal/transfer) from the label text.
  const match = paymentMethod.value?.label?.match(/(paypal)|(transfer)/i);

  return {
    paymentMethodValue: match ? match[0].toLowerCase() : null,
  };
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
