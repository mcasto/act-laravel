<template>
  <div>
    <div>
      <q-card bordered class="q-mx-md">
        <q-card-section>
          <q-input
            type="email"
            label="Your Email"
            stack-label
            dense
            outlined
            v-model="form.email"
            :readonly="verified"
            :rules="[
              (v) => !!v || 'Required',
              (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Invalid email',
            ]"
            @keyup.enter="!verified && verify()"
          >
            <template v-slot:append>
              <q-btn
                v-if="!verified"
                label="Continue"
                color="primary"
                dense
                no-caps
                :loading="verifying"
                :disable="!form.email"
                @click="verify"
              ></q-btn>
              <q-btn
                v-else
                label="Change"
                flat
                dense
                no-caps
                color="primary"
                @click="resetVerification"
              ></q-btn>
            </template>
          </q-input>
        </q-card-section>

        <template v-if="verified">
          <q-toolbar v-if="hasTickets">
            <q-toolbar-title>
              Your Summary
              <div class="text-caption">
                Remaining Flex Tickets: {{ flex.remaining }}
              </div>
            </q-toolbar-title>
            <q-btn label="History" color="primary">
              <q-menu>
                <q-card>
                  <q-card-section>
                    <q-table
                      :rows="flex.usage"
                      dense
                      :hide-bottom="flex.usage.length > 0"
                      :pagination="{ rowsPerPage: 0 }"
                      :columns="historyColumns"
                    ></q-table>
                  </q-card-section>
                </q-card>
              </q-menu>
            </q-btn>
          </q-toolbar>

          <q-toolbar v-else>
            <div class="text-subtitle2 text-red">
              {{ noTicketsMessage }}
            </div>
          </q-toolbar>
        </template>

        <q-form v-if="hasTickets" @submit.prevent="onSubmit">
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
import { format, parseISO } from "date-fns";
import { clone } from "lodash-es";
import callApi from "src/assets/call-api";
import notifySubmitError from "src/assets/notify-submit-error";
import { useStore } from "src/stores/store";
import { computed, ref, watch } from "vue";

const props = defineProps(["performance"]);
const store = useStore();

const loading = ref(false);
const verifying = ref(false);
const verified = ref(!!store.patron);

const form = ref({
  type: "flex",
  email: store.patron?.email || null,
  first_name: null,
  last_name: null,
  phone: null,
  quantity: null,
  special_request: "",
});

const flex = ref({ purchased: null, remaining: null, usage: null });

if (store.patron) {
  const flexPackage = store.patron.flex_packages?.[0];
  flex.value = flexPackage
    ? {
        purchased: flexPackage.tickets_purchased,
        remaining: flexPackage.tickets_remaining,
        usage: flexPackage.usage,
      }
    : { purchased: null, remaining: 0, usage: [] };
}

watch(
  () => form.value.email,
  () => {
    verified.value = false;
  },
);

const historyColumns = [
  {
    label: "Show",
    name: "show",
    field: "show",
    align: "left",
  },
  {
    label: "Date",
    name: "date",
    field: (row) => format(parseISO(row.date), "PP"),
    align: "left",
  },
  {
    label: "Quantity",
    name: "quantity",
    field: "quantity",
    align: "left",
  },
];

const hasTickets = computed(
  () => verified.value && !!store.patron && flex.value.remaining > 0,
);

const noTicketsMessage = computed(() => {
  if (!store.patron || store.patron.flex_packages?.length === 0) {
    return "You haven't purchased a Flex package for this season.";
  }

  return "You have no remaining Flex tickets for this season.";
});

const verify = async () => {
  verifying.value = true;

  const patron = await callApi({
    path: `/patrons/lookup?email=${form.value.email}`,
    method: "get",
    showError: false,
  }).catch(() => null);

  verifying.value = false;
  verified.value = true;

  if (!patron) {
    store.patron = null;
    form.value.first_name = null;
    form.value.last_name = null;
    form.value.phone = null;
    flex.value = { purchased: null, remaining: null, usage: null };

    return;
  }

  store.patron = patron;

  const flexPackage = patron.flex_packages[0];
  flex.value = flexPackage
    ? {
        purchased: flexPackage.tickets_purchased,
        remaining: flexPackage.tickets_remaining,
        usage: flexPackage.usage,
      }
    : { purchased: null, remaining: 0, usage: [] };
};

const resetVerification = () => {
  verified.value = false;
  store.patron = null;
  form.value.email = null;
  form.value.first_name = null;
  form.value.last_name = null;
  form.value.phone = null;
  flex.value = { purchased: null, remaining: null, usage: null };
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
