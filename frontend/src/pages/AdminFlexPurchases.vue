<template>
  <div>
    <div v-if="seasons.length === 0" class="text-grey-6 q-pa-md">
      No flex purchases on record.
    </div>

    <q-table
      v-else
      :rows="filteredRows"
      :columns="columns"
      row-key="email"
      dense
      v-model:pagination="pagination"
    >
      <template #top>
        <div class="full-width">
          <div class="flex no-wrap items-center q-gutter-x-sm q-mb-sm">
            <q-select
              :options="seasons"
              v-model="season"
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
    </q-table>
  </div>
</template>

<script setup>
import { matSearch } from "@quasar/extras/material-icons";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const store = useStore();

const search = ref("");
const pagination = ref({ page: 1, rowsPerPage: 12 });

const seasons = computed(() =>
  [...new Set(store.admin.flex_purchases.map((row) => row.season))].sort(
    (a, b) => (a < b ? 1 : -1),
  ),
);

const season = ref(seasons.value[0] ?? null);

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
    name: "tickets_purchased",
    label: "Purchased",
    field: "tickets_purchased",
    align: "center",
    sortable: true,
  },
  {
    name: "tickets_used",
    label: "Used",
    field: "tickets_used",
    align: "center",
    sortable: true,
  },
];

const rows = computed(() =>
  store.admin.flex_purchases.filter((row) => row.season === season.value),
);

const filteredRows = computed(() => {
  if (!search.value) return rows.value;
  const q = search.value.toLowerCase();
  return rows.value.filter(
    (row) =>
      `${row.first_name} ${row.last_name}`.toLowerCase().includes(q) ||
      row.email.toLowerCase().includes(q),
  );
});
</script>
