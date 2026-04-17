<template>
  <div>
    <div>
      <q-card bordered class="q-mx-md">
        <q-toolbar>
          <q-toolbar-title>
            <div class="text-subtitle1">
              2. Submit this form
            </div>
            <div class="text-caption">
              This form lets us know to look out for your payment.
            </div>
          </q-toolbar-title>
        </q-toolbar>
        <q-form @submit.prevent="onSubmit">
          <q-card-section class="q-gutter-y-sm">
            <q-input
              type="text"
              label="Number of Tickets"
              stack-label
              dense
              outlined
              v-model="form.quantity"
            ></q-input>
            <q-input
              type="email"
              label="Email"
              stack-label
              dense
              outlined
              v-model="form.email"
              @blur="getPatron"
            ></q-input>
            <q-input
              type="date"
              label="Transfer Date"
              stack-label
              dense
              outlined
              v-model="form.transfer_date"
            ></q-input>
            <q-input
              type="text"
              label="First Name"
              stack-label
              dense
              outlined
              v-model="form.first_name"
            ></q-input>
            <q-input
              type="text"
              label="Last Name"
              stack-label
              dense
              outlined
              v-model="form.last_name"
            ></q-input
            ><q-input
              type="tel"
              label="Phone / WhatsApp"
              stack-label
              dense
              outlined
              v-model="form.phone"
            ></q-input>
          </q-card-section>

          <q-card-actions class="flex justify-end">
            <q-btn type="submit" label="Continue" color="primary"></q-btn>
          </q-card-actions>
        </q-form>
      </q-card>
    </div>
  </div>
</template>

<script setup>
import { formatISO9075 } from "date-fns";
import { clone } from "lodash-es";
import callApi from "src/assets/call-api";
import { ref } from "vue";
import { useStore } from "src/stores/store";

const props = defineProps(["performance"]);
const store = useStore();

const defaultDate = formatISO9075(new Date(), { representation: "date" });

const form = ref({
  type: "transfer",
  email: store.patron?.email || null,
  first_name: store.patron?.first_name || null,
  last_name: store.patron?.last_name || null,
  phone: store.patron?.phone || null,
  quantity: null,
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
};
</script>
