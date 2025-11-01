<template>
  <div v-if="store.admin.shows">
    <q-table :rows="store.admin.shows" grid :pagination="{ rowsPerPage: 6 }">
      <template #top>
        <q-toolbar>
          <q-toolbar-title>
            Shows
          </q-toolbar-title>
          <q-btn icon="add" round color="primary" to="new-show"></q-btn>
        </q-toolbar>
      </template>
      <template #item="props">
        <q-card bordered class="q-ma-sm" style="width: 30%;">
          <q-toolbar>
            <q-toolbar-title
              class="text-subtitle2"
              v-html="displayDate(props.row)"
            >
            </q-toolbar-title>
          </q-toolbar>
          <q-card-section>
            <router-link :to="`edit-show/${props.row.id}`">
              <show-poster
                :show="props.row"
                width="20vw"
                height="20vh"
              ></show-poster>
            </router-link>
          </q-card-section>
          <q-card-section>
            <div class="flex justify-between items-center">
              <div class="ellipsis" style="width: 80%;">
                {{ props.row.name }}
              </div>
              <div>
                <q-btn
                  icon="delete"
                  round
                  flat
                  size="small"
                  @click="store.deleteShow(props.row.id)"
                ></q-btn>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </template>
    </q-table>
  </div>
</template>

<script setup>
import { add, format, formatISO9075, parseISO } from "date-fns";
import { clone } from "lodash-es";
import ShowPoster from "src/components/ShowPoster.vue";
import { useStore } from "src/stores/store";

const store = useStore();

const displayDate = (show) => {
  // if performances, then return range of dates for performances
  if (show.performances?.length > 0) {
    const performances = clone(show.performances);
    const dateList = performances.map(({ date }) => date).sort();
    const first = format(parseISO(dateList.shift()), "LLL d, yyyy");
    const last =
      dateList.length == 0
        ? first
        : format(parseISO(dateList.pop()), "LLL d, yyyy");

    return `Performances: <div> ${first} - ${last}</div>`;
  }

  // else get ticket_sales_start
  return `Tickets On Sale: <div> ${format(
    parseISO(show.ticket_sales_start),
    "LLL d, yyyy"
  )}</div>`;
};
</script>
