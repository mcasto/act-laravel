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
      :pagination="{ rowsPerPage: 12 }"
      :rows-per-page-options="[12]"
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
              ></q-toggle>
              <q-btn
                icon="add"
                size="sm"
                color="primary"
                round
                @click="onNewTicket"
              />
              <AdminTicketSalesPrint
                :recs="recs"
                :ticket-price="ticketPrice"
                :show-name="show.label"
              />
              <q-btn icon="mdi-cash-multiple" color="positive" flat>
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
                        {{ totalTickets }} tickets
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
                <q-icon name="search" />
              </template>
            </q-input>
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
        </div>
      </template>

      <template #header-cell-payment_method="props">
        <q-th :props="props">
          <q-icon name="mdi-currency-usd" />
        </q-th>
      </template>

      <template #header-cell-actions="props">
        <q-th :props="props">
          <q-icon name="mdi-cog" />
        </q-th>
      </template>

      <template #body-cell-info="props">
        <q-td :props="props">
          <q-btn icon="info" flat round size="sm">
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
        <q-td :props="props">
          <div
            :style="`width: 10px; height: 10px; border-radius: 50%; background: ${
              paymentMethodColors[props.row.payment_method.label]
            };`"
          ></div>
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props">
          <q-btn
            icon="edit"
            flat
            round
            color="primary"
            size="sm"
            @click="onEditSale(props.row)"
          />
          <q-btn
            icon="delete"
            flat
            round
            color="negative"
            size="sm"
            @click="onDelete(props.row)"
          />
        </q-td>
      </template>
    </q-table>
  </div>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { uniqBy } from "lodash-es";
import { Dialog } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";
import AdminTicketSalesPrint from "./AdminTicketSalesPrint.vue";

const store = useStore();

const currentShow = store.home.currentShow;

const show = ref(
  currentShow ? { label: currentShow.name, value: currentShow.id } : null,
);

const search = ref("");

const columns = [
  {
    name: "name",
    label: "Name",
    field: (row) => `${row.patron.first_name} ${row.patron.last_name}`,
    align: "left",
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
    name: "show_name",
    label: "Show",
    field: (row) => row.performance.show.name,
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
  if (!search.value) return recs.value;
  const q = search.value.toLowerCase();
  return recs.value.filter((rec) =>
    `${rec.patron.first_name} ${rec.patron.last_name}`
      .toLowerCase()
      .includes(q),
  );
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

const revenueByMethod = computed(() => {
  const price = ticketPrice.value;
  const groups = {};
  for (const rec of recs.value) {
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

const totalTickets = computed(() =>
  revenueByMethod.value.reduce((sum, m) => sum + m.count, 0),
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
</script>
