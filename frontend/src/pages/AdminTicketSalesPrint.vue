<template>
  <q-btn :icon="matPrint" size="md" color="grey-7" flat round @click="open = true">
    <q-tooltip>Door sheet</q-tooltip>
  </q-btn>

  <q-dialog v-model="open" maximized>
    <q-card class="column no-wrap">
      <q-card-section class="row items-center q-gutter-x-sm bg-grey-2">
        <q-select
          v-model="selectedPerf"
          :options="perfOptions"
          label="Performance"
          dense
          outlined
          style="min-width: 200px;"
        />
        <q-space />
        <q-btn
          label="Export .xlsx"
          :icon="mdiFileExcel"
          color="positive"
          :loading="exporting"
          :disable="!selectedPerf"
          @click="exportXlsx"
        />
        <q-btn :icon="matClose" flat round @click="open = false" />
      </q-card-section>

      <q-card-section class="col overflow-auto">
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
              <th class="col-narrow">FR</th>
              <th class="col-wrap">Guest List</th>
              <th class="col-wrap">Angel Benefits</th>
              <th class="col-wrap">Comments</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="rec in sortedRecs"
              :key="rec.id"
              :class="{ 'row-comp': isComp(rec), 'row-flex': isFlex(rec) }"
            >
              <td>{{ doorLastName(rec) }}</td>
              <td>{{ doorFirstName(rec) }}</td>
              <td class="col-narrow text-center">{{ activeQty(rec) }}</td>
              <td>{{ rec.payment_method.label }}</td>
              <td class="col-narrow text-right">
                {{ isComp(rec) ? "" : "$" + amountDue(rec) }}
              </td>
              <td class="col-narrow text-right">
                {{ !isComp(rec) && amountCollected(rec) ? "$" + amountDue(rec) : "" }}
              </td>
              <td class="col-narrow text-center">
                {{ frontRowCount(rec) > 0 ? frontRowCount(rec) : "" }}
              </td>
              <td>{{ guestList(rec) }}</td>
              <td>{{ angelBenefits(rec) }}</td>
              <td class="col-wrap">{{ comments(rec) }}</td>
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
import { mdiFileExcel } from "@quasar/extras/mdi-v7";
import { Notify } from "quasar";
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

// Seats a sale still holds — quantity minus cancelled tickets (legacy rows
// with no quantity count as one). Fully cancelled sales don't print: nobody
// from them is coming.
const activeQty = (rec) =>
  rec.quantity == null
    ? 1
    : Math.max(0, Number(rec.quantity) - (Number(rec.cancelled) || 0));

const perfRecs = computed(() => {
  const active = props.recs.filter((r) => activeQty(r) > 0);
  if (!selectedPerf.value) return active;
  return active.filter(
    (r) => r.performance.id === selectedPerf.value.value,
  );
});

// door_last/door_first override what prints here — set from the
// purchaser's name by default but editable independently, so the patron
// reference (and anything keyed off it, like flex balances) stays intact
// even when the printed name is overridden. Comp rows don't have these
// (see TicketSaleController::store()'s comp branch), so they fall back to
// the patron's own name, same as any not-yet-backfilled legacy row.
const doorLastName = (rec) => rec.door_last || rec.patron.last_name;
const doorFirstName = (rec) => rec.door_first || rec.patron.first_name;

const sortedRecs = computed(() =>
  sortBy(perfRecs.value, (r) => doorLastName(r).toLowerCase()),
);

const isComp = (rec) => rec.payment_method.value === "comp";
const isFlex = (rec) => rec.payment_method.value === "flex";

const amountDue = (rec) => (activeQty(rec) * props.ticketPrice).toFixed(2);

// A comp ticket is confirmed at redemption time (see
// CompTixController::redeemComp()) and never editable back to
// unconfirmed, same as every other row — only an explicit `false` counts
// as unconfirmed.
const amountCollected = (rec) => rec.confirmed !== false;

// Comp & flex tickets are transferrable: the patron of record (the
// "-er" — comper/flexer) stays in Last/First Name, but the person who
// actually shows up at the door (the "-ee" — compee/flexee) belongs in
// Guest List instead. Both fall out of the same per-ticket name mechanism
// — a redeemed comp's one Ticket row is always named after its
// pickup_name (see redeemComp()), never left as a placeholder.
const guestList = (rec) => {
  // Named guests only — a ticket nobody's put a name to yet is
  // auto-labeled with its own ticket number as a placeholder (see
  // TicketSale::issueTickets()/reconcileTicketCount()), which isn't a
  // guest name worth printing here.
  return (rec.tickets ?? [])
    .filter((ticket) => ticket.name !== ticket.formatted_number)
    .map((ticket) => ticket.name)
    .join(", ");
};

// The purchaser's Angel concession benefits, unless the box office turned
// "Allow Angel benefits" off on this sale (on by default).
const angelBenefits = (rec) =>
  rec.allow_angel_benefits === false
    ? ""
    : (rec.patron?.angel_concession_benefits ?? []).join(", ");

