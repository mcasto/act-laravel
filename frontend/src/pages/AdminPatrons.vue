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
          </div>
        </div>
      </template>

      <template #body-cell-email="props">
        <q-td :props="props">
          <a :href="`mailto:${props.value}`">{{ props.value }}</a>
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
    </q-table>

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
                <q-item-section side>x{{ u.quantity }}</q-item-section>
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
  </div>
</template>

<script setup>
import { matSearch, matStar } from "@quasar/extras/material-icons";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";
import callApi from "src/assets/call-api";

const store = useStore();

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
];

const filteredRows = computed(() => {
  const rows = store.admin.patrons ?? [];
  if (!search.value) return rows;

  const q = search.value.toLowerCase();
  return rows.filter(
    (row) =>
      `${row.first_name} ${row.last_name}`.toLowerCase().includes(q) ||
      row.email?.toLowerCase().includes(q),
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
</script>
