<template>
  <q-btn
    icon="print"
    size="md"
    color="grey-7"
    flat
    round
    @click="open = true"
  />

  <q-dialog v-model="open" maximized>
    <q-card class="column no-wrap">
      <q-card-section class="row items-center q-gutter-x-sm no-print bg-grey-2">
        <q-select
          v-model="selectedPerf"
          :options="perfOptions"
          label="Performance"
          dense
          outlined
          style="min-width: 200px;"
        />
        <q-space />
        <q-btn label="Print" icon="print" color="primary" @click="doPrint" />
        <q-btn icon="close" flat round @click="open = false" />
      </q-card-section>

      <q-card-section class="col overflow-auto print-area">
        <div class="print-header q-mb-sm">
          <strong>{{ showName }}</strong>
          <span v-if="selectedPerf"> &mdash; {{ selectedPerf.label }}</span>
        </div>

        <table class="box-office-table">
          <thead>
            <tr>
              <th>Last Name</th>
              <th>First Name</th>
              <th class="col-narrow"># Tickets</th>
              <th>Type</th>
              <th class="col-narrow">Amt Due</th>
              <th class="col-narrow">Amt Collected</th>
              <th>Email</th>
              <th v-for="m in paidMethods" :key="m" class="col-narrow">
                {{ m }}
              </th>
              <th class="col-narrow">Total Paid</th>
              <th class="col-narrow">Comp</th>
              <th class="col-narrow">No Show</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rec in sortedRecs" :key="rec.id">
              <td>{{ rec.patron.last_name }}</td>
              <td>{{ rec.patron.first_name }}</td>
              <td class="col-narrow text-center">{{ rec.quantity || 1 }}</td>
              <td>{{ rec.payment_method.label }}</td>
              <td class="col-narrow text-right">
                {{ isComp(rec) ? "" : "$" + amountDue(rec) }}
              </td>
              <td class="col-narrow"></td>
              <td>{{ rec.patron.email }}</td>
              <td
                v-for="m in paidMethods"
                :key="m"
                class="col-narrow text-center"
              >
                {{
                  !isComp(rec) && rec.payment_method.label === m
                    ? rec.quantity || 1
                    : ""
                }}
              </td>
              <td class="col-narrow text-center">
                {{ isComp(rec) ? "" : rec.quantity || 1 }}
              </td>
              <td class="col-narrow text-center">
                {{ isComp(rec) ? rec.quantity || 1 : "" }}
              </td>
              <td class="col-narrow"></td>
            </tr>
            <!-- Walk-in blank rows -->
            <tr v-for="i in walkInRows" :key="'wi-' + i" class="walk-in-row">
              <td></td>
              <td></td>
              <td class="col-narrow"></td>
              <td></td>
              <td class="col-narrow"></td>
              <td class="col-narrow"></td>
              <td></td>
              <td v-for="m in paidMethods" :key="m" class="col-narrow"></td>
              <td class="col-narrow"></td>
              <td class="col-narrow"></td>
              <td class="col-narrow"></td>
            </tr>
          </tbody>
        </table>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { uniqBy, sortBy } from "lodash-es";
import { computed, ref, watch } from "vue";

const props = defineProps({
  recs: { type: Array, required: true },
  ticketPrice: { type: Number, default: 0 },
  showName: { type: String, default: "" },
});

const open = ref(false);
const selectedPerf = ref(null);
const walkInRows = 20;

const perfOptions = computed(() =>
  uniqBy(props.recs, (r) => r.performance.id).map((r) => ({
    label: `${r.performance.formatted_date} ${r.performance.formatted_time}`,
    value: r.performance.id,
  })),
);

watch(
  perfOptions,
  (opts) => {
    if (opts.length && !selectedPerf.value) selectedPerf.value = opts[0];
  },
  { immediate: true },
);

const perfRecs = computed(() => {
  if (!selectedPerf.value) return props.recs;
  return props.recs.filter(
    (r) => r.performance.id === selectedPerf.value.value,
  );
});

const sortedRecs = computed(() =>
  sortBy(perfRecs.value, (r) => r.patron.last_name.toLowerCase()),
);

const isComp = (rec) => rec.payment_method.value === "comp";

const amountDue = (rec) => ((rec.quantity || 1) * props.ticketPrice).toFixed(2);

const paidMethods = computed(() =>
  uniqBy(
    perfRecs.value.filter((r) => !isComp(r)),
    (r) => r.payment_method.label,
  ).map((r) => r.payment_method.label),
);

const doPrint = () => window.print();
</script>

<style>
@media print {
  body > * {
    visibility: hidden;
  }
  .print-area,
  .print-area * {
    visibility: visible;
  }
  .print-area {
    position: fixed;
    inset: 0;
    overflow: visible;
    padding: 12px;
  }
  .no-print {
    display: none !important;
  }
}
</style>

<style scoped>
.box-office-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 11px;
}

.box-office-table th,
.box-office-table td {
  border: 1px solid #bbb;
  padding: 3px 6px;
  white-space: nowrap;
}

.box-office-table th {
  background: #e8e8e8;
  font-weight: 600;
  text-align: left;
}

.col-narrow {
  width: 1%;
}

.walk-in-row td {
  background: #fafafa;
  height: 22px;
}

.print-header {
  font-size: 14px;
}
</style>
