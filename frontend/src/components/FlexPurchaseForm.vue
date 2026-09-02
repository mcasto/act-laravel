<template>
  <q-card bordered class="q-mx-md">
    <q-toolbar>
      <q-toolbar-title>
        <div class="text-overline">Step 2</div>
        <div class="text-subtitle1">Submit this form</div>
        <div class="text-caption">
          This form lets us know to look out for your payment.
        </div>
      </q-toolbar-title>
    </q-toolbar>

    <div v-if="submitted" class="q-pa-md text-center">
      Thank you! We'll confirm your payment and add your Flex tickets soon.
    </div>

    <q-form v-else @submit.prevent="onSubmit">
      <q-card-section class="q-gutter-y-sm">
        <q-input
          type="email"
          label="Your Email"
          stack-label
          dense
          outlined
          v-model="form.email"
          @blur="getPatron"
          :rules="[
            (v) => !!v || 'Required',
            (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Invalid email',
          ]"
        ></q-input>
        <q-input
          type="text"
          label="First Name"
          stack-label
          dense
          outlined
          v-model="form.first_name"
          :rules="[(v) => !!v || 'Required']"
        ></q-input>
        <q-input
          type="text"
          label="Last Name"
          stack-label
          dense
          outlined
          v-model="form.last_name"
          :rules="[(v) => !!v || 'Required']"
        ></q-input>
        <q-input
          type="tel"
          label="Phone / WhatsApp"
          stack-label
          dense
          outlined
          v-model="form.phone"
          :rules="[(v) => !!v || 'Required']"
        ></q-input>
      </q-card-section>

      <q-card-actions class="flex justify-end">
        <q-btn
          type="submit"
          label="Continue"
          color="primary"
          :loading="loading"
        ></q-btn>
      </q-card-actions>
    </q-form>
  </q-card>
</template>

<script setup>
import { clone } from "lodash-es";
import callApi from "src/assets/call-api";
import notifySubmitError from "src/assets/notify-submit-error";
import { ref } from "vue";

const props = defineProps(["paymentMethodValue"]);

const loading = ref(false);
const submitted = ref(false);

const form = ref({
  email: null,
  first_name: null,
  last_name: null,
  phone: null,
});

const getPatron = async () => {
  const patron = await callApi({
    path: `/patrons/lookup?email=${form.value.email}`,
    method: "get",
    showError: false,
  }).catch(() => null);

  if (!patron) return;

  form.value.first_name = patron.first_name;
  form.value.last_name = patron.last_name;
  form.value.phone = patron.phone;
};

const onSubmit = async () => {
  loading.value = true;

  try {
    const payload = clone(form.value);
    payload.payment_method_value = props.paymentMethodValue;

    const response = await callApi({
      path: "/flex-purchase",
      method: "post",
      payload,
    });

    if (response.status === "success") {
      submitted.value = true;
    }
  } catch (error) {
    notifySubmitError(error, "Something went wrong submitting your request.");
  } finally {
    loading.value = false;
  }
};
</script>
