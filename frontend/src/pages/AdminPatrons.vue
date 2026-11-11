<template>
  <div>
    <q-table
      :rows="filteredRows"
      :columns="columns"
      row-key="id"
      dense
      v-model:pagination="pagination"
    >
      <template #top>
        <div class="full-width">
          <div class="flex no-wrap items-center q-gutter-x-sm q-mb-sm">
            <div class="text-h6">Patron Management</div>
            <q-space />
            <q-input
              v-model="search"
              dense
              outlined
              placeholder="Search by name or email..."
              clearable
              style="min-width: 260px;"
              debounce="300"
            >
              <template #prepend>
                <q-icon :name="matSearch" />
              </template>
            </q-input>
            <q-btn
              color="primary"
              :icon="matAdd"
              label="Add Patron"
              :disable="isReadOnly"
              @click="openDialog()"
            />
          </div>
        </div>
      </template>

      <template #body-cell-email="props">
        <q-td :props="props">
          <q-btn
            v-if="otherEmails(props.row).length"
            :icon="matEmail"
            :label="`${otherEmails(props.row).length + 1} emails`"
            flat
            dense
            no-caps
            color="primary"
            @click="openEmailsDialog(props.row)"
          />
          <a v-else :href="`mailto:${props.value}`">{{ props.value }}</a>
        </q-td>
      </template>

      <template #body-cell-is_angel="props">
        <q-td :props="props">
          <template v-if="props.row.is_angel">
            <q-icon :name="matStar" color="amber" size="sm">
              <q-tooltip>{{ props.row.angel_level }}</q-tooltip>
            </q-icon>
          </template>
        </q-td>
      </template>

      <template #body-cell-flex_remaining="props">
        <q-td :props="props">
          <template v-if="props.value === null">
            <span class="text-grey">N/A</span>
          </template>
          <template v-else>
            <a href="#" @click.prevent="openFlexHistory(props.row.id)">{{ props.value }}</a>
          </template>
        </q-td>
      </template>

      <template #body-cell-history="props">
        <q-td :props="props">
          <q-btn
            flat
            round
            dense
            :icon="matReceiptLong"
            color="primary"
            @click="openPurchaseHistory(props.row.id)"
          >
            <q-tooltip>Ticket purchase history (all shows/seasons)</q-tooltip>
          </q-btn>
        </q-td>
      </template>

      <template #body-cell-front_row="props">
        <q-td :props="props">
          <q-input
            :model-value="props.value"
            type="number"
            dense
            outlined
            min="0"
            max="3"
            debounce="500"
            style="max-width: 90px;"
            :disable="isReadOnly"
            @update:model-value="(val) => updateFrontRow(props.row, val)"
          >
            <q-tooltip>
              Front-row seats needed (wheelchair, vision-impaired, caretakers, etc.)
            </q-tooltip>
          </q-input>
        </q-td>
      </template>

      <template #body-cell-comments="props">
        <q-td :props="props">
          <q-btn
            flat
            round
            dense
            :icon="matComment"
            :color="props.value ? 'primary' : 'grey-5'"
            @click="openComments(props.row)"
          >
            <q-tooltip v-if="props.value">{{ props.value }}</q-tooltip>
            <q-tooltip v-else>Add a note about this patron</q-tooltip>
          </q-btn>
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
            @click="deletePatron(props.row)"
          />
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialog" persistent>
      <q-card style="min-width: 400px;">
        <q-card-section>
          <div class="text-h6">{{ form.id ? "Edit" : "Add" }} Patron</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
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
            v-if="!form.id"
            v-model="form.email"
            type="email"
            label="Email"
            outlined
            dense
            class="q-mb-md"
          />
          <div v-else class="q-mb-md">
            <div class="text-caption text-grey-7 q-mb-xs">
              Email addresses — purchases under any of them belong to this
              patron; email goes to the main one.
            </div>
            <q-list dense bordered separator class="rounded-borders">
              <q-item v-for="e in form.emails" :key="e.id">
                <q-item-section>{{ e.email }}</q-item-section>
                <q-item-section side>
                  <q-badge v-if="isMain(e)" color="primary">Main</q-badge>
                  <div v-else class="row no-wrap items-center">
                    <q-btn
                      flat
                      dense
                      no-caps
                      size="sm"
                      color="primary"
                      label="Make main"
                      :disable="isReadOnly || emailBusy"
                      @click="makeMain(e)"
                    />
                    <q-btn
                      :icon="matDelete"
                      flat
                      round
                      dense
                      size="sm"
                      color="negative"
                      :disable="isReadOnly || emailBusy"
                      @click="removeEmail(e)"
                    />
                  </div>
                </q-item-section>
              </q-item>
              <q-item v-if="!form.emails.length">
                <q-item-section class="text-grey-7">No email addresses yet.</q-item-section>
              </q-item>
            </q-list>
            <div class="row no-wrap items-center q-gutter-x-sm q-mt-sm">
              <q-input
                v-model="newEmail"
                type="email"
                placeholder="Add another address"
                outlined
                dense
                class="col"
                :disable="isReadOnly"
                @keyup.enter="addEmail"
              />
              <q-btn
                :icon="matAdd"
                round
                dense
                color="primary"
                :loading="emailBusy"
                :disable="isReadOnly || !newEmail.trim()"
                @click="addEmail"
              />
            </div>
            <q-btn
              flat
              dense
              no-caps
              color="primary"
              :icon="matMergeType"
              label="Merge a duplicate patron into this one…"
              class="q-mt-sm"
              :disable="isReadOnly"
              @click="openMerge()"
            />
          </div>
          <q-input
            v-model="form.phone"
            label="Phone (optional)"
            outlined
            dense
            class="q-mb-md"
          />
          <q-checkbox v-model="form.founding_angel" label="Founding Angel" />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            :loading="saving"
            :disable="!canSave"
            @click="saveDialog"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="emailsDialog.visible">
      <q-card style="min-width: 320px; max-width: 95vw;">
        <q-card-section>
          <div class="text-h6">Email Addresses</div>
          <div class="text-caption text-grey-7">{{ emailsDialog.name }}</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-list dense separator>
            <q-item v-for="email in emailsDialog.emails" :key="email">
              <q-item-section>
                <a :href="`mailto:${email}`">{{ email }}</a>
              </q-item-section>
              <q-item-section side>
                <q-badge v-if="email === emailsDialog.main" color="primary">Main</q-badge>
              </q-item-section>
            </q-item>
          </q-list>
          <div class="text-caption text-grey-7 q-mt-sm">
            Email goes to the main address. Edit the patron to add, remove or
            change the main one.
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Close" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="merge.visible" persistent>
      <q-card style="width: 480px; max-width: 95vw;">
        <q-card-section>
          <div class="text-h6">Merge Patrons</div>
          <div class="text-caption text-grey-7">
            Into {{ form.first_name }} {{ form.last_name }} (#{{ form.id }})
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none q-gutter-y-md">
          <q-select
            v-model="merge.fromId"
            :options="mergeOptions"
            label="Duplicate patron to merge in"
            use-input
            input-debounce="0"
            emit-value
            map-options
            dense
            outlined
            :disable="merge.locked"
            @filter="filterMergeOptions"
            @update:model-value="loadMergePreview"
          />

          <div v-if="merge.preview" class="text-body2">
            <div>
              <span class="text-weight-medium">
                {{ merge.preview.first_name }} {{ merge.preview.last_name }}
              </span>
              (#{{ merge.preview.id }})
              <span v-if="merge.preview.email" class="text-grey-7">— {{ merge.preview.email }}</span>
            </div>
            <ul class="q-my-sm q-pl-md">
              <li v-for="(count, table) in merge.preview.counts" :key="table">
                {{ count }} {{ tableLabel(table, count) }}
              </li>
              <li v-if="!Object.keys(merge.preview.counts ?? {}).length" class="text-grey-7">
                No purchases or other records
              </li>
            </ul>
            <div class="text-caption text-grey-7">
              Everything above moves to {{ form.first_name }} {{ form.last_name }},
              its email addresses become extra addresses here, any missing phone
              or comments are copied over, and the duplicate record is removed.
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" :disable="merge.saving" v-close-popup />
          <q-btn
            flat
            label="Merge"
            color="primary"
            :loading="merge.saving"
            :disable="!merge.preview"
            @click="confirmMerge"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="flexHistoryDialog">
      <q-card style="min-width: 500px; max-width: 95vw;">
        <q-card-section>
          <div class="text-h6">{{ flexHistoryPatronName }}</div>
          <div class="text-caption text-grey-7">Flex Ticket History</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div v-if="flexHistoryLoading" class="text-center q-pa-md">
            <q-spinner color="primary" size="2em" />
          </div>

          <div v-else-if="flexHistorySeasons.length === 0" class="text-grey text-center q-pa-md">
            No flex package history found.
          </div>

          <div
            v-for="season in flexHistorySeasons"
            :key="season.season"
            class="q-mb-md"
          >
            <div class="text-subtitle1 text-bold">{{ season.season }}</div>
            <div class="text-caption q-mb-xs">
              Purchased: {{ season.tickets_purchased }} &nbsp;•&nbsp;
              Used: {{ season.tickets_used }} &nbsp;•&nbsp;
              Remaining: {{ season.tickets_remaining }}
            </div>

            <q-list v-if="season.usage.length" dense bordered separator>
              <q-item v-for="(u, i) in season.usage" :key="i">
                <q-item-section>{{ u.show }}</q-item-section>
                <q-item-section side>{{ u.date }}</q-item-section>
                <q-item-section side>
                  x{{ u.used_quantity ?? u.quantity }}
                  <span v-if="u.cancelled" class="text-caption text-grey-7">
                    ({{ u.cancelled }} cancelled{{
                      u.cancelled_returned ? `, ${u.cancelled_returned} returned` : ""
                    }})
                  </span>
                </q-item-section>
              </q-item>
            </q-list>
            <div v-else class="text-grey text-caption">No tickets used yet this season.</div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Close" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="purchaseHistory.visible">
      <q-card style="min-width: 600px; max-width: 95vw;">
        <q-card-section>
          <div class="text-h6">{{ purchaseHistory.patronName }}</div>
          <div class="text-caption text-grey-7">
            Ticket Purchase History
            <template v-if="!purchaseHistory.loading && purchaseHistory.totalSales">
              &nbsp;•&nbsp; {{ purchaseHistory.totalSales }}
              {{ purchaseHistory.totalSales === 1 ? "purchase" : "purchases" }},
              {{ purchaseHistory.totalTickets }}
              {{ purchaseHistory.totalTickets === 1 ? "ticket" : "tickets" }}
            </template>
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div v-if="purchaseHistory.loading" class="text-center q-pa-md">
            <q-spinner color="primary" size="2em" />
          </div>

          <div
            v-else-if="purchaseHistory.seasons.length === 0"
            class="text-grey text-center q-pa-md"
          >
            No ticket purchases found.
          </div>

          <div
            v-for="season in purchaseHistory.seasons"
            :key="season.season"
            class="q-mb-md"
          >
            <div class="text-subtitle1 text-bold">
              {{ season.season }}
              <span class="text-caption text-grey-7">
                ({{ season.tickets }} {{ season.tickets === 1 ? "ticket" : "tickets" }})
              </span>
            </div>

            <q-markup-table dense flat bordered separator="horizontal">
              <thead>
                <tr>
                  <th class="text-left">Show</th>
                  <th class="text-left">Performance</th>
                  <th class="text-center">Qty</th>
                  <th class="text-left">Payment</th>
                  <th class="text-left">Purchased</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="sale in season.sales" :key="sale.id">
                  <td class="text-left">
                    <a href="#" @click.prevent="openSaleDetails(sale)">
                      {{ sale.show ?? "—" }}
                    </a>
                  </td>
                  <td class="text-left">
                    {{ sale.formatted_date }} {{ sale.formatted_time }}
                  </td>
                  <td class="text-center">
                    {{ sale.quantity }}
                    <q-badge v-if="sale.cancelled" color="grey-7" class="q-ml-xs">
                      {{ sale.cancelled }} cancelled
                    </q-badge>
                    <q-badge v-if="sale.no_show" color="negative" class="q-ml-xs">
                      {{ sale.no_show }} no-show
                    </q-badge>
                  </td>
                  <td class="text-left">{{ sale.payment_method ?? "—" }}</td>
                  <td class="text-left">{{ formatSoldAt(sale.sold_at) }}</td>
                </tr>
              </tbody>
            </q-markup-table>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Close" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="saleDetails.visible">
      <q-card v-if="saleDetails.sale" style="min-width: 480px; max-width: 95vw;">
        <q-card-section>
          <div class="text-h6">{{ saleDetails.sale.show ?? "Unknown show" }}</div>
          <div class="text-caption text-grey-7">
            {{ saleDetails.sale.formatted_date }} {{ saleDetails.sale.formatted_time }}
            &nbsp;•&nbsp; Purchase #{{ saleDetails.sale.id }}
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-markup-table dense flat bordered separator="horizontal" class="q-mb-md">
            <tbody>
              <tr v-for="field in saleDetailFields" :key="field.label">
                <td class="text-left text-weight-medium" style="width: 40%;">
                  {{ field.label }}
                </td>
                <td class="text-left" style="white-space: pre-wrap;">{{ field.value }}</td>
              </tr>
            </tbody>
          </q-markup-table>

          <div class="text-subtitle2 q-mb-xs">Tickets</div>
          <q-markup-table
            v-if="saleDetails.sale.tickets.length"
            dense
            flat
            bordered
            separator="horizontal"
          >
            <thead>
              <tr>
                <th class="text-left">#</th>
                <th class="text-left">Name</th>
                <th class="text-left">Redeemed</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ticket in saleDetails.sale.tickets" :key="ticket.id">
                <td class="text-left">{{ ticket.number }}</td>
                <td class="text-left">{{ ticket.name }}</td>
                <td class="text-left">
                  <template v-if="ticket.redeemed_at">
                    {{ formatSoldAt(ticket.redeemed_at) }}
                  </template>
                  <span v-else class="text-grey">No</span>
                </td>
              </tr>
            </tbody>
          </q-markup-table>
          <div v-else class="text-grey text-caption">No tickets issued.</div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Close" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="commentsDialog.visible">
      <q-card style="min-width: 420px; max-width: 95vw;">
        <q-card-section>
          <div class="text-h6">{{ commentsDialog.patronName }}</div>
          <div class="text-caption text-grey-7">
            Admin-only notes — never shown to the patron
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
            v-model="commentsDialog.value"
            type="textarea"
            outlined
            autogrow
            :disable="isReadOnly"
            placeholder="e.g. Usually ~15 min late — hold their seat if prepaid."
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            :disable="isReadOnly"
            :loading="commentsDialog.saving"
            @click="saveComments"
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
  matMergeType,
  matReceiptLong,
  matSearch,
  matStar,
} from "@quasar/extras/material-icons";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { Notify } from "quasar";

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "patrons") === "read-only",
);

