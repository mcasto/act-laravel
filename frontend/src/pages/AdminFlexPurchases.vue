<template>
  <div class="q-pa-md">
    <div v-if="store.admin.flex_purchases.length === 0" class="text-grey-6 q-pa-md">
      No flex purchases on record.
      <div class="q-mt-sm">
        <q-btn
          color="primary"
          :icon="matAdd"
          label="Add Purchase"
          :disable="isReadOnly"
          @click="openDialog()"
        />
      </div>
    </div>

    <q-table
      v-else
      :rows="filteredRows"
      :columns="columns"
      row-key="id"
      dense
      v-model:pagination="pagination"
    >
      <template #top>
        <div class="full-width">
          <div class="row items-center justify-between q-mb-md">
            <div class="text-h6">Flex Purchases</div>
            <q-btn
              color="primary"
              :icon="matAdd"
              label="Add Purchase"
              :disable="isReadOnly"
              @click="openDialog()"
            />
          </div>
          <div class="flex no-wrap items-center q-gutter-x-sm q-mb-sm">
            <q-select
              :options="seasons"
              v-model="seasonFilter"
              label="Season"
              dense
              outlined
              stack-label
              style="min-width: 8rem;"
            />
            <q-space />
            <q-input
              v-model="search"
              dense
              outlined
              placeholder="Search by name or email..."
              clearable
              style="min-width: 220px;"
              debounce="300"
            >
              <template #prepend>
                <q-icon :name="matSearch" />
              </template>
            </q-input>
          </div>
        </div>
      </template>

      <template #body-cell-purchased_at="props">
        <q-td :props="props">
          {{ props.row.purchased_at ? format(parseISO(props.row.purchased_at), "PP") : "—" }}
        </q-td>
      </template>

      <template #body-cell-payment_method="props">
        <q-td :props="props">
          {{ props.row.payment_method?.label ?? "—" }}
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props" class="text-right">
          <q-btn
            :icon="matEdit"
            flat
            round
            dense
            size="sm"
            color="primary"
            :disable="isReadOnly"
            @click="openDialog(props.row)"
          />
          <q-btn
            :icon="matDelete"
            flat
            round
            dense
            size="sm"
            color="negative"
            :disable="isReadOnly"
            @click="deletePackage(props.row)"
          />
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialog" persistent>
      <q-card style="min-width: 400px;">
        <q-card-section>
          <div class="text-h6">
            {{ form.id ? "Edit" : "Add" }} Flex Purchase
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <template v-if="!form.id">
            <q-btn-toggle
              v-model="patronMode"
              spread
              no-caps
              toggle-color="primary"
              outline
              :options="[
                { label: 'Existing Patron', value: 'existing' },
                { label: 'New Patron', value: 'new' },
              ]"
              class="q-mb-md full-width"
            />

            <template v-if="patronMode === 'existing'">
              <q-select
                v-model="selectedPatron"
                :options="patronSearchResults"
                use-input
                hide-selected
                fill-input
                input-debounce="300"
                label="Search by name or email"
                outlined
                dense
                class="q-mb-md"
                :option-label="(p) => `${p.first_name} ${p.last_name} — ${p.email}`"
                @filter="onPatronFilter"
                @update:model-value="onPatronSelected"
              >
                <template #no-option>
                  <q-item>
                    <q-item-section class="text-grey">
                      Type at least 2 characters to search
                    </q-item-section>
                  </q-item>
                </template>
              </q-select>
              <div v-if="selectedPatron" class="text-caption text-grey-7 q-mb-md">
                Selected: {{ selectedPatron.first_name }} {{ selectedPatron.last_name }} ({{ selectedPatron.email }})
              </div>
            </template>

            <template v-else>
              <q-input
                v-model="form.email"
                type="email"
                label="Email"
                outlined
                dense
                class="q-mb-md"
                @blur="getPatron"
                hint="If this email already exists, we'll use that patron instead of creating a duplicate."
              />
              <q-input
                v-model="form.first_name"
                label="First Name"
                outlined
                dense
                class="q-mb-md"
              />
              <q-input
                v-model="form.last_name"
                label="Last Name"
                outlined
                dense
                class="q-mb-md"
              />
              <q-input
                v-model="form.phone"
                label="Phone (optional)"
                outlined
                dense
                class="q-mb-md"
              />
            </template>
          </template>

          <q-select
            v-model="form.season"
            :options="seasonOptions"
            label="Season"
            outlined
            dense
            class="q-mb-md"
          />
          <q-input
            v-model.number="form.tickets_purchased"
            type="number"
            label="Tickets Purchased"
            min="1"
            outlined
            dense
            class="q-mb-md"
          />
          <q-select
            v-model="form.payment_method_value"
            :options="paymentMethodOptions"
            label="Payment Method"
            emit-value
            map-options
            outlined
            dense
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            @click="save"
            :disable="!canSave"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { matAdd, matDelete, matEdit, matSearch } from "@quasar/extras/material-icons";
