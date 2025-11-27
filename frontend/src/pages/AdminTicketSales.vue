<template>
  <div>
    <q-table :rows="recs" :columns="columns">
      <template #top>
        <q-select
          :options="shows"
          v-model="show"
          :label="show ? 'Show' : 'Select Show'"
          dense
          outlined
          stack-label
          style="min-width: 8rem;"
        ></q-select>
      </template>
      <template #body-cell-purchaser="{row}">
        <q-td>
          <div class="flex justify-between items-center">
            <div>{{ row.first_name }} {{ row.last_name }}</div>
            <q-btn icon="info" flat round>
              <q-menu>
                <q-list dense separator>
                  <q-item>
                    <q-item-section side>
                      Email:
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>
                        <a
                          :href="`mailto:${row.first_name} ${row.last_name} <${row.email}>`"
                        >
                          {{ row.email }}
                        </a>
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section side>
                      Phone:
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>
                        {{ row.mobile_number }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
          </div>
        </q-td>
      </template>
    </q-table>
  </div>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { uniq } from "lodash-es";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const store = useStore();

const columns = [
  {
    label: "Name",
    name: "purchaser",
    align: "left",
  },
  {
    label: "Tickets",
    name: "num-tix",
    field: (rec) => rec.quantity || "?",
    align: "center",
  },
  {
    label: "Sold At",
    name: "sold-at",
    field: (rec) => format(parseISO(rec.sold_at), "PPp"),
    align: "center",
  },
  {
    label: "Show",
    name: "show",
    field: "show",
    align: "left",
  },
  {
    label: "Performance",
    name: "performance_date",
    field: (rec) =>
      `${rec.performance.formatted_date}, ${rec.performance.formatted_time}`,
    align: "left",
  },
];

const show = ref(null);

const recs = computed(() => {
  if (!show.value) return [];

  return store.admin.tickets.filter(
    (rec) => rec.performance.show.name == show.value
  );
});

const shows = computed(() => {
  return uniq(
    store.admin.tickets.map((rec) => {
      return rec.performance.show.name;
    })
  );
});
</script>