const search = ref("");
const pagination = ref({ page: 1, rowsPerPage: 15 });

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
    name: "is_angel",
    label: "Angel",
    field: "is_angel",
    align: "center",
    sortable: true,
  },
  {
    name: "flex_remaining",
    label: "Flex Remaining",
    field: "flex_remaining",
    align: "center",
    sortable: true,
  },
  {
    name: "history",
    label: "History",
    field: "id",
    align: "center",
  },
  {
    name: "front_row",
    label: "Front Row",
    field: "front_row",
    align: "center",
    sortable: true,
  },
  {
    name: "comments",
    label: "Notes",
    field: "comments",
    align: "center",
  },
  {
    name: "actions",
    label: "",
    field: "",
    align: "right",
  },
];

const filteredRows = computed(() => {
  const rows = store.admin.patrons ?? [];
  if (!search.value) return rows;

  const q = search.value.toLowerCase();
  return rows.filter(
    (row) =>
      `${row.first_name} ${row.last_name}`.toLowerCase().includes(q) ||
      row.email?.toLowerCase().includes(q) ||
      (row.emails ?? []).some((e) => e.email.includes(q)),
  );
});

const flexHistoryDialog = ref(false);
const flexHistoryLoading = ref(false);
const flexHistoryPatronName = ref("");
const flexHistorySeasons = ref([]);

