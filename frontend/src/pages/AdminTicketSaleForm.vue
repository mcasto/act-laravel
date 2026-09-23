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

        <q-checkbox
          v-if="isEdit"
          v-model="form.confirmed"
          label="Payment Confirmed"
        ></q-checkbox>

        <q-input
          v-if="isEdit"
          type="textarea"
          rows="2"
          label="Reason Changed"
          stack-label
          dense
          outlined
          v-model="form.reason_changed"
        ></q-input>

        <template v-if="ticketRows.length">
          <div class="text-caption text-grey-7 q-mt-sm">Tickets</div>
          <q-input
            v-for="(ticket, index) in ticketRows"
            :key="ticket.id ?? `new-${index}`"
            type="text"
            :label="isEdit ? `#${ticket.formatted_number}` : `Ticket ${index + 1}`"
            :hint="!isEdit && index === 0 ? 'Defaults to the purchaser — change if this ticket is for someone else' : undefined"
            stack-label
            dense
            outlined
            :model-value="ticket.name"
            @update:model-value="(val) => onTicketNameInput(index, val)"
          ></q-input>
        </template>
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
          :disable="isReadOnly"
        ></q-btn>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import { useRoute } from "vue-router";
import { formatISO } from "date-fns";

const store = useStore();
const route = useRoute();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "ticket-sales") === "read-only",
);

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
        confirmed: !!existingSale.confirmed,
        reason_changed: existingSale.reason_changed,
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

// A comp row (merged into store.admin.ticket_sales by TicketSaleController::allSales())
// has no `tickets` array, only a single `number` — this section only applies
// to real ticket_sales rows. On create there's no existingSale at all yet,
// so seed one blank row per unit of quantity instead — the backend assigns
// real ticket ids/numbers at save time and autofills anything still blank
// (see TicketSale::issueTickets()).
const ticketRows = ref(
  isEdit
    ? [...(existingSale?.tickets ?? [])].sort((a, b) => a.number - b.number)
    : Array.from({ length: form.value.quantity || 1 }, () => ({ name: "" })),
);

const onTicketNameInput = (index, value) => {
  ticketRows.value[index].name = value;
  if (index === 0) ticket0Touched.value = true;
};

// The first ticket defaults to the purchaser's own name (kept in sync with
// the First/Last Name fields, in case they're filled in or corrected after
// quantity is already set) until the admin actually types something into
// that field themselves — after that it's just a normal independent value.
const ticket0Touched = ref(false);

if (!isEdit) {
  watch(
    () => [form.value.first_name, form.value.last_name],
    ([firstName, lastName]) => {
      if (ticket0Touched.value || !ticketRows.value[0]) return;
      ticketRows.value[0].name = `${firstName ?? ""} ${lastName ?? ""}`.trim();
    },
    { immediate: true },
  );

  // Resize the Tickets section as quantity changes — grow appends blank
  // rows, shrink drops from the end (preserving already-typed names on the
  // remaining rows).
  watch(
    () => form.value.quantity,
    (newQty) => {
      const qty = Math.max(1, Number(newQty) || 1);
      if (qty > ticketRows.value.length) {
        while (ticketRows.value.length < qty) {
          ticketRows.value.push({ name: "" });
        }
      } else if (qty < ticketRows.value.length) {
        ticketRows.value.length = qty;
      }
    },
  );
}

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

  if (isEdit) {
    payload.id = existingSale.id;
    payload.confirmed = form.value.confirmed;
    payload.reason_changed = form.value.reason_changed;
    if (ticketRows.value.length) {
      payload.tickets = ticketRows.value.map((t) => ({ id: t.id, name: t.name }));
    }
  } else if (ticketRows.value.length) {
    // Positional array of guest names, parallel to quantity — blank
    // entries are autofilled server-side (see TicketSale::issueTickets()).
    payload.tickets = ticketRows.value.map((t) => t.name);
  }

  const response = await callApi({
    path: isEdit ? "/ticket-sales" : "/admin/ticket-sales",
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