// Two distinct sources feed this one column: the patron's own standing
// accessibility need (front_row) and this sale's own reserved front-row
// count for the party (also front_row, e.g. an Angel level's reserved
// seating — see ticket_sales.front_row). Both are rare, so a patron
// needing both at once is rarer still — simply summing them is good
// enough until that combination actually happens and proves it needs
// finer handling. This is distinct from ticket_sales.special_seating,
// which is now free-text notes (e.g. "aisle seat"), not a count.
const frontRowCount = (rec) =>
  (rec.patron?.front_row || 0) + (rec.front_row || 0);

// Falls back to the patron's own notes for older sales saved before
// ticket_sales.comments existed (there's no backfill for this one, unlike
// door_last/door_first) — same reasoning as doorLastName/doorFirstName.
// Only null falls back: '' means an admin cleared it on purpose.
const comments = (rec) => rec.comments ?? rec.patron?.comments ?? "";

// ---- Excel export of the door sheet ----
// Same rows and columns as the preview above (sortedRecs + the helpers),
// so the file can't drift from what's on screen. exceljs is loaded only
// when exporting, keeping it out of the page's normal bundle.

const HEADERS = [
  "Last Name", "First Name", "# Tickets", "Payment Method", "Amt Due",
  "Amt Collected", "FR", "Guest List", "Angel Benefits", "Comments",
];
const WIDTHS = [16, 14, 9, 18, 10, 13, 5, 30, 30, 36];
const MONEY_COLUMNS = [5, 6];
const CENTER_COLUMNS = [3, 7];
// Same dark fills as the on-screen .row-comp / .row-flex, with white text.
const ROW_FILLS = { comp: "FF7A4A00", flex: "FF101452" };
const WALK_IN_ROWS = walkInRows;

const exporting = ref(false);

const sheetRow = (rec) => {
  const comp = isComp(rec);
  const due = Number(amountDue(rec));
  return [
    doorLastName(rec),
    doorFirstName(rec),
    activeQty(rec),
    rec.payment_method.label,
    comp ? null : due,
    !comp && amountCollected(rec) ? due : null,
    frontRowCount(rec) > 0 ? frontRowCount(rec) : null,
    guestList(rec),
    angelBenefits(rec),
    comments(rec),
  ];
};

const fileName = () => {
  const name = `${props.showName} - ${selectedPerf.value?.label ?? ""} - Door Sheet`
    .replace(/[\\/:*?"<>|]+/g, "")
    .replace(/\s+/g, " ")
    .trim();
  return `${name}.xlsx`;
};

const exportXlsx = async () => {
  exporting.value = true;
  try {
    const { default: ExcelJS } = await import("exceljs");
    const workbook = new ExcelJS.Workbook();
    const sheet = workbook.addWorksheet("Door Sheet", {
      pageSetup: {
        orientation: "landscape",
        fitToPage: true,
        fitToWidth: 1,
        fitToHeight: 0,
        printTitlesRow: "2:2",
        margins: { left: 0.3, right: 0.3, top: 0.4, bottom: 0.4, header: 0.2, footer: 0.2 },
      },
      views: [{ state: "frozen", ySplit: 2 }],
    });

    sheet.columns = WIDTHS.map((width) => ({ width }));

    const title = sheet.addRow([`${props.showName} — ${selectedPerf.value?.label ?? ""}`]);
    title.font = { bold: true, size: 14 };
    sheet.mergeCells(1, 1, 1, HEADERS.length);

    const header = sheet.addRow(HEADERS);
    header.font = { bold: true };
    header.eachCell((cell) => {
      cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFE8E8E8" } };
    });

    for (const rec of sortedRecs.value) {
      const row = sheet.addRow(sheetRow(rec));
      const kind = isComp(rec) ? "comp" : isFlex(rec) ? "flex" : null;
      if (kind) {
        row.eachCell({ includeEmpty: true }, (cell) => {
          cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: ROW_FILLS[kind] } };
          cell.font = { color: { argb: "FFFFFFFF" } };
        });
      }
    }

    // Blank rows for walk-ins, as on the printed sheet.
    for (let i = 0; i < WALK_IN_ROWS; i++) {
      sheet.addRow(HEADERS.map(() => null)).height = 20;
    }

    const lastRow = sheet.rowCount;
    for (let r = 2; r <= lastRow; r++) {
      const row = sheet.getRow(r);
      for (let c = 1; c <= HEADERS.length; c++) {
        const cell = row.getCell(c);
        cell.border = {
          top: { style: "thin", color: { argb: "FFBBBBBB" } },
          left: { style: "thin", color: { argb: "FFBBBBBB" } },
          bottom: { style: "thin", color: { argb: "FFBBBBBB" } },
          right: { style: "thin", color: { argb: "FFBBBBBB" } },
        };
        cell.alignment = {
          vertical: "top",
          wrapText: c >= 8,
          horizontal: CENTER_COLUMNS.includes(c) ? "center" : undefined,
        };
        if (r > 2 && MONEY_COLUMNS.includes(c)) cell.numFmt = '"$"#,##0.00';
      }
    }

    const buffer = await workbook.xlsx.writeBuffer();
    const url = URL.createObjectURL(
      new Blob([buffer], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      }),
    );
    const link = document.createElement("a");
    link.href = url;
    link.download = fileName();
    link.click();
    URL.revokeObjectURL(url);
  } catch (error) {
    console.error(error);
    Notify.create({ type: "negative", message: "Couldn't export the door sheet." });
  } finally {
    exporting.value = false;
  }
};
</script>

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