const openFlexHistory = async (patronId) => {
  flexHistoryDialog.value = true;
  flexHistoryLoading.value = true;
  flexHistorySeasons.value = [];

  const response = await callApi({
    path: "/admin/patrons/flex-history",
    method: "get",
    payload: patronId,
    useAuth: true,
  });

  if (response) {
    flexHistoryPatronName.value = response.patron.name;
    flexHistorySeasons.value = response.seasons;
  }

  flexHistoryLoading.value = false;
};

const purchaseHistory = ref({
  visible: false,
  loading: false,
  patronName: "",
  totalSales: 0,
  totalTickets: 0,
  seasons: [],
});

const openPurchaseHistory = async (patronId) => {
  purchaseHistory.value = {
    visible: true,
    loading: true,
    patronName: "",
    totalSales: 0,
    totalTickets: 0,
    seasons: [],
  };

  const response = await callApi({
    path: "/admin/patrons/purchase-history",
    method: "get",
    payload: patronId,
    useAuth: true,
  });

  if (response) {
    purchaseHistory.value.patronName = response.patron.name;
    purchaseHistory.value.totalSales = response.total_sales;
    purchaseHistory.value.totalTickets = response.total_tickets;
    purchaseHistory.value.seasons = response.seasons;
  }

  purchaseHistory.value.loading = false;
};

