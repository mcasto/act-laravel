<template>
  <q-btn :icon="matPrint" size="md" color="grey-7" flat round @click="open = true">
    <q-tooltip>Print sheet for door</q-tooltip>
  </q-btn>

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
        <q-btn label="Print" :icon="matPrint" color="primary" @click="doPrint" />
        <q-btn :icon="matClose" flat round @click="open = false" />
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
              <th>Payment Method</th>
              <th class="col-narrow">Amt Due</th>
              <th class="col-narrow">Amt Collected</th>
              <th class="col-narrow">Special Seating</th>
              <th class="col-wrap">Guest List</th>
              <th class="col-wrap">Angel Benefits</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="rec in sortedRecs"
              :key="rec.id"
              :class="{ 'row-comp': isComp(rec), 'row-flex': isFlex(rec) }"
            >
              <td>{{ rec.patron.last_name }}</td>
              <td>{{ rec.patron.first_name }}</td>
              <td class="col-narrow text-center">{{ rec.quantity || 1 }}</td>
              <td>{{ rec.payment_method.label }}</td>
              <td class="col-narrow text-right">
                {{ isComp(rec) ? "" : "$" + amountDue(rec) }}
              </td>
              <td class="col-narrow"></td>
              <td class="col-narrow text-center">
                {{ specialSeating(rec) > 0 ? specialSeating(rec) : "" }}
              </td>
              <td>{{ guestList(rec) }}</td>
              <td>{{ angelBenefits(rec) }}</td>
            </tr>
            <!-- Walk-in blank rows -->
            <tr v-for="i in walkInRows" :key="'wi-' + i" class="walk-in-row">
              <td></td>
              <td></td>
              <td class="col-narrow"></td>
              <td></td>
              <td class="col-narrow"></td>
              <td class="col-narrow"></td>
              <td class="col-narrow"></td>
              <td></td>
              <td></td>
            </tr>
          </tbody>
        </table>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { matClose, matPrint } from "@quasar/extras/material-icons";
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
const isFlex = (rec) => rec.payment_method.value === "flex";

const amountDue = (rec) => ((rec.quantity || 1) * props.ticketPrice).toFixed(2);

// Comp & flex tickets are transferrable: the patron of record (the
// "-er" — comper/flexer) stays in Last/First Name, but the person who
// actually shows up at the door (the "-ee" — compee/flexee) belongs in
// Guest List instead. For flex this already falls out of the normal
// per-ticket name mechanism below. For comp there's no per-ticket
// breakdown — the door attendee is captured separately as pickup_name.
const guestList = (rec) => {
  if (isComp(rec)) return rec.patron?.pickup_name ?? "";

  // Named guests only — a ticket nobody's put a name to yet is
  // auto-labeled with its own ticket number as a placeholder (see
  // TicketSale::issueTickets()/reconcileTicketCount()), which isn't a
  // guest name worth printing here.
  return (rec.tickets ?? [])
    .filter((ticket) => ticket.name !== ticket.formatted_number)
    .map((ticket) => ticket.name)
    .join(", ");
};

const angelBenefits = (rec) =>
  (rec.patron?.angel_concession_benefits ?? []).join(", ");

// Two distinct sources feed this one column: the patron's own standing
// accessibility need (front_row) and this sale's own reserved-party
// seating (special_seating, e.g. an Angel level's reserved seating). Both
// are rare, so a patron needing both at once is rarer still — simply
// summing them is good enough until that combination actually happens and
// proves it needs finer handling.
const specialSeating = (rec) =>
  (rec.patron?.front_row || 0) + (rec.special_seating || 0);

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

.col-wrap {
  white-space: normal;
  max-width: 220px;
}

.walk-in-row td {
  background: #fafafa;
  height: 22px;
}

/*
 * Dark backgrounds (not light tints) so comp vs. flex rows stay visibly
 * distinct from each other and from the plain rows even in grayscale —
 * the whole point of this sheet is that it's printed. The two colors are
 * chosen with clearly different luminance, not just different hue, so a
 * B&W printer still shows two different shades of gray.
 */
.row-comp td {
  background: #7a4a00;
  color: #fff;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

.row-flex td {
  background: #101452;
  color: #fff;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

.print-header {
  font-size: 14px;
}
</style>
