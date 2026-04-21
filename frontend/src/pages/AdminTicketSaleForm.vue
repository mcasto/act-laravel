<template>
  <div>
    <div class="text-h6 q-mb-md">
      {{ isEdit ? "Edit Ticket Sale" : "New Ticket Sale" }}
    </div>

    <q-form @submit.prevent="onSubmit" style="max-width: 480px;">
      <div class="q-gutter-y-sm">
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
          type="text"
          label="First Name"
          stack-label
          dense
          outlined
          v-model="form.first_name"
          :rules="[(val) => !!val || 'Required']"
        ></q-input>

        <q-input
          type="text"
          label="Last Name"
          stack-label
          dense
          outlined
          v-model="form.last_name"
          :rules="[(val) => !!val || 'Required']"
        ></q-input>

        <q-input
          type="tel"
          label="Phone / WhatsApp"
          stack-label
          dense
          outlined
          v-model="form.phone"
          :rules="[(val) => !!val || 'Required']"
        ></q-input>

        <q-select
          label="Performance"
          stack-label
          dense
          outlined
          v-model="form.performance"
          :options="performanceOptions"
          :option-disable="(opt) => opt.disable"
          :rules="[(val) => !!val || 'Required']"
        ></q-select>

        <q-select
          label="Payment Method"
          stack-label
          dense
          outlined
          v-model="form.payment_method"
          :options="paymentMethodOptions"
          :rules="[(val) => !!val || 'Required']"
        ></q-select>

        <q-input
          type="number"
          label="Quantity"
          stack-label
          dense
          outlined
          v-model.number="form.quantity"
          min="1"
          :rules="[(val) => val >= 1 || 'Must be at least 1']"
        ></q-input>
      </div>

      <div class="flex justify-end q-mt-md q-gutter-x-sm">
        <q-btn
          flat
          label="Cancel"
          @click="store.router.push({ name: 'admin-ticket-sales' })"
        ></q-btn>
        <q-btn
          type="submit"
          label="Save"
          color="primary"
          :loading="loading"
        ></q-btn>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { useRoute } from "vue-router";
import { formatISO } from "date-fns";

const store = useStore();
const route = useRoute();

const loading = ref(false);
const isEdit = route.name === "admin-ticket-sale-edit";
const existingSale = isEdit
  ? store.admin.ticket_sales.find((s) => s.id == route.params.id)
  : null;

const performanceOptions = computed(() => {
  const now = new Date();
  return (store.admin.show?.performances ?? []).map((p) => {
    let label = `${p.formatted_date} ${p.formatted_time}`;
    return { label, value: p };
  });
});

const paymentMethodOptions = computed(() =>
  (store.paymentMethods ?? []).map((m) => ({
    label: m.label,
    value: m,
  })),
);

const form = ref(
  isEdit && existingSale
    ? {
        email: existingSale.patron.email,
        first_name: existingSale.patron.first_name,
        last_name: existingSale.patron.last_name,
        phone: existingSale.patron.phone,
        performance: {
          label: `${existingSale.performance.formatted_date} ${existingSale.performance.formatted_time}`,
          value: existingSale.performance,
          disable: false,
        },
        payment_method: {
          label: existingSale.payment_method.label,
          value: existingSale.payment_method,
        },
        quantity: existingSale.quantity,
      }
    : {
        email: null,
        first_name: null,
        last_name: null,
        phone: null,
        performance: null,
        payment_method: null,
        quantity: 1,
      },
);

const getPatron = async () => {
  if (!form.value.email) return;

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

  const payload = {
    email: form.value.email,
    first_name: form.value.first_name,
    last_name: form.value.last_name,
    phone: form.value.phone,
    performance_id: form.value.performance?.value?.id,
    type: form.value.payment_method?.value?.value,
    quantity: form.value.quantity,
    send_mail: store.send_mail,
    transfer_date:
      form.value.type == "transfer"
        ? formatISO(new Date(), { representation: "date" })
        : null,
  };

  if (isEdit) payload.id = existingSale.id;

  const response = await callApi({
    path: "/ticket-sales",
    method: isEdit ? "put" : "post",
    useAuth: true,
    payload,
  });

  loading.value = false;

  if (response) {
    store.router.push({ name: "admin-ticket-sales" });
  }
};
</script>
