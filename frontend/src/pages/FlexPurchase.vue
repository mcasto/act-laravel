<template>
  <div class="q-pa-md">
    <div class="row">
      <div class="col-12 col-md-8 offset-md-2">
        <div class="text-center text-h4">
          {{ store.flex.title }}
        </div>
        <div class="text-center text-h6">
          {{ store.flex.subtitle }}
        </div>

        <div class="text-center q-mt-md">
          <q-img :src="store.flex.image" fit="contain"></q-img>
        </div>

        <div v-html="store.flex.body" class="q-mt-md text-subtitle1"></div>

        <q-separator spaced></q-separator>

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
        ></q-select>

        <flex-credit
          :id="store.flex.fixr.id"
          v-if="paymentMethod.value == 'fixr'"
        ></flex-credit>

        <div v-else class="q-mt-md">
          <div v-html="details.popupText"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { computed, onMounted, ref } from "vue";
import FlexCredit from "src/components/FlexCredit.vue";

const store = useStore();

const paymentMethod = ref("fixr");

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

const details = computed(() => {
  if (paymentMethod.value == "fixr") {
    return { label: "", value: "" };
  }

  const config = store.flex.buttons.find(
    ({ title }) => title == paymentMethod.value.value
  );

  console.log({ config });

  return config;
});

onMounted(() => {
  paymentMethod.value = { ...store.flex.fixr, value: "fixr" };
});
</script>