import { format, parseISO } from "date-fns";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import { computed, onMounted, ref } from "vue";

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "flex-purchases") === "read-only",
);

const search = ref("");
const pagination = ref({ page: 1, rowsPerPage: 12 });

const seasons = computed(() =>
  [...new Set(store.admin.flex_purchases.map((row) => row.season))].sort(
    (a, b) => (a < b ? 1 : -1),
  ),
);

const seasonFilter = ref(seasons.value[0] ?? null);

// Theater seasons run October 1 - August 31 (see App\Helpers\TheaterSeason
// on the backend, which this mirrors) — the "current" season doesn't flip
// on a naive calendar-year boundary.
const currentSeasonString = () => {
  const now = new Date();
  const startYear = now.getMonth() >= 9 ? now.getFullYear() : now.getFullYear() - 1;
  return `${String(startYear).slice(-2)}-${String(startYear + 1).slice(-2)}`;
};

const nextSeasonString = () => {
  const [startShort] = currentSeasonString().split("-");
  const startYear = 2000 + parseInt(startShort, 10) + 1;
  return `${String(startYear).slice(-2)}-${String(startYear + 1).slice(-2)}`;
};

const seasonOptions = computed(() => {
  const opts = [currentSeasonString(), nextSeasonString()];
  // Editing an older record shouldn't silently lose its actual season.
  if (form.value.season && !opts.includes(form.value.season)) {
    opts.unshift(form.value.season);
  }
  return opts;
});

const columns = [
  {
    name: "name",
    label: "Name",
    field: (row) => `${row.first_name} ${row.last_name}`,
    align: "left",
    sortable: true,
  },
  {
    name: "email",
    label: "Email",
    field: "email",
    align: "left",
    sortable: true,
  },
  {
    name: "season",
    label: "Season",
    field: "season",
    align: "center",
    sortable: true,
  },
  {
    name: "tickets_purchased",
    label: "Tickets",
    field: "tickets_purchased",
    align: "center",
    sortable: true,
  },
  {
    name: "payment_method",
    label: "Payment Method",
    field: (row) => row.payment_method?.label,
    align: "left",
  },
  {
    name: "purchased_at",
    label: "Purchased",
    field: "purchased_at",
    align: "left",
    sortable: true,
  },
  {
    name: "actions",
    label: "",
    field: "",
    align: "right",
  },
];

const rows = computed(() => {
  if (!seasonFilter.value) return store.admin.flex_purchases;
  return store.admin.flex_purchases.filter((row) => row.season === seasonFilter.value);
});

const filteredRows = computed(() => {
  if (!search.value) return rows.value;
  const q = search.value.toLowerCase();
  return rows.value.filter(
    (row) =>
      `${row.first_name} ${row.last_name}`.toLowerCase().includes(q) ||
      row.email.toLowerCase().includes(q),
  );
});

const paymentMethods = ref([]);
const paymentMethodOptions = computed(() =>
  paymentMethods.value.map((pm) => ({ label: pm.label, value: pm.value })),
);

onMounted(async () => {
  paymentMethods.value = await callApi({
    path: "/payment-methods",
    method: "get",
    useAuth: true,
  });
});

const dialog = ref(false);
const form = ref({
  id: null,
  email: "",
  first_name: "",
  last_name: "",
  phone: "",
  season: "",
  tickets_purchased: null,
  payment_method_value: null,
});

