<template>
  <div class="q-px-xl q-mt-md">
    <div class="text-h6" v-html="config.title"></div>

    <p v-html="config.header"></p>

    <div class="q-mb-md" v-html="config.thanks"></div>

    <div
      class="angel-level"
      id="act-angel"
      v-for="level of config.levels"
      :key="level.id"
    >
      <div class="text-bold q-mt-lg q-mb-xs">
        <span class="q-mr-md">
          {{ level.label }}
        </span>
        <q-btn
          :label="`Donate ${level.min_amount_formatted}`"
          color="primary"
          @click="onDonateClick(level)"
        ></q-btn>
      </div>

      <q-list dense bordered separator class="q-mx-lg">
        <q-item
          v-for="(item, index) of level.benefits"
          :key="`${level.id}-benefit-${index}`"
        >
          <q-item-section>
            <q-item-label v-html="item"> </q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </div>

    <angel-donate-dialog
      v-model="dialog.visible"
      :buttons="dialog.buttons"
      :fixr-link="dialog.fixrLink"
      v-model:paymentMethod="dialog.paymentMethod"
      :payment-methods="dialog.paymentMethods"
      :title="dialog.title"
    ></angel-donate-dialog>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import AngelDonateDialog from "src/components/AngelDonateDialog.vue";
import { ref } from "vue";

const store = useStore();

const config = store.angelConfig;

const onDonateClick = (level) => {
  dialog.value = {
    visible: true,
    buttons: level.buttons,
    fixrLink: level.fixr_link,
    paymentMethods: preparePaymentMethods(level.buttons),
    paymentMethod: preparePaymentMethod(level.fixr_link),
    title: `${level.label} (${level.min_amount_formatted})`,
  };
};

const preparePaymentMethods = (buttons) => {
  return [
    {
      label: config.config.fixr_label,
      value: "fixr",
    },
    ...buttons.map((button) => {
      return {
        label: button.label,
        value: button.id,
      };
    }),
  ];
};

const preparePaymentMethod = (fixrLink) => {
  return {
    id: fixrLink,
    label: config.config.fixr_label,
    value: "fixr",
  };
};

const dialog = ref({
  visible: false,
  buttons: null,
  fixrLink: null,
  paymentMethods: null,
  paymentMethod: null,
});
</script>
