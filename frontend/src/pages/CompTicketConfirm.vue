<template>
  <div>
    <div v-if="store.admin.compConfirm.redeemed_at">
      <div class="flex justify-center q-mt-md">
        <div class="column">
          <div class="text-h6 text-red">
            Your comp ticket has been redeemed for use on
            {{ performance.formatted_date }} at {{ performance.formatted_time }}
          </div>
          <div class="text-subtitle1 text-green">
            You can change it below.
          </div>
        </div>
      </div>
      <q-separator></q-separator>
    </div>
    <div>
      <q-toolbar>
        <q-toolbar-title>
          Welcome, {{ store.admin.compConfirm.name }}
        </q-toolbar-title>
      </q-toolbar>
      <q-separator></q-separator>
      <q-form @submit.prevent="onSubmit" class="q-pa-md">
        <q-select
          :options="options"
          dense
          outlined
          label="Select Performance"
          stack-label
          :rules="[(v) => !!v || 'Required']"
          v-model="form.performance"
        ></q-select>
        <q-input
          type="text"
          label="Pickup Name"
          hint="Name of person who will pick up ticket at door"
          v-model="form.pickup_name"
          stack-label
          dense
          outlined
          :rules="[(v) => !!v || 'Required']"
        ></q-input>

        <div class="flex justify-end">
          <q-btn type="submit" label="Submit" color="primary"></q-btn>
        </div>
      </q-form>
    </div>
  </div>
</template>

<script setup>
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const store = useStore();

const performance = computed(() => {
  if (!store.admin.compConfirm) {
    return null;
  }

  return store.admin.compConfirm.show.performances.find(
    ({ id }) => id == store.admin.compConfirm.performance_id,
  );
});

const form = ref({
  performance: performance.value
    ? {
        label: `${performance.value.formatted_date} ${performance.value.formatted_time}`,
        value: performance.value.id,
      }
    : null,
  pickup_name: store.admin.compConfirm.pickup_name || null,
});

const options = computed(() => {
  const now = new Date();
  return store.admin.compConfirm.show.performances.map((p) => {
    const isPast = new Date(`${p.date}T${p.start_time}`) < now;
    const isSoldOut = !!p.sold_out;
    return {
      label: `${p.formatted_date} ${p.formatted_time}${
        isSoldOut ? " — Sold Out" : ""
      }`,
      value: p.id,
      disable: isPast || isSoldOut,
    };
  });
});

const onSubmit = async () => {
  const payload = { ...form.value };
  payload.performance_id = payload.performance?.value;

  try {
    const response = await callApi({
      path: `/comp/redeem/${store.admin.compConfirm.uid}`,
      method: "post",
      payload,
    });
    if (response) {
      console.log({ response });
      store.admin.compConfirm = response.rec;
      Notify.create({
        color: "positive",
        message: "Your ticket has been reserved!",
      });
    }
  } catch (e) {
    Notify.create({
      type: "negative",
      message: "Something went wrong. Please try again.",
    });
  }
};
</script>
