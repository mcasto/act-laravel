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
              >
                <q-tooltip>
                  Default for the "Send Emails" toggle on the New Ticket Sale
                  form — doesn't send anything by itself. Turn off if you're
                  catching up on bulk data entry.
                </q-tooltip>
              </q-toggle>
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
              <q-btn
                v-if="currentShow"
                :icon="mdiCashClock"
                color="warning"
                flat
                @click="unconfirmedDialog = true"
              >
                <q-badge v-if="unconfirmedRecs.length" color="negative" floating>
                  {{ unconfirmedRecs.length }}
                </q-badge>
                <q-tooltip>Unconfirmed PayPal / Bank Transfer Payments</q-tooltip>
              </q-btn>
              <q-btn :icon="matEvent" color="info" flat>
                <q-tooltip>Tickets Sold By Date</q-tooltip>
                <q-menu>
                  <q-list dense style="min-width: 380px;">
                    <q-item-label header class="text-weight-bold"
                      >Tickets Sold By Date</q-item-label
                    >
                    <q-item v-if="ticketsByDate.length">
                      <q-item-section />
                      <q-item-section
                        side
                        class="text-caption text-grey-7 tsbd-col"
                        >Confirmed</q-item-section
                      >
                      <q-item-section
                        side
                        class="text-caption text-grey-7 tsbd-col"
                        >Reserved</q-item-section
                      >
                      <q-item-section
                        side
                        class="text-caption text-grey-7 tsbd-rev"
                        >Projected</q-item-section
                      >
                    </q-item>
                    <q-item v-for="row in ticketsByDate" :key="row.id">
                      <q-item-section>{{ row.label }}</q-item-section>
                      <q-item-section side class="tsbd-col">
                        {{ row.confirmed }}
                      </q-item-section>
                      <q-item-section side class="text-weight-medium tsbd-col">
                        {{ row.total }}
                      </q-item-section>
                      <q-item-section side class="text-positive tsbd-rev">
                        ${{ row.revenue.toFixed(2) }}
                      </q-item-section>
                    </q-item>
                    <div
                      v-if="ticketsByDate.length === 0"
                      class="text-caption text-grey-7 q-pa-sm"
                    >
                      No reservations yet.
                    </div>
                    <q-separator v-if="ticketsByDate.length" />
                    <q-item v-if="ticketsByDate.length">
                      <q-item-section class="text-weight-bold"
                        >Total</q-item-section
                      >
                      <q-item-section side class="text-weight-bold tsbd-col">
                        {{ confirmedTicketCount }}
                      </q-item-section>
                      <q-item-section side class="text-weight-bold tsbd-col">
                        {{ totalTickets }}
                      </q-item-section>
                      <q-item-section
                        side
                        class="text-positive text-weight-bold tsbd-rev"
                      >
                        ${{ projectedRevenueAll.toFixed(2) }}
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

      <template #body-cell-special_seating="props">
        <q-td :props="props" class="text-center">
          <q-icon v-if="props.value" :name="matComment" color="primary">
            <q-tooltip>{{ props.value }}</q-tooltip>
          </q-icon>
        </q-td>
      </template>

      <template #body-cell-info="props">
        <q-td :props="props">
          <q-btn :icon="matInfo" flat round size="sm">
            <q-menu>
              <q-list dense separator>
                <q-item>
                  <q-item-section side>Purchaser:</q-item-section>
                  <q-item-section>
                    <q-item-label
                      >{{ props.row.patron.first_name }}
                      {{ props.row.patron.last_name }}</q-item-label
                    >
                  </q-item-section>
                </q-item>
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

      <template #body-cell-tickets="props">
        <q-td :props="props" class="text-center" :class="{ 'bg-green-2': allTicketsRedeemed(props.row) }">
          <span
            v-if="props.row.tickets?.length"
            class="text-primary cursor-pointer text-weight-medium"
            @click="openTicketsDialog(props.row)"
          >
            {{ ticketNumbersDisplay(props.row) }}
          </span>
          <span v-else>{{ ticketNumbersDisplay(props.row) }}</span>
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
          This sets the default for the "Send Emails" toggle on the New
          Ticket Sale form — it doesn't send anything by itself. When a new
          sale is saved with that toggle on, two emails go out right away:
          one to the box office letting you know a sale came in, and one to
          the ticket buyer confirming their purchase.
          <br /><br />
          Turn this off if you're catching up on bulk data entry and don't
          want those emails going out for sales you're just recording after
          the fact — it'll stay off for every New Ticket Sale form you open
          until you turn it back on.
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Got it" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="unconfirmedDialog">
      <q-card style="min-width: 360px; max-width: 900px; width: 100%;">
        <q-card-section class="flex items-center">
          <div>
            <div class="text-h6">Unconfirmed Payments</div>
            <div class="text-caption text-grey-7">
              {{ currentShow?.name }} — PayPal and Bank Transfer reservations
              still awaiting payment confirmation
            </div>
          </div>
          <q-space />
          <q-btn
            v-if="unconfirmedRecs.length"
            :icon="matEmail"
            label="Send Reminder"
            flat
            dense
            color="primary"
            :disable="isReadOnly || reminderSelected.length === 0"
            @click="openReminderDialog"
          >
            <q-tooltip>
              Emails each checked patron individually
            </q-tooltip>
          </q-btn>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div v-if="unconfirmedRecs.length === 0" class="text-grey-7">
            Nothing outstanding — every PayPal and Bank Transfer payment is
            confirmed.
          </div>
          <q-markup-table v-else dense flat bordered wrap-cells>
            <thead>
              <tr>
                <th class="text-center">
                  <q-checkbox
                    :model-value="reminderAllState"
                    dense
                    @update:model-value="toggleAllReminders"
                  />
                </th>
                <th class="text-left">Performance</th>
                <th class="text-left">Purchaser</th>
                <th class="text-left">Contact</th>
                <th class="text-center">Qty</th>
                <th class="text-center">Tickets</th>
                <th class="text-left">Method</th>
                <th class="text-left">Date Sold</th>
                <th class="text-left">Transfer Date</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in unconfirmedRecs" :key="row.id">
                <td class="text-center">
                  <q-checkbox v-model="reminderSelected" :val="row.id" dense />
                </td>
                <td>
                  {{ row.performance.formatted_date }}
                  {{ row.performance.formatted_time }}
                </td>
                <td>{{ row.patron.first_name }} {{ row.patron.last_name }}</td>
                <td>
                  <a
                    :href="`mailto:${row.patron.first_name} ${row.patron.last_name} <${row.patron.email}>`"
                    >{{ row.patron.email }}</a
                  >
                  <div v-if="row.patron.phone" class="text-caption">
                    {{ row.patron.phone }}
                  </div>
                </td>
                <td class="text-center">{{ row.quantity || "?" }}</td>
                <td class="text-center">{{ ticketNumbersDisplay(row) }}</td>
                <td>{{ row.payment_method.label }}</td>
                <td>{{ format(parseISO(row.sold_at), "PP") }}</td>
                <td>
                  {{
                    row.transfer_date
                      ? format(parseISO(row.transfer_date), "PP")
                      : ""
                  }}
                </td>
                <td>
                  <q-btn
                    :icon="matEdit"
                    flat
                    round
                    color="primary"
                    size="sm"
                    :disable="isReadOnly"
                    @click="onEditSale(row)"
                  >
                    <q-tooltip>Edit / mark confirmed</q-tooltip>
                  </q-btn>
                </td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Close" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="reminderDialog" persistent>
      <q-card style="min-width: 360px; max-width: 600px; width: 100%;">
        <q-card-section>
          <div class="text-h6">Send Payment Reminder</div>
          <div class="text-caption text-grey-7">
            Goes to {{ reminderPatronCount }} patron{{
              reminderPatronCount === 1 ? "" : "s"
            }}, each as their own email. Each one starts with "Hello
            <i>name</i>," and ends with a list of their pending reservations,
            so just write the part in between. Each patron's ticket numbers
            are added to the end of the subject as "(Reference: …)".
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none q-gutter-y-md">
          <q-input
            v-model="reminderSubject"
            label="Subject"
            outlined
            dense
            maxlength="255"
          />
          <q-input
            v-model="reminderBody"
            label="Message"
            type="textarea"
            outlined
            autogrow
            maxlength="5000"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancel"
            :disable="reminderSending"
            v-close-popup
          />
          <q-btn
            flat
            label="Send"
            color="primary"
            :loading="reminderSending"
            :disable="!reminderSubject.trim() || !reminderBody.trim()"
            @click="sendReminders"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="ticketsDialog">
      <q-card style="min-width: 350px;">
        <q-card-section>
          <div class="text-h6">Tickets</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-list separator>
            <q-item v-for="ticket in ticketsDialogRows" :key="ticket.id">
              <q-item-section side>
                <div class="text-weight-medium">#{{ ticket.formatted_number }}</div>
              </q-item-section>
              <q-item-section>{{ ticket.name }}</q-item-section>
              <q-item-section side>
                <q-checkbox
                  v-model="ticket.redeemed"
                  label="Redeemed"
                  :disable="isReadOnly"
                />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            :disable="isReadOnly"
            @click="saveTicketRedemptions"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import {
  matAdd,
  matComment,
  matDelete,
  matEdit,
  matEmail,
  matEvent,
  matInfo,
  matSearch,
} from "@quasar/extras/material-icons";
import {
  mdiCashClock,
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

// search and performanceFilter live in the store (sessionStorage-persisted)
// so they survive the trip to the add/edit ticket form and back, and stay
// set until cleared manually (performanceFilter also resets on show change).
const search = computed({
  get: () => store.ticketSalesSearch,
  set: (val) => {
    store.ticketSalesSearch = val;
  },
});

const performanceFilter = computed({
  get: () => store.ticketSalesPerformanceFilter,
  set: (val) => {
    store.ticketSalesPerformanceFilter = val;
  },
});

watch(show, () => {
  performanceFilter.value = null;
});

const pagination = ref({ page: 1, rowsPerPage: 12, sortBy: "last", descending: false });
const pagesNumber = computed(() =>
  Math.ceil(filteredRecs.value.length / pagination.value.rowsPerPage),
);

// A ticket_sales row (including a redeemed comp, which gets a real row of
// its own — see CompTixController::redeemComp()) carries its individual
// tickets in `tickets`. Legacy rows predating per-ticket tracking have
// neither `tickets` nor `number`.
const padNumber = (n) => String(n).padStart(3, "0");
const ticketNumbersDisplay = (row) => {
  if (row.tickets?.length) {
    const nums = row.tickets.map((t) => t.number).sort((a, b) => a - b);
    return nums.length > 1
      ? `${padNumber(nums[0])}–${padNumber(nums[nums.length - 1])}`
      : padNumber(nums[0]);
  }
  if (row.number != null) return padNumber(row.number);
  return "—";
};

// Same fallback as AdminTicketSalesPrint.vue: comps and not-yet-backfilled
// legacy rows have no door_first/door_last, so use the purchaser's name.
const doorLast = (row) => row.door_last || row.patron.last_name;
const doorFirst = (row) => row.door_first || row.patron.first_name;
const doorName = (row) => `${doorFirst(row)} ${doorLast(row)}`;

const compareNames = (a, b) =>
  a[0].localeCompare(b[0], undefined, { sensitivity: "base" }) ||
  a[1].localeCompare(b[1], undefined, { sensitivity: "base" });

const allTicketsRedeemed = (row) =>
  !!row.tickets?.length && row.tickets.every((t) => !!t.redeemed_at);

// Order requested: Performance Date : Name : Qty : Ticket #s : Confirmed :
// Date Sold : No Show — Name since split into Last/First. "info"
// (purchaser/email/phone popup) stays glued to Last/First and
// "payment_method"/"actions" (both unlabeled icon-only columns) stay
// trailing at the end, same as before — none of the three were named in
// that order, so they're left in their least-disruptive spot.
const columns = [
  {
    name: "performance",
    label: "Performance",
    field: (row) =>
      `${row.performance.formatted_date} ${row.performance.formatted_time}`,
    align: "left",
  },
  // Door Name (not the purchaser) split into Last/First, to match the box
  // office's spreadsheet and the printed door sheet
  // (AdminTicketSalesPrint.vue) — the purchaser is in the info popup.
  // Each sorts by its own name, then the other as a tiebreaker.
  {
    name: "last",
    label: "Last",
    field: (row) => doorLast(row),
    sort: (_a, _b, rowA, rowB) =>
      compareNames(
        [doorLast(rowA), doorFirst(rowA)],
        [doorLast(rowB), doorFirst(rowB)],
      ),
    align: "left",
    sortable: true,
  },
  {
    name: "first",
    label: "First",
    field: (row) => doorFirst(row),
    sort: (_a, _b, rowA, rowB) =>
      compareNames(
        [doorFirst(rowA), doorLast(rowA)],
        [doorFirst(rowB), doorLast(rowB)],
      ),
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
    name: "tickets",
    label: "Tickets",
    field: ticketNumbersDisplay,
    align: "center",
  },
  {
    name: "confirmed",
    label: "Confirmed",
    field: "confirmed",
    align: "center",
  },
  {
    name: "sold_at",
    label: "Date Sold",
    field: (row) => format(parseISO(row.sold_at), "PP"),
    align: "left",
  },
  {
    name: "no_show",
    label: "No Show",
    field: "no_show",
    align: "center",
  },
  {
    name: "front_row",
    label: "Front Row",
    field: (row) => (row.front_row > 0 ? row.front_row : ""),
    align: "center",
  },
  {
    name: "special_seating",
    label: "Special Seating",
    field: "special_seating",
    align: "center",
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
    // Matches either the door name shown in the table or the purchaser
    // (in the info popup), so a search still finds a sale whose door name
    // was overridden to someone else.
    result = result.filter(
      (rec) =>
        doorName(rec).toLowerCase().includes(q) ||
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

// Comp tickets are confirmed=true from the moment they're redeemed (see
// CompTixController::redeemComp()) and stay that way; regular ticket_sales
// rows default to unconfirmed, so only exclude those where it's
// explicitly false.
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

// All reservations grouped by performance date, with the confirmed subset
// broken out. Revenue is projected across every reservation (as if pending
// payments all come in), using the same price × multiplier as
// revenueByMethod. Independent of performanceFilter so the summary always
// shows every date regardless of which one is filtered to.
const ticketsByDate = computed(() => {
  const price = ticketPrice.value;
  const groups = {};
  for (const rec of recs.value) {
    const perf = rec.performance;
    if (!groups[perf.id]) {
      groups[perf.id] = {
        id: perf.id,
        date: perf.date,
        label: `${perf.formatted_date} ${perf.formatted_time}`,
        confirmed: 0,
        total: 0,
        revenue: 0,
      };
    }
    const qty = rec.quantity || 1;
    groups[perf.id].total += qty;
    if (rec.confirmed !== false) groups[perf.id].confirmed += qty;
    groups[perf.id].revenue +=
      qty * price * (rec.payment_method.revenue_multiplier ?? 1);
  }
  return sortBy(Object.values(groups), "date");
});

// PayPal and Bank Transfer are the only methods that sit pending until the
// box office sees the money arrive — this is the box office's "who to
// chase" list. Always the current show (tickets are only ever on sale for
// one show at a time, and past shows' stragglers don't matter), regardless
// of which show is picked in the selector or the search/performance filters.
const PENDING_PAYMENT_METHODS = ["paypal", "transfer"];
const unconfirmedDialog = ref(false);

const unconfirmedRecs = computed(() => {
  if (!currentShow) return [];
  return sortBy(
    store.admin.ticket_sales.filter(
      (rec) =>
        rec.performance.show.id == currentShow.id &&
        !rec.confirmed &&
        PENDING_PAYMENT_METHODS.includes(rec.payment_method?.value),
    ),
    [
      (rec) => rec.performance.date,
      (rec) => rec.performance.start_time,
      (rec) => rec.patron.last_name?.toLowerCase(),
    ],
  );
});

// Checked rows to remind — everyone by default each time the list opens.
const reminderSelected = ref([]);
watch(unconfirmedDialog, (open) => {
  if (open) reminderSelected.value = unconfirmedRecs.value.map((r) => r.id);
});

const reminderAllState = computed(() => {
  if (reminderSelected.value.length === 0) return false;
  if (reminderSelected.value.length === unconfirmedRecs.value.length) return true;
  return null;
});

const toggleAllReminders = (val) => {
  reminderSelected.value = val ? unconfirmedRecs.value.map((r) => r.id) : [];
};

// The server sends one email per patron, so a patron with two pending
// sales counts once.
const reminderPatronCount = computed(
  () =>
    new Set(
      unconfirmedRecs.value
        .filter((r) => reminderSelected.value.includes(r.id))
        .map((r) => r.patron.id),
    ).size,
);

const reminderDialog = ref(false);
const reminderSending = ref(false);
const reminderSubject = ref("");
const reminderBody = ref(
  "We have your reservation, but we haven't received your payment yet. " +
    "If you've already sent it, thank you! Please reply and let us know " +
    "when and how it was sent so we can match it up.",
);

const openReminderDialog = () => {
  if (!reminderSubject.value) {
    reminderSubject.value = `Payment Reminder - ${currentShow?.name ?? ""}`;
  }
  reminderDialog.value = true;
};

const sendReminders = async () => {
  reminderSending.value = true;
  try {
    const response = await callApi({
      path: "/ticket-sales/payment-reminders",
      method: "post",
      useAuth: true,
      payload: {
        ticket_sale_ids: reminderSelected.value,
        subject: reminderSubject.value,
        body: reminderBody.value,
      },
    });

    if (!response || response.status !== "success") {
      Notify.create({
        type: "negative",
        message: response?.message || "Something went wrong.",
      });
      return;
    }

    if (response.failed?.length) {
      Notify.create({
        type: "warning",
        timeout: 0,
        actions: [{ label: "Dismiss", color: "white" }],
        message: `Sent ${response.sent}, but these failed: ${response.failed.join(", ")}`,
      });
    } else {
      Notify.create({
        type: "positive",
        message: `Sent ${response.sent} reminder${response.sent === 1 ? "" : "s"}.`,
      });
    }
    reminderDialog.value = false;
  } finally {
    reminderSending.value = false;
  }
};

const projectedRevenueAll = computed(() =>
  ticketsByDate.value.reduce((sum, row) => sum + row.revenue, 0),
);

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

const ticketsDialog = ref(false);
const ticketsDialogRows = ref([]);
const activeTicketSaleId = ref(null);

const openTicketsDialog = (row) => {
  activeTicketSaleId.value = row.id;
  ticketsDialogRows.value = [...row.tickets]
    .sort((a, b) => a.number - b.number)
    .map((t) => ({ ...t, redeemed: !!t.redeemed_at }));
  ticketsDialog.value = true;
};

const saveTicketRedemptions = async () => {
  const response = await callApi({
    path: `/ticket-sales/${activeTicketSaleId.value}/tickets`,
    method: "put",
    useAuth: true,
    payload: {
      tickets: ticketsDialogRows.value.map((t) => ({ id: t.id, redeemed: t.redeemed })),
    },
  });

  if (!response || response.status !== "success") {
    Notify.create({
      type: "negative",
      message: response?.message || "Something went wrong.",
    });
    return;
  }

  const sale = store.admin.ticket_sales.find((s) => s.id === activeTicketSaleId.value);
  if (sale) sale.tickets = response.tickets;

  Notify.create({ type: "positive", message: "Ticket redemptions saved." });
  ticketsDialog.value = false;
};
</script>

<style scoped>
.tsbd-col {
  min-width: 70px;
  align-items: flex-end;
}
.tsbd-rev {
  min-width: 90px;
  align-items: flex-end;
}
</style>
