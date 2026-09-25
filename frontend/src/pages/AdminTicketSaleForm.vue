<template>
  <div>
    <div class="text-h6 q-mb-md">
      {{ isEdit ? "Edit Ticket Sale" : "New Ticket Sale" }}
    </div>

    <q-form @submit.prevent="onSubmit">
      <q-select
        label="Performance"
        stack-label
        dense
        outlined
        bg-color="white"
        class="q-mb-md"
        style="max-width: 560px;"
        v-model="form.performance"
        :options="performanceOptions"
        :option-disable="(opt) => opt.disable"
        :rules="[(val) => !!val || 'Required']"
      ></q-select>

      <div class="row q-col-gutter-lg">
        <div class="col-12 col-md-4">
          <div class="q-gutter-y-md">
          <q-card flat bordered>
            <q-card-section>
              <div class="row items-center q-gutter-x-sm text-subtitle1 text-weight-medium q-mb-md">
                <q-icon :name="matPerson" color="primary" size="xs" />
                <div>Purchaser</div>
              </div>

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

                <div class="row q-col-gutter-sm">
                  <q-input
                    class="col-6"
                    type="text"
                    label="First Name"
                    :hint="patronFound ? 'Existing patron — edit on Patron Management' : undefined"
                    stack-label
                    dense
                    outlined
                    v-model="form.first_name"
                    :disable="patronFound"
                    :rules="[(val) => !!val || 'Required']"
                  ></q-input>

                  <q-input
                    class="col-6"
                    type="text"
                    label="Last Name"
                    :hint="patronFound ? 'Existing patron — edit on Patron Management' : undefined"
                    stack-label
                    dense
                    outlined
                    v-model="form.last_name"
                    :disable="patronFound"
                    :rules="[(val) => !!val || 'Required']"
                  ></q-input>
                </div>

                <q-input
                  type="tel"
                  label="Phone / WhatsApp"
                  :hint="patronFound ? 'Existing patron — edit on Patron Management' : undefined"
                  stack-label
                  dense
                  outlined
                  v-model="form.phone"
                  :disable="patronFound"
                ></q-input>
              </div>
            </q-card-section>
          </q-card>

          <q-card flat bordered>
            <q-card-section>
              <div class="row items-center q-gutter-x-sm text-subtitle1 text-weight-medium">
                <q-icon :name="matBadge" color="primary" size="xs" />
                <div>Door Name</div>
              </div>
              <div class="text-caption text-grey-7 q-mb-sm" v-if="!isEdit">
                Defaults to the purchaser — change to override what prints at the door
              </div>

              <div class="row q-col-gutter-sm q-mt-none">
                <q-input
                  class="col-6"
                  type="text"
                  label="First Name"
                  stack-label
                  dense
                  outlined
                  :model-value="form.door_first"
                  @update:model-value="onDoorFirstInput"
                ></q-input>

                <q-input
                  class="col-6"
                  type="text"
                  label="Last Name"
                  stack-label
                  dense
                  outlined
                  :model-value="form.door_last"
                  @update:model-value="onDoorLastInput"
                ></q-input>
              </div>
            </q-card-section>
          </q-card>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="q-gutter-y-md">
          <q-card flat bordered>
            <q-card-section>
              <div class="row items-center q-gutter-x-sm text-subtitle1 text-weight-medium q-mb-md">
                <q-icon :name="matPayment" color="primary" size="xs" />
                <div>Payment</div>
              </div>

              <div class="q-gutter-y-sm">
                <div class="row q-col-gutter-sm">
                  <q-select
                    class="col-7"
                    label="Payment Method"
                    stack-label
                    dense
                    outlined
                    v-model="form.payment_method"
                    :options="paymentMethodOptions"
                    :rules="[(val) => !!val || 'Required']"
                  ></q-select>

                  <q-input
                    class="col-5"
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

                <q-checkbox
                  :model-value="form.confirmed"
                  label="Payment Confirmed"
                  @update:model-value="onConfirmedInput"
                ></q-checkbox>

                <q-input
                  v-if="isEdit"
                  type="textarea"
                  rows="2"
                  label="Reason Changed"
                  stack-label
                  dense
                  outlined
                  class="q-mt-sm"
                  v-model="form.reason_changed"
                ></q-input>
              </div>
            </q-card-section>
          </q-card>

          <q-card flat bordered>
            <q-card-section>
              <div class="row items-center q-gutter-x-sm text-subtitle1 text-weight-medium q-mb-md">
                <q-icon :name="matEventSeat" color="primary" size="xs" />
                <div>Seating &amp; Notes</div>
              </div>

              <div class="q-gutter-y-sm">
                <q-input
                  type="number"
                  label="Front Row"
                  hint="Reserved front-row seats for this party (e.g. an Angel level's reserved seating) — not the patron's own accessibility need"
                  stack-label
                  dense
                  outlined
                  :model-value="form.front_row"
                  min="0"
                  max="20"
                  step="1"
                  @update:model-value="onFrontRowInput"
                  :rules="[(val) => (val >= 0 && val <= 20) || 'Must be a whole number between 0 and 20']"
                ></q-input>

                <q-input
                  type="textarea"
                  rows="2"
                  label="Special Seating"
                  hint="Notes about seating other than front row, e.g. aisle seat"
                  stack-label
                  dense
                  outlined
                  class="q-mt-md"
                  v-model="form.special_seating"
                ></q-input>

                <q-input
                  type="textarea"
                  rows="2"
                  label="Comments"
                  hint="Defaults to the patron's notes — change to override for this sale"
                  stack-label
                  dense
                  outlined
                  class="q-mt-md"
                  :model-value="form.comments"
                  @update:model-value="onCommentsInput"
                ></q-input>
              </div>
            </q-card-section>
          </q-card>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="q-gutter-y-md">
          <q-card v-if="seatingSummary" flat bordered class="bg-blue-1">
            <q-card-section>
              <div class="text-subtitle2 text-grey-8">Seating Summary</div>
              <div class="row q-gutter-x-xl q-mt-sm">
                <div>
                  <div class="text-h3 text-weight-bold text-primary">
                    {{ seatingSummary.front_row_total }}
                  </div>
                  <div class="text-caption text-grey-7">Front Row</div>
                </div>
                <div>
                  <div class="text-h3 text-weight-bold">
                    {{ seatingSummary.reservations_total }}<span class="text-h5 text-grey-6">/{{ seatingSummary.sold_out_target }}</span>
                  </div>
                  <div class="text-caption text-grey-7">Reservations</div>
                </div>
              </div>
            </q-card-section>
          </q-card>

          <q-card flat bordered v-if="ticketRows.length">
            <q-card-section>
              <div class="row items-center q-gutter-x-sm text-subtitle1 text-weight-medium q-mb-md">
                <q-icon :name="matConfirmationNumber" color="primary" size="xs" />
                <div>Tickets</div>
              </div>

              <div class="q-gutter-y-sm">
                <q-input
                  v-for="(ticket, index) in ticketRows"
                  :key="ticket.id ?? `new-${index}`"
                  type="text"
                  :label="isEdit ? `#${ticket.formatted_number}` : `Ticket ${index + 1}`"
                  :hint="!isEdit && index === 0 ? 'Defaults to Door Name — change if this ticket is for someone else' : undefined"
                  stack-label
                  dense
                  outlined
                  :model-value="ticket.name"
                  @update:model-value="(val) => onTicketNameInput(index, val)"
                ></q-input>
              </div>
            </q-card-section>
          </q-card>
          </div>
        </div>
      </div>

      <div class="flex justify-end q-mt-lg q-gutter-x-sm">
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
import {
  matBadge,
  matConfirmationNumber,
  matEventSeat,
  matPayment,
  matPerson,
} from "@quasar/extras/material-icons";
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

