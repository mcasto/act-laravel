<template>
  <div>
    <div>
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
        <q-form @submit.prevent="onSubmit">
          <q-card-section class="q-gutter-y-sm">
            <q-input
              type="number"
              label="Number of Tickets"
              stack-label
              dense
              outlined
              v-model.number="form.quantity"
              min="1"
              :rules="[(val) => val >= 1 || 'Must be at least 1']"
              v-if="!isFlex"
            ></q-input>
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
            ></q-input
            ><q-input
              type="tel"
              label="Phone / WhatsApp"
              stack-label
              dense
              outlined
              v-model="form.phone"
              :rules="[(v) => !!v || 'Required']"
            ></q-input>
            <div class="text-subtitle2">Special Request</div>
            <q-editor v-model="form.special_request"></q-editor>
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
    </div>
  </div>
</template>

<script setup>
import { clone } from "lodash-es";
import callApi from "src/assets/call-api";
import notifySubmitError from "src/assets/notify-submit-error";
import { ref } from "vue";
import { useStore } from "src/stores/store";

const props = defineProps(["performance", "isFlex"]);
const store = useStore();

const loading = ref(null);

const form = ref({
  type: "paypal",
  email: null,
  first_name: null,
  last_name: null,
  phone: null,
  quantity: props.isFlex ? store.flex.num_tickets : null,
  special_request: "",
});

const getPatron = async () => {
  const patron = await callApi({
    path: `/patrons/lookup?email=${form.value.email}`,
    method: "get",
    showError: false,
  }).catch(() => null);

  if (!patron) {
    store.patron = null;
    return;
  }

  form.value.first_name = patron.first_name;
  form.value.last_name = patron.last_name;
  form.value.phone = patron.phone;

  store.patron = patron;
};

const onSubmit = async () => {
  loading.value = true;

  try {
    const payload = clone(form.value);
    payload.performance_id = props.performance.id;

    const response = await callApi({
      path: "/ticket-sales",
      method: "post",
      payload,
    });

    if (response.transaction_id) {
      store.router.push({
        name: "ticket-confirmation",
        params: { uuid: response.transaction_id },
      });
    }
  } catch (error) {
    notifySubmitError(error, "Something went wrong submitting your request.");
  } finally {
    loading.value = false;
  }
};
</script>