const patronMode = ref("existing");
const selectedPatron = ref(null);
const patronSearchResults = ref([]);

const onPatronFilter = (val, update) => {
  if (val.length < 2) {
    update(() => {
      patronSearchResults.value = [];
    });
    return;
  }

  update(async () => {
    patronSearchResults.value = await callApi({
      path: `/admin/patrons/search?q=${encodeURIComponent(val)}`,
      method: "get",
      useAuth: true,
      showError: false,
    }).catch(() => []);
  });
};

const onPatronSelected = (patron) => {
  if (!patron) return;
  form.value.email = patron.email;
  form.value.first_name = patron.first_name;
  form.value.last_name = patron.last_name;
  form.value.phone = patron.phone ?? "";
};

const canSave = computed(() => {
  if (!form.value.season || !form.value.tickets_purchased || !form.value.payment_method_value) {
    return false;
  }
  if (!form.value.id) {
    if (patronMode.value === "existing") return !!selectedPatron.value;
    return !!form.value.email && !!form.value.first_name && !!form.value.last_name;
  }
  return true;
});

const openDialog = (pkg = null) => {
  patronMode.value = "existing";
  selectedPatron.value = null;
  patronSearchResults.value = [];

  form.value = pkg
    ? {
        id: pkg.id,
        email: pkg.email,
        first_name: pkg.first_name,
        last_name: pkg.last_name,
        phone: "",
        season: pkg.season,
        tickets_purchased: pkg.tickets_purchased,
        payment_method_value: pkg.payment_method?.value ?? null,
      }
    : {
        id: null,
        email: "",
        first_name: "",
        last_name: "",
        phone: "",
        season: currentSeasonString(),
        tickets_purchased: null,
        payment_method_value: null,
      };
  dialog.value = true;
};

const getPatron = async () => {
  if (!form.value.email) return;

  const patron = await callApi({
    path: `/patrons/lookup?email=${form.value.email}`,
    method: "get",
    useAuth: true,
    showError: false,
  }).catch(() => null);

  if (!patron) return;

  form.value.first_name = patron.first_name;
  form.value.last_name = patron.last_name;
  form.value.phone = patron.phone ?? "";
};

const reload = async () => {
  store.admin.flex_purchases = await callApi({
    path: "/admin/flex-purchases",
    method: "get",
    useAuth: true,
  });
};

const save = async () => {
  const isEdit = !!form.value.id;

  const payload = isEdit
    ? {
        season: form.value.season,
        tickets_purchased: form.value.tickets_purchased,
        payment_method_value: form.value.payment_method_value,
      }
    : {
        email: form.value.email,
        first_name: form.value.first_name,
        last_name: form.value.last_name,
        phone: form.value.phone || null,
        season: form.value.season,
        tickets_purchased: form.value.tickets_purchased,
        payment_method_value: form.value.payment_method_value,
      };

  const response = await callApi({
    path: isEdit ? `/admin/flex-purchases/${form.value.id}` : "/admin/flex-purchases",
    method: isEdit ? "put" : "post",
    payload,
    useAuth: true,
  });

  if (!response || response.status !== "success") {
    Notify.create({
      type: "negative",
      message: response?.message || "Something went wrong.",
    });
    return;
  }

  Notify.create({
    type: "positive",
    message: `Flex purchase ${isEdit ? "updated" : "added"}.`,
  });
  dialog.value = false;
  await reload();
};

const deletePackage = (pkg) => {
  Notify.create({
    type: "warning",
    position: "center",
    message: `Delete this Flex purchase (${pkg.tickets_purchased} tickets, ${pkg.season}) for ${pkg.first_name} ${pkg.last_name}? This can't be undone.`,
    actions: [
      { label: "No" },
      {
        label: "Yes",
        handler: async () => {
          const response = await callApi({
            path: `/admin/flex-purchases/${pkg.id}`,
            method: "delete",
            useAuth: true,
          });

          if (!response || response.status !== "success") {
            Notify.create({
              type: "negative",
              message: response?.message || "Delete failed.",
            });
            return;
          }

          await reload();
        },
      },
    ],
  });
};
</script>
