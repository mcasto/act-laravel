<template>
  <div class="q-pa-md">
    <div class="text-center text-h4">
      {{ store.flex.title }}
    </div>
    <div class="text-center text-h6">
      {{ store.flex.subtitle }}
    </div>

    <div class="row">
      <div class="col-12 col-md-4 text-center q-mt-md">
        <q-img :src="store.flex.image" fit="contain"></q-img>
      </div>

      <div class="col-12 col-md-7 offset-md-1">
        <div v-html="store.flex.body" class="q-mt-md text-subtitle1"></div>

        <purchase-options
          :fixr-link="store.flex.fixr.link"
          :payment-methods="paymentMethods"
          :buttons="store.flex.buttons"
          :separator="true"
          v-model="paymentMethod"
        ></purchase-options>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { computed, onMounted, ref } from "vue";
import PurchaseOptions from "src/components/PurchaseOptions.vue";

const store = useStore();

console.log({ flex: store.flex });

const paymentMethod = ref(null);

const paymentMethods = computed(() => {
  return [
    {
      label: store.flex.fixr.label,
      value: "fixr",
    },
    ...store.flex.buttons.map((button) => {
      return {
        label: button.label,
        value: button.title,
      };
    }),
  ];
});

onMounted(() => {
  paymentMethod.value = { ...store.flex.fixr, value: "fixr" };
});
</script>