// Handles "Y-m-d H:i:s" (Safari can't parse the space form), full ISO
// timestamps, and date-only "Y-m-d" (pinned to local noon — bare dates
// parse as UTC midnight and would show as the previous day in the US).
const toDate = (value) => {
  const str = String(value);
  return new Date(/^\d{4}-\d{2}-\d{2}$/.test(str) ? `${str}T12:00:00` : str.replace(" ", "T"));
};

const formatSoldAt = (value) =>
  value
    ? toDate(value).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
      })
    : "—";

const saleDetails = ref({ visible: false, sale: null });

const openSaleDetails = (sale) => {
  saleDetails.value = { visible: true, sale };
};

// Only rows with something to show, so empty optional fields don't pad
// the dialog out with dashes.
const saleDetailFields = computed(() => {
  const s = saleDetails.value.sale;
  if (!s) return [];

  const doorName = [s.door_first, s.door_last].filter(Boolean).join(" ");

  return [
    { label: "Quantity", value: s.quantity },
    { label: "Cancelled", value: s.cancelled || null },
    { label: "Returned to Patron", value: s.cancelled_returned || null },
    { label: "Payment Method", value: s.payment_method },
    { label: "Purchased", value: formatSoldAt(s.sold_at) },
    { label: "Payment Confirmed", value: s.confirmed ? "Yes" : "No" },
    { label: "Transfer Date", value: s.transfer_date ? formatSoldAt(s.transfer_date) : null },
    { label: "Transaction ID", value: s.transaction_id },
    { label: "Door List Name", value: doorName },
    { label: "Front-Row Seats", value: s.front_row || null },
    { label: "Special Seating", value: s.special_seating },
    { label: "No-Shows", value: s.no_show || null },
    { label: "Reason Changed", value: s.reason_changed },
    { label: "Comments", value: s.comments },
  ].filter((f) => f.value !== null && f.value !== undefined && f.value !== "");
});

