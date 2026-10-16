<template>
  <div>
    <q-table
      :rows="filteredRows"
      :columns="columns"
      row-key="key"
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
            placeholder="Search by user, section, field, or value..."
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

      <template #body-cell-old="props">
        <q-td :props="props" class="value-cell">
          {{ props.value }}
          <q-tooltip v-if="props.value.length > VALUE_TOOLTIP_AT" max-width="600px">
            <div style="white-space: pre-wrap;">{{ props.value }}</div>
          </q-tooltip>
        </q-td>
      </template>

      <template #body-cell-new="props">
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
const VALUE_TOOLTIP_AT = 60;

const actionColors = {
  created: "positive",
  updated: "info",
  deleted: "negative",
};

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
    name: "user",
    label: "User",
    field: "user",
    align: "left",
    sortable: true,
  },
  {
    name: "action",
    label: "Action",
    field: "action",
    align: "center",
    sortable: true,
  },
  {
    name: "section",
    label: "Section",
    field: "section",
    align: "left",
    sortable: true,
  },
  {
    name: "field",
    label: "Field",
    field: "field",
    align: "left",
    sortable: true,
  },
  {
    name: "old",
    label: "Old Value",
    field: "old",
    align: "left",
    sortable: true,
  },
  {
    name: "new",
    label: "New Value",
    field: "new",
    align: "left",
    sortable: true,
  },
];

// Bookkeeping columns that every created/deleted record carries and that
// say nothing about what was actually entered.
const NOISE_FIELDS = ["id", "created_at", "updated_at", "deleted_at"];

const sectionFor = (log) => {
  if (log.description) return log.description;
  return [log.model_type, log.model_id ? `#${log.model_id}` : null]
    .filter(Boolean)
    .join(" ");
};

// "recognition_name" -> "Recognition Name"
const humanize = (key) =>
  String(key)
    .replace(/_/g, " ")
    .replace(/\b\w/g, (c) => c.toUpperCase());

const formatValue = (value) => {
  if (value === null || value === undefined || value === "") return "—";
  if (typeof value === "object") return JSON.stringify(value);
  return String(value);
};

const isPlainObject = (v) => !!v && typeof v === "object" && !Array.isArray(v);
const isOldNewPair = (v) => isPlainObject(v) && "old" in v && "new" in v;

// One { field, old, new } per changed field. Entries come in a few shapes:
//  - model updates and button templates: { field: { old, new } }
//  - flat-file config saves: { old: {...}, new: {...} } (whole config) or
//    { old: x, new: y } (a single setting like the active season)
//  - model creates/deletes and one-off actions: { field: value }
const fieldChanges = (log) => {
  const changes = log.changes;
  if (!isPlainObject(changes) || !Object.keys(changes).length) {
    return [{ field: "", old: "", new: "" }];
  }

  if (isOldNewPair(changes) && Object.keys(changes).length === 2) {
    const { old: oldVal, new: newVal } = changes;
    if (isPlainObject(oldVal) || isPlainObject(newVal)) {
      const keys = new Set([
        ...Object.keys(oldVal ?? {}),
        ...Object.keys(newVal ?? {}),
      ]);
      const rows = [...keys]
        .filter(
          (k) =>
            JSON.stringify(oldVal?.[k]) !== JSON.stringify(newVal?.[k]),
        )
        .map((k) => ({ field: k, old: oldVal?.[k], new: newVal?.[k] }));
      return rows.length ? rows : [{ field: "", old: "", new: "" }];
    }
    return [{ field: "", old: oldVal, new: newVal }];
  }

  return Object.entries(changes)
    .filter(([k]) => log.action === "updated" || !NOISE_FIELDS.includes(k))
    .map(([k, v]) => {
      if (isOldNewPair(v)) return { field: k, old: v.old, new: v.new };
      if (log.action === "deleted") return { field: k, old: v, new: null };
      return { field: k, old: null, new: v };
    });
};

const rows = computed(() =>
  (store.admin.change_logs ?? []).flatMap((log) => {
    const base = {
      created_at: log.created_at,
      user: log.user?.name ?? "Unknown",
      action: log.action,
      section: sectionFor(log),
    };
    return fieldChanges(log).map((change, i) => {
      // A bare description entry (nothing recorded but the section) shows
      // empty value cells rather than a pair of dashes.
      const hasValues =
        !!change.field || change.old != null || change.new != null;
      return {
        ...base,
        key: `${log.id}-${i}`,
        field: change.field ? humanize(change.field) : "",
        rawField: change.field ?? "",
        old: hasValues ? formatValue(change.old) : "",
        new: hasValues ? formatValue(change.new) : "",
      };
    });
  }),
);

const filteredRows = computed(() => {
  const q = search.value?.toLowerCase().trim();
  if (!q) return rows.value;
  return rows.value.filter((row) =>
    [
      row.user,
      row.action,
      row.section,
      row.field,
      row.rawField,
      row.old,
      row.new,
    ].some((f) => f?.toLowerCase().includes(q)),
  );
});
</script>

<style scoped>
.value-cell {
  max-width: 280px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
