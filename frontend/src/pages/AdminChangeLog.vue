<template>
  <div>
    <q-table
      :rows="filteredLogs"
      :columns="columns"
      row-key="id"
      dense
      v-model:pagination="pagination"
    >
      <template #top>
        <div class="full-width flex items-center q-gutter-x-md">
          <div class="text-h6">Changelog</div>
          <q-space />
          <q-input
            v-model="search"
            dense
            outlined
            clearable
            placeholder="Search by user, section, or description..."
            style="min-width: 260px;"
            debounce="300"
          >
            <template #prepend>
              <q-icon :name="matSearch" />
            </template>
          </q-input>
        </div>
      </template>

      <template #body-cell-action="props">
        <q-td :props="props" class="text-center">
          <q-badge :color="actionColors[props.row.action] ?? 'grey'">
            {{ props.row.action }}
          </q-badge>
        </q-td>
      </template>

      <template #body-cell-details="props">
        <q-td :props="props" class="text-center">
          <q-btn
            v-if="props.row.changes && Object.keys(props.row.changes).length"
            :icon="matInfo"
            flat
            round
            size="sm"
            @click="detailsDialog = { visible: true, row: props.row }"
          />
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="detailsDialog.visible">
      <q-card style="min-width: 400px; max-width: 700px;">
        <q-card-section>
          <div class="text-h6">{{ summaryFor(detailsDialog.row) }}</div>
          <div class="text-caption text-grey-7" v-if="detailsDialog.row">
            {{ format(parseISO(detailsDialog.row.created_at), "PPpp") }} —
            {{ detailsDialog.row.user?.name ?? "Unknown user" }}
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section style="max-height: 60vh; overflow-y: auto;">
          <q-markup-table dense flat>
            <thead>
              <tr>
                <th class="text-left">Field</th>
                <th v-if="isDiff(detailsDialog.row)" class="text-left">Old</th>
                <th class="text-left">{{ isDiff(detailsDialog.row) ? "New" : "Value" }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(value, key) in detailsDialog.row?.changes" :key="key">
                <td class="text-left text-weight-medium">{{ key }}</td>
                <td v-if="isDiff(detailsDialog.row)" class="text-left">
                  {{ formatValue(value?.old) }}
                </td>
                <td class="text-left">
                  {{ formatValue(isDiff(detailsDialog.row) ? value?.new : value) }}
                </td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Close" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { matInfo, matSearch } from "@quasar/extras/material-icons";
import { format, parseISO } from "date-fns";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const store = useStore();

const search = ref("");
const pagination = ref({ page: 1, rowsPerPage: 20 });
const detailsDialog = ref({ visible: false, row: null });

const actionColors = {
  created: "positive",
  updated: "info",
  deleted: "negative",
};

const columns = [
  {
    name: "created_at",
    label: "Date",
    field: (row) => format(parseISO(row.created_at), "PPp"),
    align: "left",
    sortable: true,
  },
  {
    name: "user",
    label: "User",
    field: (row) => row.user?.name ?? "Unknown",
    align: "left",
  },
  {
    name: "action",
    label: "Action",
    field: "action",
    align: "center",
  },
  {
    name: "summary",
    label: "Section / Change",
    field: (row) => summaryFor(row),
    align: "left",
  },
  {
    name: "details",
    label: "",
    field: "",
    align: "center",
    style: "width: 48px;",
  },
];

const summaryFor = (row) => {
  if (!row) return "";
  if (row.description) return row.description;
  const target = [row.model_type, row.model_id ? `#${row.model_id}` : null]
    .filter(Boolean)
    .join(" ");
  return `${row.action} ${target}`.trim();
};

// Update-style entries store { field: { old, new } }; create/delete entries
// store the record's full attributes as plain field: value pairs.
const isDiff = (row) =>
  !!row?.changes &&
  Object.values(row.changes).every(
    (v) => v && typeof v === "object" && "old" in v && "new" in v,
  );

const formatValue = (value) => {
  if (value === null || value === undefined || value === "") return "—";
  if (typeof value === "object") return JSON.stringify(value);
  return String(value);
};

const filteredLogs = computed(() => {
  const q = search.value?.toLowerCase().trim();
  if (!q) return store.admin.change_logs ?? [];
  return (store.admin.change_logs ?? []).filter((row) =>
    [row.user?.name, row.model_type, row.description, row.action].some((f) =>
      f?.toLowerCase().includes(q),
    ),
  );
});
</script>