const updateFrontRow = async (row, value) => {
  const previous = row.front_row;
  const front_row = value === null || value === "" ? 0 : Number(value);

  if (front_row === previous) return;

  row.front_row = front_row;

  try {
    const response = await callApi({
      path: `/admin/patrons/${row.id}`,
      method: "put",
      payload: { front_row },
      useAuth: true,
      showError: false,
    });

    if (!response || response.status !== "success") {
      throw new Error("unexpected response");
    }
  } catch (e) {
    row.front_row = previous;
    Notify.create({
      type: "negative",
      message: "Failed to update front-row seats needed (must be 0-3).",
    });
  }
};

const commentsDialog = ref({
  visible: false,
  row: null,
  patronName: "",
  value: "",
  saving: false,
});

const openComments = (row) => {
  commentsDialog.value = {
    visible: true,
    row,
    patronName: `${row.first_name} ${row.last_name}`,
    value: row.comments ?? "",
    saving: false,
  };
};

const saveComments = async () => {
  const { row, value } = commentsDialog.value;
  commentsDialog.value.saving = true;

  const response = await callApi({
    path: `/admin/patrons/${row.id}`,
    method: "put",
    payload: { comments: value || null },
    useAuth: true,
    showError: false,
  });

  commentsDialog.value.saving = false;

  if (!response || response.status !== "success") {
    Notify.create({
      type: "negative",
      message: "Failed to save note.",
    });
    return;
  }

  row.comments = response.comments;
  commentsDialog.value.visible = false;
};

