<template>
  <q-card bordered class="q-mx-md">
    <q-toolbar>
      <q-toolbar-title>
        <div class="text-overline">Step 2</div>
        <div class="text-subtitle1">Submit this form</div>
        <div class="text-caption">
          This form lets us know to look out for your donation.
        </div>
      </q-toolbar-title>
    </q-toolbar>

    <div v-if="submitted" class="q-pa-md text-center">
      Thank you for your donation! We'll be in touch.
    </div>

    <q-form v-else @submit.prevent="onSubmit">
      <q-card-section class="q-gutter-y-sm">
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
          type="text"
          label="Recognition Name"
          hint="How do you want the name(s) displayed?"
          stack-label
          dense
          outlined
          v-model="recognitionName"
          :rules="[(v) => !!v || 'Required']"
        ></q-input>
        <q-input
          type="email"
          label="Email"
          stack-label
          dense
          outlined
          v-model="form.email"
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
import { computed, ref, watch } from "vue";

const props = defineProps(["angelLevelId", "donationAmount", "paymentMethodValue"]);

const loading = ref(false);
const submitted = ref(false);
const recognitionNameEdited = ref(false);

const form = ref({
  first_name: "",
  last_name: "",
  recognition_name: "",
  email: "",
});

// Keeps recognition_name in sync with first/last name until the donor
// deliberately types their own value into that field.
const recognitionName = computed({
  get: () => form.value.recognition_name,
  set: (val) => {
    form.value.recognition_name = val;
    recognitionNameEdited.value = true;
  },
});

watch([() => form.value.first_name, () => form.value.last_name], () => {
  if (!recognitionNameEdited.value) {
    form.value.recognition_name =
      `${form.value.first_name || ""} ${form.value.last_name || ""}`.trim();
  }
});

const onSubmit = async () => {
  loading.value = true;

  try {
    const payload = clone(form.value);
    payload.angel_level_id = props.angelLevelId;
    payload.donation_amount = props.donationAmount;
    payload.payment_method_value = props.paymentMethodValue;

    const response = await callApi({
      path: "/angel-donation",
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
