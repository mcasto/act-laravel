<template>
  <div>
    <div>
      <q-card bordered class="q-mx-md">
        <q-toolbar
          v-if="store.patron && store.patron?.flex_packages.length > 0"
        >
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
          <div class="text-subtitle2">
            {{ header }}
          </div>
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
              v-if="store.patron && store.patron?.flex_packages.length > 0"
            ></q-input>
            <q-input
              type="email"
              label="Email"
              stack-label
              dense
              outlined
              v-model="form.email"
              @blur="getPatron"
              :rules="(v) => !!v || 'Required'"
            ></q-input>
            <q-input
              type="text"
              label="First Name"
              stack-label
              dense
              outlined
              v-model="form.first_name"
              :rules="(v) => !!v || 'Required'"
            ></q-input>
            <q-input
              type="text"
              label="Last Name"
              stack-label
              dense
              outlined
              v-model="form.last_name"
              :rules="(v) => !!v || 'Required'"
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
            <q-btn
              type="submit"
              label="Continue"
              color="primary"
              :loading="loading"
              :disabled="
                !store.patron || store.patron?.flex_packages.length == 0
              "
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
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const props = defineProps(["performance"]);
const store = useStore();

const loading = ref(null);

const form = ref({
  type: "flex",
  email: store.patron?.email || null,
  first_name: store.patron?.first_name || null,
  last_name: store.patron?.last_name || null,
  phone: store.patron?.phone || null,
  quantity: null,
});

const flex = ref({ purchased: null, remaining: null, usage: null });

if (store.patron?.flex_packages.length > 0) {
  const flexPackage = store.patron.flex_packages[0];
  flex.value = {
    purchased: flexPackage.tickets_purchased,
    remaining: flexPackage.tickets_remaining,
    usage: flexPackage.usage,
  };
}

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

const header = computed(() => {
  if (!form.value.email) {
    return "To see your Flex summary and history, enter your email address and press `tab`";
  }

  if (!store.patron || store.patron?.flex_packages.length == 0) {
    return "You haven't purchased a Flex package for this season.";
  }

  return "";
});

const getPatron = async () => {
  const patron = await callApi({
    path: `/patrons/lookup?email=${form.value.email}`,
    method: "get",
    showError: false,
  }).catch(() => null);

  if (!patron) {
    store.patron = null;
    form.value.first_name = null;
    form.value.last_name = null;
    form.value.phone = null;

    return;
  }

  store.patron = patron;

  form.value.first_name = patron.first_name;
  form.value.last_name = patron.last_name;
  form.value.phone = patron.phone;

  if (patron.flex_packages.length > 0) {
    const flexPackage = patron.flex_packages[0];
    flex.value = {
      purchased: flexPackage.tickets_purchased,
      remaining: flexPackage.tickets_remaining,
      usage: flexPackage.usage,
    };
  }
};

const onSubmit = async () => {
  loading.value = true;

  const payload = clone(form.value);
  payload.performance_id = props.performance.id;

  const response = await callApi({
    path: "/ticket-sales",
    method: "post",
    payload,
  });

  if (response.transaction_id) {
    const store = useStore();
    store.router.push({
      name: "ticket-confirmation",
      params: { uuid: response.transaction_id },
    });
  }
};
</script>