const dialog = ref(false);
const saving = ref(false);
const form = ref({
  id: null,
  first_name: "",
  last_name: "",
  email: "",
  phone: "",
  founding_angel: false,
});

// The main address is chosen from the list when editing, so only a new
// patron needs one typed in.
const canSave = computed(
  () =>
    !!form.value.first_name &&
    !!form.value.last_name &&
    (!!form.value.id || !!form.value.email),
);

const openDialog = (row = null) => {
  form.value = row
    ? {
        id: row.id,
        first_name: row.first_name,
        last_name: row.last_name,
        email: row.email,
        emails: [...(row.emails ?? [])],
        phone: row.phone ?? "",
        founding_angel: !!row.founding_angel,
      }
    : {
        id: null,
        first_name: "",
        last_name: "",
        email: "",
        phone: "",
        founding_angel: false,
      };
  dialog.value = true;
};

const reload = async () => {
  store.admin.patrons = await callApi({
    path: "/admin/patrons",
    method: "get",
    useAuth: true,
  });
};

// Laravel's automatic validate() failure (e.g. a duplicate email on
// create) reaches here as a WretchError whose .message is the raw JSON
// response text, not a parsed object — this app has no global handler
// that reshapes it, so it has to be unpacked here or it'd otherwise only
// ever reach the browser console (see AdminTicketSaleForm.vue's
// special_seating fix for the same class of bug).
const parseApiErrorMessage = (error) => {
  try {
    const parsed = JSON.parse(error.message);
    if (parsed.errors) return Object.values(parsed.errors).flat().join(" ");
    return parsed.message;
  } catch {
    return error?.message;
  }
};

const saveDialog = async () => {
  saving.value = true;
  const isEdit = !!form.value.id;

  const payload = {
    first_name: form.value.first_name,
    last_name: form.value.last_name,
    phone: form.value.phone || null,
    founding_angel: form.value.founding_angel,
  };
  if (!isEdit) payload.email = form.value.email;

  try {
    const response = await callApi({
      path: isEdit ? `/admin/patrons/${form.value.id}` : "/admin/patrons",
      method: isEdit ? "put" : "post",
      payload,
      useAuth: true,
      showError: false,
    });

    if (!response || response.status !== "success") {
      throw new Error(response?.message || "Something went wrong.");
    }

    Notify.create({
      type: "positive",
      message: `Patron ${isEdit ? "updated" : "added"}.`,
    });
    dialog.value = false;
    await reload();
  } catch (e) {
    Notify.create({
      type: "negative",
      message: parseApiErrorMessage(e) || "Something went wrong.",
    });
  } finally {
    saving.value = false;
  }
};

const deletePatron = (row) => {
  Notify.create({
    type: "warning",
    position: "center",
    message: `Delete ${row.first_name} ${row.last_name}? This can't be undone.`,
    actions: [
      { label: "No" },
      {
        label: "Yes",
        handler: async () => {
          const response = await callApi({
            path: `/admin/patrons/${row.id}`,
            method: "delete",
            useAuth: true,
          });

          if (!response || response.status !== "success") return;

          await reload();
        },
      },
    ],
  });
};
// ---- Email addresses (patron_emails) and merging duplicates ----

const normalize = (email) => (email ?? "").trim().toLowerCase();

// A patron's addresses other than the main one, for the table's +N badge.
const otherEmails = (row) =>
  (row.emails ?? []).map((e) => e.email).filter((e) => e !== normalize(row.email));

const isMain = (e) => e.email === normalize(form.value.email);

// The table's "N emails" button — every address, main first.
const emailsDialog = ref({ visible: false, name: "", main: "", emails: [] });
const openEmailsDialog = (row) => {
  const main = normalize(row.email);
  emailsDialog.value = {
    visible: true,
    name: `${row.first_name} ${row.last_name}`,
    main,
    emails: [main, ...otherEmails(row)].filter(Boolean),
  };
};

const newEmail = ref("");
const emailBusy = ref(false);

const emailCall = async (opts) => {
  emailBusy.value = true;
  try {
    return await callApi({ useAuth: true, showError: false, ...opts }).catch((e) => {
      Notify.create({ type: "negative", message: parseApiErrorMessage(e) || "Something went wrong." });
      return null;
    });
  } finally {
    emailBusy.value = false;
  }
};

