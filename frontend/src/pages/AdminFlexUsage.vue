<template>
  <div class="q-pa-md">
    <div class="text-h6 q-mb-md">Flex Usage</div>

    <div class="flex no-wrap items-center q-gutter-x-sm q-mb-md">
      <q-select
        :options="usage.seasons ?? []"
        :model-value="usage.season"
        label="Season"
        dense
        outlined
        stack-label
        style="min-width: 8rem;"
        @update:model-value="loadSeason"
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
      <q-btn flat round :icon="matUnfoldMore" @click="setAllExpanded(true)">
        <q-tooltip>Expand all</q-tooltip>
      </q-btn>
      <q-btn flat round :icon="matUnfoldLess" @click="setAllExpanded(false)">
        <q-tooltip>Collapse all</q-tooltip>
      </q-btn>
      <q-btn
        flat
        round
        :icon="matFileDownload"
        :disable="filteredPatrons.length === 0"
        @click="exportCsv"
      >
        <q-tooltip>Download CSV</q-tooltip>
      </q-btn>
    </div>

    <div v-if="filteredPatrons.length === 0" class="text-grey-6 q-pa-md">
      {{ search ? "No patrons match your search." : "No flex packages or flex usage this season." }}
    </div>

    <q-table
      v-else
      :rows="filteredPatrons"
      :columns="columns"
      row-key="patron_id"
      dense
      flat
      bordered
      v-model:pagination="pagination"
      hide-pagination
    >
      <template #body="props">
        <q-tr
          :props="props"
          class="cursor-pointer"
          @click="expanded[props.row.patron_id] = !expanded[props.row.patron_id]"
        >
          <q-td auto-width>
            <q-icon
              v-if="props.row.usage.length"
              :name="expanded[props.row.patron_id] ? matExpandLess : matExpandMore"
            />
          </q-td>
          <q-td key="name" :props="props">{{ props.row.last_name }}, {{ props.row.first_name }}</q-td>
          <q-td key="email" :props="props">{{ props.row.email }}</q-td>
          <q-td key="tickets_purchased" :props="props">{{ props.row.tickets_purchased }}</q-td>
          <q-td key="tickets_used" :props="props">{{ props.row.tickets_used }}</q-td>
          <q-td key="tickets_remaining" :props="props">
            <span :class="{ 'text-negative text-weight-bold': props.row.tickets_remaining < 0 }">
              {{ props.row.tickets_remaining }}
            </span>
            <q-tooltip v-if="props.row.tickets_purchased === 0">
              No flex package on record for this season
            </q-tooltip>
          </q-td>
        </q-tr>
        <q-tr v-if="expanded[props.row.patron_id] && props.row.usage.length" :props="props">
          <q-td colspan="100%" class="bg-grey-1">
            <q-markup-table flat dense class="bg-transparent q-ml-lg">
              <thead>
                <tr>
                  <th class="text-left">Show</th>
                  <th class="text-left">Performance</th>
                  <th class="text-center">Tickets</th>
                  <th class="text-center">No-shows</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="use in props.row.usage" :key="use.id">
                  <td>{{ use.show }}</td>
                  <td>{{ use.formatted_date }} {{ use.formatted_time }}</td>
                  <td class="text-center">{{ use.quantity }}</td>
                  <td class="text-center">{{ use.no_show || "—" }}</td>
                </tr>
              </tbody>
            </q-markup-table>
          </q-td>
        </q-tr>
      </template>
    </q-table>
  </div>
</template>

<script setup>
import {
  matExpandLess,
  matExpandMore,
  matFileDownload,
  matSearch,
  matUnfoldLess,
  matUnfoldMore,
} from "@quasar/extras/material-icons";
import { exportFile, Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const store = useStore();

const usage = computed(() => store.admin.flex_usage ?? { patrons: [] });
const search = ref("");
const expanded = ref({});
const pagination = ref({ rowsPerPage: 0, sortBy: "name", descending: false });

const loadSeason = async (season) => {
  store.admin.flex_usage = await callApi({
    path: `/admin/flex-usage?season=${encodeURIComponent(season)}`,
    method: "get",
    useAuth: true,
  });
  expanded.value = {};
};

const filteredPatrons = computed(() => {
  const patrons = usage.value.patrons ?? [];
  if (!search.value) return patrons;
  const q = search.value.toLowerCase();
  return patrons.filter(
    (row) =>
      `${row.first_name} ${row.last_name}`.toLowerCase().includes(q) ||
      (row.email ?? "").toLowerCase().includes(q),
  );
});

const columns = [
  { name: "expand", label: "", field: "", align: "left" },
  {
    name: "name",
    label: "Name",
    field: (row) => `${row.last_name}, ${row.first_name}`,
    align: "left",
    sortable: true,
    sort: (a, b) => a.localeCompare(b),
  },
  { name: "email", label: "Email", field: "email", align: "left", sortable: true },
  { name: "tickets_purchased", label: "Purchased", field: "tickets_purchased", align: "center", sortable: true },
  { name: "tickets_used", label: "Used", field: "tickets_used", align: "center", sortable: true },
  { name: "tickets_remaining", label: "Remaining", field: "tickets_remaining", align: "center", sortable: true },
];

const setAllExpanded = (open) => {
  const next = {};
  filteredPatrons.value.forEach((row) => {
    if (row.usage.length) next[row.patron_id] = open;
  });
  expanded.value = next;
};

// Same quoting/escaping recipe as AdminFlexPurchases.vue's CSV export.
const wrapCsvValue = (val) => {
  const formatted = val === undefined || val === null ? "" : String(val);
  return `"${formatted.split('"').join('""')}"`;
};

// One row per patron, with where they used their tickets in a single
// column so the sheet stays one-line-per-patron for box office.
const exportCsv = () => {
  const header = ["Last Name", "First Name", "Email", "Purchased", "Used", "Remaining", "Used At"];
  const lines = [header.map(wrapCsvValue).join(",")];

  filteredPatrons.value.forEach((row) =>
    lines.push(
      [
        row.last_name,
        row.first_name,
        row.email,
        row.tickets_purchased,
        row.tickets_used,
        row.tickets_remaining,
        row.usage.map((use) => `${use.show} ${use.formatted_date} (${use.quantity})`).join("; "),
      ]
        .map(wrapCsvValue)
        .join(","),
    ),
  );

  const status = exportFile(`flex-usage-${usage.value.season}.csv`, lines.join("\r\n"), "text/csv");

  if (status !== true) {
    Notify.create({
      message: "Browser denied file download — please allow popups/downloads for this site",
      color: "negative",
    });
  }
};
</script>
