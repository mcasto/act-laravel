<template>
  <q-table
    :rows="sessions"
    :columns="sessionColumns"
    dense
    hide-bottom
    :pagination="{ rowsPerPage: 0 }"
  >
    <template #top-left>
      <div class="text-h6">
        AUDITION DATES AND LOCATION
      </div>
    </template>
    <template #body-cell-address="{row}">
      <q-td class="text-center">
        <a :href="row.location_map_link" target="_blank">
          <q-btn icon="pin_drop" round flat dense>
            <q-tooltip>{{ row.location_address }}</q-tooltip>
          </q-btn></a
        >
      </q-td>
    </template>
  </q-table>
</template>

<script setup>
import { format, parseISO } from "date-fns";

const props = defineProps(["sessions"]);

const sessionColumns = [
  {
    label: "Date",
    name: "date",
    field: (row) => {
      return format(parseISO(row.session), "PP");
    },
    align: "left",
  },
  {
    label: "Time",
    name: "time",
    field: (row) => {
      return format(parseISO(row.session), "p");
    },
    align: "left",
  },
  {
    label: "Location",
    name: "location",
    field: "location_name",
    align: "left",
  },
  {
    label: "Map Link",
    name: "address",
    align: "center",
  },
];
</script>
