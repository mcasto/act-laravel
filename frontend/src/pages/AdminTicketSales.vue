<template>
  <div>
    <div v-if="shows.length === 0" class="text-grey-6 q-pa-md">
      No ticket sales on record.
    </div>

    <q-table
      v-else
      :rows="filteredRecs"
      :columns="columns"
      row-key="id"
      dense
      v-model:pagination="pagination"
    >
      <template #top>
        <div class="full-width">
          <div class="flex no-wrap items-center q-gutter-x-sm q-mb-sm">
            <q-select
              :options="shows"
              v-model="show"
              :label="show ? 'Show' : 'Select Show'"
              dense
              outlined
              stack-label
              style="min-width: 8rem;"
            />
            <q-space />
            <template v-if="show">
              <q-toggle
                label="Send Emails"
                v-model="store.send_mail"
                :false-value="0"
                :true-value="1"
                :disable="isReadOnly"
              ></q-toggle>
              <q-btn
                :icon="matInfo"
                flat
                round
                dense
                size="sm"
                @click="sendMailInfoDialog = true"
              ></q-btn>
              <q-btn
                :icon="matAdd"
                size="sm"
                color="primary"
                round
                :disable="isReadOnly"
                @click="onNewTicket"
              />
              <AdminTicketSalesPrint
                :recs="recs"
                :ticket-price="ticketPrice"
                :show-name="show.label"
              />
              <q-btn :icon="matEvent" color="info" flat>
                <q-tooltip>Tickets Sold By Date</q-tooltip>
                <q-menu>
                  <q-list dense style="min-width: 220px;">
                    <q-item-label header class="text-weight-bold"
                      >Tickets Sold By Date</q-item-label
                    >
                    <q-item v-for="row in ticketsByDate" :key="row.id">
                      <q-item-section>{{ row.label }}</q-item-section>
                      <q-item-section side class="text-weight-medium">
                        {{ row.count }}
                      </q-item-section>
                    </q-item>
                    <div
                      v-if="ticketsByDate.length === 0"
                      class="text-caption text-grey-7 q-pa-sm"
                    >
                      No confirmed sales yet.
                    </div>
                    <q-separator v-if="ticketsByDate.length" />
                    <q-item v-if="ticketsByDate.length">
                      <q-item-section class="text-weight-bold"
                        >Total</q-item-section
                      >
                      <q-item-section side class="text-weight-bold">
                        {{ confirmedTicketCount }}
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
              <q-btn :icon="mdiCashMultiple" color="positive" flat>
                <q-tooltip>Projected Revenue</q-tooltip>
                <q-menu>
                  <q-list dense style="min-width: 260px;">
                    <q-item-label header class="text-weight-bold"
                      >Projected Revenue</q-item-label
                    >
                    <q-item v-for="row in revenueByMethod" :key="row.label">
                      <q-item-section side>
                        <div
                          :style="`width: 10px; height: 10px; border-radius: 50%; background: ${row.color};`"
                        ></div>
                      </q-item-section>
                      <q-item-section>{{ row.label }}</q-item-section>
                      <q-item-section side
                        >{{ row.count }} × ${{ ticketPrice }}</q-item-section
                      >
                      <q-item-section
                        side
                        class="text-positive text-weight-medium"
                      >
                        ${{ row.revenue.toFixed(2) }}
                      </q-item-section>
                    </q-item>
                    <q-separator />
                    <q-item>
                      <q-item-section class="text-weight-bold"
                        >Total</q-item-section
                      >
                      <q-item-section side class="text-weight-bold">
                        {{ confirmedTicketCount }} tickets
                      </q-item-section>
                      <q-item-section
                        side
                        class="text-positive text-weight-bold"
                      >
                        ${{ totalRevenue.toFixed(2) }}
                      </q-item-section>
                    </q-item>
                    <q-item>
                      <q-item-section class="text-caption text-grey-7">
                        % Sold Out
                      </q-item-section>
                      <q-item-section side class="text-caption text-grey-7">
                        {{ totalTickets }} / {{ soldOutCapacity }} ({{
                          soldOutPct
                        }}%)
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </template>
          </div>
          <div class="flex no-wrap items-center q-gutter-x-md">
            <q-input
              v-model="search"
              dense
              outlined
              placeholder="Search by name..."
              clearable
              style="min-width: 200px;"
              debounce="300"
            >
              <template #prepend>
                <q-icon :name="matSearch" />
              </template>
            </q-input>
            <q-select
              v-if="show"
              :options="performanceOptions"
              v-model="performanceFilter"
              label="Performance Date"
              dense
              outlined
              clearable
              emit-value
              map-options
              style="min-width: 220px;"
            />
            <q-space />
            <template v-if="show">
              <div
                v-for="(color, label) in paymentMethodColors"
                :key="label"
                class="flex items-center q-gutter-x-xs"
              >
                <div
                  :style="`width: 10px; height: 10px; border-radius: 50%; background: ${color};`"
                ></div>
                <span class="text-caption">{{ label }}</span>
              </div>
            </template>
          </div>
          <div class="flex justify-end">
            <q-pagination
              v-if="pagesNumber > 1"
              v-model="pagination.page"
              :max="pagesNumber"
              :max-pages="6"
              boundary-links
              direction-links
              size="sm"
              class="q-mt-sm"
            />
          </div>
        </div>
      </template>

      <template #header-cell-payment_method="props">
        <q-th :props="props">
          <q-icon :name="mdiCurrencyUsd" />
        </q-th>
      </template>

      <template #header-cell-actions="props">
        <q-th :props="props">
          <q-icon :name="mdiCog" />
        </q-th>
      </template>

      <template #body-cell-no_show="props">
        <q-td :props="props">
          <q-toggle
            label="No Show"
            v-model="props.row.no_show"
            :false-value="0"
            :true-value="1"
            :disable="isReadOnly"
            @update:model-value="updateNoShow(props.row)"
          ></q-toggle>
        </q-td>
      </template>

      <template #body-cell-confirmed="props">
        <q-td :props="props" class="text-center">
          <q-icon
            v-if="props.row.confirmed"
            :name="mdiCheckBold"
            color="positive"
          >
            <q-tooltip>Payment confirmed</q-tooltip>
          </q-icon>
        </q-td>
      </template>

      <template #body-cell-info="props">
        <q-td :props="props">
          <q-btn :icon="matInfo" flat round size="sm">
            <q-menu>
              <q-list dense separator>
                <q-item>
                  <q-item-section side>Email:</q-item-section>
                  <q-item-section>
                    <q-item-label>
                      <a
                        :href="`mailto:${props.row.patron.first_name} ${props.row.patron.last_name} <${props.row.patron.email}>`"
                      >
                        {{ props.row.patron.email }}
                      </a>
                    </q-item-label>
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section side>Phone:</q-item-section>
                  <q-item-section>
                    <q-item-label>{{ props.row.patron.phone }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-menu>
          </q-btn>
        </q-td>
      </template>

      <template #body-cell-payment_method="props">
        <q-td :props="props" class="text-center">
          <q-icon
            :name="mdiCircle"
            :style="`color: ${
              paymentMethodColors[props.row.payment_method.label]
            };`"
          >
            <q-tooltip>{{ props.row.payment_method.label }}</q-tooltip>
          </q-icon>
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props">
          <q-btn
            :icon="matEdit"
            flat
            round
            color="primary"
            size="sm"
            :disable="isReadOnly"
            @click="onEditSale(props.row)"
          />
          <q-btn
            :icon="matDelete"
            flat
            round
            color="negative"
            size="sm"
            :disable="isReadOnly"
            @click="onDelete(props.row)"
          />
        </q-td>
      </template>

      <template #bottom>
        <div class="flex justify-end full-width">
          <q-pagination
            v-if="pagesNumber > 1"
            v-model="pagination.page"
            :max="pagesNumber"
            :max-pages="6"
            boundary-links
            direction-links
            size="sm"
          />
        </div>
      </template>
    </q-table>

    <q-dialog v-model="sendMailInfoDialog">
      <q-card style="max-width: 420px;">
        <q-card-section>
          <div class="text-h6">About "Send Emails"</div>
        </q-card-section>
        <q-card-section class="q-pt-none">
          When this is on, recording or editing a ticket sale here sends two
          emails right away: one to the box office letting you know a sale
          came in, and one to the ticket buyer confirming their purchase.
          <br /><br />
          Turn it off if you're catching up on data entry or fixing a mistake
          and don't want those emails going out again.
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Got it" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import {
  matAdd,
  matDelete,
  matEdit,
  matEvent,
  matInfo,
  matSearch,
} from "@quasar/extras/material-icons";
import {
  mdiCashMultiple,
  mdiCheckBold,
  mdiCircle,
  mdiCog,
  mdiCurrencyUsd,
} from "@quasar/extras/mdi-v7";
import { format, parseISO } from "date-fns";
import { sortBy, uniqBy } from "lodash-es";
import { Dialog, Notify } from "quasar";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import { computed, ref, watch } from "vue";
import AdminTicketSalesPrint from "./AdminTicketSalesPrint.vue";

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "ticket-sales") === "read-only",
);

const sendMailInfoDialog = ref(false);

const currentShow = store.home.currentShow;

const show = ref(
  store.admin.selectedShow ??
    (currentShow ? { label: currentShow.name, value: currentShow.id } : null),
);

watch(show, (val) => {
  store.admin.selectedShow = val;
});

const search = ref("");
const performanceFilter = ref(null);

watch(show, () => {
  performanceFilter.value = null;
});

const pagination = ref({ page: 1, rowsPerPage: 12, sortBy: "name", descending: false });
const pagesNumber = computed(() =>
  Math.ceil(filteredRecs.value.length / pagination.value.rowsPerPage),
);

const columns = [
  {
    name: "no_show",
    label: "No Show",
    field: "no_show",
    align: "center",
  },
  {
    name: "confirmed",
    label: "Confirmed",
    field: "confirmed",
    align: "center",
  },
  {
    name: "name",
    label: "Name",
    field: (row) => `${row.patron.first_name} ${row.patron.last_name}`,
    align: "left",
    sortable: true,
  },
  {
    name: "info",
    label: "",
    field: "",
    align: "center",
    style: "width: 36px; padding: 0;",
  },
  {
    name: "quantity",
    label: "Qty",
    field: (row) => row.quantity || "?",
    align: "center",
  },
  {
    name: "sold_at",
    label: "Date Sold",
    field: (row) => format(parseISO(row.sold_at), "PP"),
    align: "left",
  },
  {
    name: "performance",
    label: "Performance",
    field: (row) =>
      `${row.performance.formatted_date} ${row.performance.formatted_time}`,
    align: "left",
  },
  {
    name: "payment_method",
    label: "",
    field: "",
    align: "center",
    style: "width: 16px; padding: 0;",
  },
  {
    name: "actions",
    label: "",
    field: "",
    align: "center",
    style: "width: 70px;",
  },
];

const recs = computed(() => {
  if (!show.value) return [];
  return store.admin.ticket_sales.filter(
    (rec) => rec.performance.show.id == show.value.value,
  );
});

const filteredRecs = computed(() => {
  let result = recs.value;

  if (performanceFilter.value) {
    result = result.filter(
      (rec) => rec.performance.id === performanceFilter.value,
    );
  }

  if (search.value) {
    const q = search.value.toLowerCase();
    result = result.filter((rec) =>
      `${rec.patron.first_name} ${rec.patron.last_name}`
        .toLowerCase()
        .includes(q),
    );
  }

  return result;
});

// Distinct performances for the selected show, for the date filter — drawn
// from recs (not store.admin.shows) so it only offers dates that actually
// have ticket sales to filter down to.
const performanceOptions = computed(() => {
  const uniq = uniqBy(recs.value, (rec) => rec.performance.id).map((rec) => ({
    label: `${rec.performance.formatted_date} ${rec.performance.formatted_time}`,
    value: rec.performance.id,
    date: rec.performance.date,
  }));
  return sortBy(uniq, "date");
});

const shows = computed(() => {
  const fromSales = uniqBy(
    store.admin.ticket_sales,
    (rec) => rec.performance.show.id,
  ).map((rec) => ({
    label: rec.performance.show.name,
    value: rec.performance.show.id,
  }));

  const current = store.home.currentShow;
  if (current && !fromSales.some((s) => s.value === current.id)) {
    fromSales.unshift({ label: current.name, value: current.id });
  }

  return fromSales;
});

const ticketPrice = computed(
  () => recs.value[0]?.performance?.show?.ticket_price ?? 0,
);

// Comp-ticket rows are merged in from a different table and have no
// `confirmed` field at all (always treated as real/confirmed); regular
// ticket_sales rows default to unconfirmed, so only exclude those where
// it's explicitly false.
const confirmedRecs = computed(() =>
  recs.value.filter((rec) => rec.confirmed !== false),
);

const revenueByMethod = computed(() => {
  const price = ticketPrice.value;
  const groups = {};
  for (const rec of confirmedRecs.value) {
    const { label, color, revenue_multiplier } = rec.payment_method;
    if (!groups[label])
      groups[label] = {
        label,
        color,
        multiplier: revenue_multiplier ?? 1,
        count: 0,
        revenue: 0,
      };
    const qty = rec.quantity || 1;
    groups[label].count += qty;
    groups[label].revenue += qty * price * (revenue_multiplier ?? 1);
  }
  return Object.values(groups);
});

const totalRevenue = computed(() =>
  revenueByMethod.value.reduce((sum, m) => sum + m.revenue, 0),
);

// Confirmed-only, matches the Projected Revenue breakdown above.
const confirmedTicketCount = computed(() =>
  revenueByMethod.value.reduce((sum, m) => sum + m.count, 0),
);

// All reservations regardless of confirmation — a pending sale still
// occupies a seat, so sold-out capacity shouldn't exclude it.
const totalTickets = computed(() =>
  recs.value.reduce((sum, rec) => sum + (rec.quantity || 1), 0),
);

const performanceCount = computed(() => {
  if (!show.value) return 0;
  const s = store.admin.shows?.find((s) => s.id === show.value.value);
  return s?.performances?.length ?? 0;
});

const soldOutCapacity = computed(
  () => (store.config?.sold_out_target ?? 0) * performanceCount.value,
);

const soldOutPct = computed(() => {
  if (!soldOutCapacity.value) return 0;
  return ((totalTickets.value / soldOutCapacity.value) * 100).toFixed(1);
});

// Confirmed sales grouped by performance date — mirrors revenueByMethod's
// use of confirmedRecs, and is independent of performanceFilter so the
// summary always shows every date regardless of which one is filtered to.
const ticketsByDate = computed(() => {
  const groups = {};
  for (const rec of confirmedRecs.value) {
    const perf = rec.performance;
    if (!groups[perf.id]) {
      groups[perf.id] = {
        id: perf.id,
        date: perf.date,
        label: `${perf.formatted_date} ${perf.formatted_time}`,
        count: 0,
      };
    }
    groups[perf.id].count += rec.quantity || 1;
  }
  return sortBy(Object.values(groups), "date");
});

const paymentMethodColors = computed(() => {
  const methods = uniqBy(recs.value, (rec) => rec.payment_method.id).map(
    (rec) => rec.payment_method,
  );
  return Object.fromEntries(methods.map((m) => [m.label, m.color]));
});

const onDelete = async (row) => {
  Dialog.create({
    title: "Delete Ticket Sale",
    message: `Are you sure you want to delete the ticket sold to ${row.patron.first_name} ${row.patron.last_name} for the ${row.performance.formatted_date} performance at ${row.performance.formatted_time}?`,
    ok: "Yes",
    cancel: "No",
  }).onOk(async () => {
    const response = await callApi({
      path: `/ticket-sales`,
      method: "delete",
      useAuth: true,
      payload: row,
    });
    store.admin.ticket_sales = response;
  });
};

const onNewTicket = async () => {
  store.router.push({
    name: "admin-ticket-sale-new",
    params: { show_id: show.value.value },
  });
};

const onEditSale = async (row) => {
  store.router.push({
    name: "admin-ticket-sale-edit",
    params: { id: row.id },
  });
};

const updateNoShow = async (row) => {
  try {
    await callApi({
      path: `/ticket-sales/no-show/${row.id}`,
      method: "put",
      payload: row,
      useAuth: true,
    });
  } catch (e) {
    console.error({ error: e });
  }
};
</script>