// firstOrCreate() on the backend only uses submitted first/last name when
// CREATING a brand new patron — editing them is silently ignored once a
// patron with that email already exists, so those fields are disabled
// whenever one's found rather than let the admin type changes that won't
// actually save. An edit always starts against an existing sale's
// already-known patron.
const patronFound = ref(isEdit);

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
        front_row: existingSale.front_row || 0,
        special_seating: existingSale.special_seating || "",
        // Fallback covers a record saved before this field existed and
        // not yet caught by the one-time backfill command.
        door_last: existingSale.door_last ?? existingSale.patron.last_name,
        door_first: existingSale.door_first ?? existingSale.patron.first_name,
        comments: existingSale.comments ?? existingSale.patron.comments ?? "",
      }
    : {
        email: null,
        first_name: null,
        last_name: null,
        phone: null,
        performance: null,
        payment_method: null,
        quantity: 1,
        confirmed: false,
        front_row: 0,
        special_seating: "",
        door_last: "",
        door_first: "",
        comments: "",
      },
);

// Shown once a performance is picked (or immediately on edit, where it's
// already picked) — how many front-row seats are already committed for
// that performance and how full it is, so the admin has that context
// before adding another sale.
const seatingSummary = ref(null);

watch(
  () => form.value.performance?.value?.id,
  async (performanceId) => {
    if (!performanceId) {
      seatingSummary.value = null;
      return;
    }

    seatingSummary.value = await callApi({
      path: `/admin/performances/${performanceId}/seating-summary`,
      method: "get",
      useAuth: true,
      showError: false,
    }).catch(() => null);
  },
  { immediate: true },
);

