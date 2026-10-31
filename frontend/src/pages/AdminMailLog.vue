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
        <div class="full-width flex items-center q-gutter-x-md">
          <div class="text-h6">Mail Log</div>
          <q-space />
          <q-input
            v-model="search"
            dense
            outlined
            clearable
            placeholder="Search by recipient, subject, or type..."
            style="min-width: 260px;"
            debounce="300"
          >
            <template #prepend>
              <q-icon :name="matSearch" />
            </template>
          </q-input>
        </div>
      </template>

      <template #body-cell-status="props">
        <q-td :props="props" class="text-center">
          <q-badge :color="props.row.sent_at ? 'positive' : 'negative'">
            {{ props.value }}
          </q-badge>
          <q-tooltip v-if="!props.row.sent_at" max-width="300px">
            The app tried to send this email but the mail service rejected
            it. Details are in the server's laravel.log for that day.
          </q-tooltip>
        </q-td>
      </template>

      <template #body-cell-recipients="props">
        <q-td :props="props" class="value-cell">
          {{ props.value }}
          <q-tooltip v-if="props.value.length > VALUE_TOOLTIP_AT" max-width="600px">
            <div style="white-space: pre-wrap;">{{ props.value }}</div>
          </q-tooltip>
        </q-td>
      </template>

      <template #body-cell-subject="props">
        <q-td :props="props" class="value-cell">
          {{ props.value }}
          <q-tooltip v-if="props.value.length > VALUE_TOOLTIP_AT" max-width="600px">
            <div style="white-space: pre-wrap;">{{ props.value }}</div>
          </q-tooltip>
        </q-td>
      </template>
    </q-table>
  </div>
</template>

<script setup>
import { matSearch } from "@quasar/extras/material-icons";
import { format, parseISO } from "date-fns";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const store = useStore();

const search = ref("");
const pagination = ref({
  page: 1,
  rowsPerPage: 20,
  sortBy: "created_at",
  descending: true,
});

// Values longer than this are cut off in the cell; hover shows the full text.
const VALUE_TOOLTIP_AT = 50;

const columns = [
  {
    name: "created_at",
    label: "Date",
    field: "created_at",
    format: (val) => format(parseISO(val), "PPp"),
    align: "left",
    sortable: true,
  },
  {
    name: "status",
    label: "Status",
    field: "status",
    align: "center",
    sortable: true,
  },
  {
    name: "recipients",
    label: "Recipients",
    field: "recipients",
    align: "left",
    sortable: true,
  },
  {
    name: "subject",
    label: "Subject",
    field: "subject",
    align: "left",
    sortable: true,
  },
  {
    name: "type",
    label: "Type",
    field: "type",
    align: "left",
    sortable: true,
  },
  {
    name: "mailersend_message_id",
    label: "MailerSend ID",
    field: "mailersend_message_id",
    format: (val) => val ?? "—",
    align: "left",
  },
];

// "App\Mail\TicketSaleMailer" -> "Ticket Sale"
const typeFor = (mailable) => {
  if (!mailable) return "—";
  return mailable
    .split("\\")
    .pop()
    .replace(/Mailer$|Mail$/, "")
    .replace(/([a-z])([A-Z])/g, "$1 $2");
};

const rows = computed(() =>
  (store.admin.mail_logs ?? []).map((log) => ({
    ...log,
    status: log.sent_at ? "Sent" : "Failed",
    recipients: [
      log.to,
      log.cc ? `cc: ${log.cc}` : null,
      log.bcc ? `bcc: ${log.bcc}` : null,
    ]
      .filter(Boolean)
      .join("; "),
    subject: log.subject ?? "",
    type: typeFor(log.mailable),
  })),
);

const filteredRows = computed(() => {
  const q = search.value?.toLowerCase().trim();
  if (!q) return rows.value;
  return rows.value.filter((row) =>
    [row.recipients, row.subject, row.type, row.status].some((f) =>
      f?.toLowerCase().includes(q),
    ),
  );
});
</script>

<style scoped>
.value-cell {
  max-width: 320px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
