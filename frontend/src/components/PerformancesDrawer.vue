<template>
  <div>
    <q-toolbar class="bg-primary text-white text-h6 justify-between">
      <div>
        <q-btn icon="add" flat round @click="newPerformance"></q-btn>
        Performances
      </div>
      <q-btn icon="save" flat round @click="$emit('update')"></q-btn>

      <q-btn icon="close" size="sm" flat round @click="$emit('close')"></q-btn>
    </q-toolbar>

    <q-markup-table separator="cell">
      <tbody>
        <q-tr
          v-for="performance of store.admin.show.performances.filter(
            ({ deleted }) => !deleted
          )"
          :key="performance.id"
        >
          <q-td class="text-center">
            <div class="cursor-pointer">
              {{ format(parseISO(performance.date), "PP") }}
              <q-popup-edit v-model="performance.date" v-slot="scope" buttons>
                <q-date v-model="scope.value" mask="YYYY-MM-DD"></q-date>
              </q-popup-edit>
            </div>
          </q-td>
          <q-td class="text-center">
            <div class="cursor-pointer">
              {{ performance.sold_out_target }}
              <q-tooltip>
                Sold Out Target
              </q-tooltip>
              <q-popup-edit
                v-model="performance.sold_out_target"
                v-slot="scope"
                buttons
              >
                <q-input
                  type="number"
                  v-model.number="scope.value"
                  label="Sold Out Target"
                  dense
                ></q-input>
              </q-popup-edit>
            </div>
          </q-td>
          <!-- <q-td class="text-center">
            <q-btn icon="img:/api/storage/fixr-icon" flat round>
              <q-tooltip>Fixr Link</q-tooltip>
            </q-btn>
            <q-popup-edit
              v-model="performance.fixr_link"
              v-slot="scope"
              buttons
              style="width: 55rem;"
            >
              <q-input
                type="text"
                v-model="scope.value"
                label="Fixr Link"
                dense
              >
                <template #append>
                  <q-btn icon="link" round flat @click="openLink(scope.value)">
                    <q-tooltip>Test Link</q-tooltip>
                  </q-btn>
                </template>
              </q-input>
            </q-popup-edit>
          </q-td> -->
          <q-td class="text-center">
            <q-btn icon="delete" flat round @click="performance.deleted = true">
              <q-tooltip>
                Delete Performance
              </q-tooltip>
            </q-btn>
          </q-td>
        </q-tr>
      </tbody>
    </q-markup-table>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { add, format, formatISO9075, parseISO, sub } from "date-fns";
import { clone } from "lodash-es";

const store = useStore();

const emits = defineEmits(["close", "update"]);

const openLink = (link) => {
  window.open(link);
};

const newPerformance = () => {
  // get date of last performance
  const lastPerformance = clone(
    store.admin.show.performances.filter(({ deleted }) => !deleted)
  ).pop() || {
    date: formatISO9075(sub(new Date(), { days: 1 }), {
      representation: "date",
    }),
  }; // defaults to yesterday if there are no performances

  // set date for new performance
  const newDate = formatISO9075(
    add(parseISO(lastPerformance.date), { days: 1 }),
    {
      representation: "date",
    }
  );

  // create new date (defaults to the day after the final performance or today if there are no performances)
  store.admin.show.performances.push({
    show_id: store.admin.show.id,
    date: newDate,
    sold_out_target: 50,
    fixr_link: "",
  });
};
</script>