const onConfirmedInput = (val) => {
  form.value.confirmed = val;
  confirmedTouched.value = true;
};

// Clearing the field (or pasting something non-numeric) emits "" or null
// rather than a number — normalize that to 0 immediately instead of
// letting a non-integer value reach submit, where the backend's
// integer-only validation would silently reject it.
const onFrontRowInput = (val) => {
  const num = Number(val);
  form.value.front_row = Number.isInteger(num) ? num : 0;
};

// On create there's no existingSale at all yet, so seed one blank row per
// unit of quantity instead — the backend assigns real ticket ids/numbers
// at save time and autofills anything still blank (see
// TicketSale::issueTickets()).
const ticketRows = ref(
  isEdit
    ? [...(existingSale?.tickets ?? [])].sort((a, b) => a.number - b.number)
    : Array.from({ length: form.value.quantity || 1 }, () => ({ name: "" })),
);

const onTicketNameInput = (index, value) => {
  ticketRows.value[index].name = value;
  if (index === 0) ticket0Touched.value = true;
};

// The first ticket defaults to Door Name (not the purchaser's First/Last
// Name directly) — chained: patron lookup -> Door Name -> Ticket 1, each
// link overridable independently. So Ticket 1 keeps following Door Name
// (even after Door Name itself has been overridden) right up until the
// admin actually types into Ticket 1 themselves — after that it's just a
// normal independent value, and typing into it never touches Door Name.
const ticket0Touched = ref(false);

// Comp & flex tickets are effectively pre-paid/no-cost, so payment is
// treated as confirmed by default — the admin can still uncheck it
// manually, which then sticks even if the payment method is changed again.
const confirmedTouched = ref(false);

// Door Name defaults to the purchaser's name (kept in sync as First/Last
// Name are filled in or corrected) until the admin overrides it — only on
// create. On edit it's just whatever's already stored; changing the
// purchaser's name shouldn't silently blow away a door-name override
// someone already set.
const doorNameTouched = ref(false);

const onDoorLastInput = (val) => {
  form.value.door_last = val;
  doorNameTouched.value = true;
};

const onDoorFirstInput = (val) => {
  form.value.door_first = val;
  doorNameTouched.value = true;
};

// Comments defaults to the found patron's own notes (see getPatron()
// below) until the admin overrides it for this sale specifically.
const commentsTouched = ref(false);

const onCommentsInput = (val) => {
  form.value.comments = val;
  commentsTouched.value = true;
};

if (!isEdit) {
  watch(
    () => [form.value.first_name, form.value.last_name],
    ([firstName, lastName]) => {
      if (doorNameTouched.value) return;
      form.value.door_first = firstName ?? "";
      form.value.door_last = lastName ?? "";
    },
    { immediate: true },
  );

  // Chained off Door Name (not directly off First/Last Name) — keeps
  // following Door Name even after Door Name has itself been overridden,
  // since Door Name -> Ticket 1 is its own independent default link.
  watch(
    () => [form.value.door_first, form.value.door_last],
    ([doorFirst, doorLast]) => {
      if (ticket0Touched.value || !ticketRows.value[0]) return;
      ticketRows.value[0].name = `${doorFirst ?? ""} ${doorLast ?? ""}`.trim();
    },
    { immediate: true },
  );
}

if (!isEdit) {
  watch(
    () => form.value.payment_method,
    (pm) => {
      if (confirmedTouched.value) return;
      const type = pm?.value?.value;
      form.value.confirmed = type === "comp" || type === "flex";
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
  if (!form.value.email) {
    patronFound.value = false;
    return;
  }

  const patron = await callApi({
    path: `/patrons/lookup?email=${form.value.email}`,
    method: "get",
    showError: false,
  }).catch(() => null);

  patronFound.value = !!patron;

  // No match means this email isn't tied to an existing patron — clear the
  // name/phone fields instead of leaving whatever patron's info happened
  // to be there before, which would otherwise misleadingly suggest this
  // email already belongs to that person.
  form.value.first_name = patron?.first_name ?? "";
  form.value.last_name = patron?.last_name ?? "";
  form.value.phone = patron?.phone ?? "";

  if (!commentsTouched.value) {
    form.value.comments = patron?.comments ?? "";
  }
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
    confirmed: form.value.confirmed,
    front_row: form.value.front_row,
    special_seating: form.value.special_seating || null,
    door_last: form.value.door_last || null,
    door_first: form.value.door_first || null,
    comments: form.value.comments || null,
    send_mail: store.send_mail,
    transfer_date:
      form.value.type == "transfer"
        ? formatISO(new Date(), { representation: "date" })
        : null,
  };

  if (isEdit) {
    payload.id = existingSale.id;
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