const afterEmailChange = async (response) => {
  if (response?.emails) form.value.emails = response.emails;
  if (response?.patron?.email) form.value.email = response.patron.email;
  await reload();
};

const addEmail = async () => {
  const email = newEmail.value.trim();
  if (!email) return;

  const response = await emailCall({
    path: `/admin/patrons/${form.value.id}/emails`,
    method: "post",
    payload: { email },
  });
  if (!response) return;

  if (response.status === "conflict") {
    // Belongs to another patron — offer to merge that record in.
    openMerge(response.other);
    return;
  }
  if (response.status !== "success") {
    Notify.create({ type: "negative", message: response.message || "Couldn't add that address." });
    return;
  }

  newEmail.value = "";
  await afterEmailChange(response);
};

const makeMain = async (e) => {
  const response = await emailCall({
    path: `/admin/patrons/${form.value.id}/emails/${e.id}/primary`,
    method: "put",
  });
  if (response?.status === "success") await afterEmailChange(response);
};

const removeEmail = async (e) => {
  const response = await emailCall({
    path: `/admin/patrons/${form.value.id}/emails/${e.id}`,
    method: "delete",
  });
  if (!response) return;
  if (response.status !== "success") {
    Notify.create({ type: "negative", message: response.message || "Couldn't remove that address." });
    return;
  }
  await afterEmailChange(response);
};

const merge = ref({ visible: false, fromId: null, preview: null, saving: false, locked: false });
const mergeFilter = ref("");

const mergeOptions = computed(() => {
  const q = mergeFilter.value.toLowerCase();
  return (store.admin.patrons ?? [])
    .filter((p) => p.id !== form.value.id)
    .filter(
      (p) =>
        !q ||
        `${p.first_name} ${p.last_name}`.toLowerCase().includes(q) ||
        (p.email ?? "").toLowerCase().includes(q),
    )
    .slice(0, 50)
    .map((p) => ({
      label: `${p.first_name} ${p.last_name} (#${p.id})${p.email ? ` — ${p.email}` : ""}`,
      value: p.id,
    }));
});

const filterMergeOptions = (val, update) => update(() => (mergeFilter.value = val));

// `other` = the patron already holding an address being added (from the
// add-email conflict) — preselected; otherwise the admin picks one.
const openMerge = (other = null) => {
  mergeFilter.value = "";
  merge.value = {
    visible: true,
    fromId: other?.id ?? null,
    preview: other,
    saving: false,
    locked: !!other,
  };
};

const loadMergePreview = async (id) => {
  merge.value.preview = null;
  if (!id) return;
  const response = await emailCall({ path: `/admin/patrons/${id}/merge-preview`, method: "get" });
  if (response && merge.value.fromId === id) merge.value.preview = response;
};

const TABLE_LABELS = {
  ticket_sales: ["ticket sale", "ticket sales"],
  angels: ["Angel record", "Angel records"],
  patron_flex_packages: ["Flex package", "Flex packages"],
  pending_flex_redemptions: ["pending Flex request", "pending Flex requests"],
  donations: ["donation", "donations"],
  course_contacts: ["class enrollment", "class enrollments"],
  fixr_webhook_responses: ["Fixr record", "Fixr records"],
  reservation_reminders: ["reminder record", "reminder records"],
  patron_payment_ids: ["payment record", "payment records"],
};
const tableLabel = (table, count) => {
  const [one, many] = TABLE_LABELS[table] ?? [table.replace(/_/g, " "), table.replace(/_/g, " ")];
  return count === 1 ? one : many;
};

const confirmMerge = async () => {
  merge.value.saving = true;
  try {
    const response = await emailCall({
      path: `/admin/patrons/${form.value.id}/merge`,
      method: "post",
      payload: { from_id: merge.value.fromId },
    });
    if (!response || response.status !== "success") {
      if (response) Notify.create({ type: "negative", message: response.message || "Couldn't merge." });
      return;
    }

    Notify.create({ type: "positive", message: "Patrons merged." });
    merge.value.visible = false;
    newEmail.value = "";
    await afterEmailChange(response);
  } finally {
    merge.value.saving = false;
  }
};
</script>
