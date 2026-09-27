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
      <q-select
        v-model="selectedShowId"
        :options="showOptions"
        emit-value
        map-options
        label="Show"
        dense
        outlined
        stack-label
        :disable="showOptions.length === 0"
        style="min-width: 16rem;"
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
        :disable="!selectedShow?.tickets_used"
        @click="exportCsv"
      >
        <q-tooltip>Download CSV</q-tooltip>
      </q-btn>
    </div>

    <div v-if="!selectedShow" class="text-grey-6 q-pa-md">
      No performances scheduled for this season.
    </div>

    <div v-else-if="!filteredShow" class="text-grey-6 q-pa-md">
      No flex usage matches your search.
    </div>

    <q-card v-else flat bordered class="q-mb-md">
      <q-card-section class="row items-center justify-between q-py-sm">
        <div class="text-subtitle1 text-weight-medium">{{ selectedShow.name }}</div>
        <div class="text-caption text-grey-7">
          {{ selectedShow.tickets_used }} {{ selectedShow.tickets_used === 1 ? "ticket" : "tickets" }} ·
          {{ selectedShow.patron_count }} {{ selectedShow.patron_count === 1 ? "patron" : "patrons" }}
        </div>
      </q-card-section>

      <q-separator />

      <q-list separator>
        <q-expansion-item
          v-for="performance in filteredShow.performances"
          :key="performance.id"
          v-model="expanded[performance.id]"
          :disable="performance.sales.length === 0"
          dense
          expand-separator
        >
          <template #header>
            <q-item-section>
              <q-item-label>{{ performance.formatted_date }}</q-item-label>
              <q-item-label caption>{{ performance.formatted_time }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-badge
                :color="performance.tickets_used ? 'primary' : 'grey-5'"
                :label="`${performance.tickets_used} flex`"
              />
            </q-item-section>
          </template>

          <q-markup-table flat dense class="q-mx-md q-mb-sm">
            <thead>
              <tr>
                <th class="text-left">Name</th>
                <th class="text-left">Email</th>
                <th class="text-center">Tickets</th>
                <th class="text-center">No-shows</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sale in performance.sales" :key="sale.id">
                <td>{{ sale.last_name }}, {{ sale.first_name }}</td>
                <td>{{ sale.email }}</td>
                <td class="text-center">{{ sale.quantity }}</td>
                <td class="text-center">{{ sale.no_show || "—" }}</td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-expansion-item>
      </q-list>
    </q-card>
  </div>
</template>

<script setup>
import {
  matFileDownload,
  matSearch,
  matUnfoldLess,
  matUnfoldMore,
} from "@quasar/extras/material-icons";
import { exportFile, Notify } from "quasar";
import callApi from "src/assets/call-api";
import getCurrentShow from "src/assets/get-current-show";
import { useStore } from "src/stores/store";
import { computed, ref, watch } from "vue";

const store = useStore();

const usage = computed(() => store.admin.flex_usage ?? { shows: [] });
const search = ref("");
const expanded = ref({});

const showOptions = computed(() =>
  usage.value.shows.map((show) => ({ label: show.name, value: show.id })),
);

// Defaults to the show running or coming up next (same "Current Show" rule
// the rest of the admin uses), falling back to the season's last show once
// every show in it has closed.
const defaultShowId = () =>
  (getCurrentShow(usage.value.shows) ?? usage.value.shows.at(-1))?.id ?? null;

const selectedShowId = ref(defaultShowId());

const selectedShow = computed(
  () => usage.value.shows.find((show) => show.id === selectedShowId.value) ?? null,
);

watch(selectedShowId, () => {
  expanded.value = {};
  if (search.value) setAllExpanded(true);
});

const loadSeason = async (season) => {
  store.admin.flex_usage = await callApi({
    path: `/admin/flex-usage?season=${encodeURIComponent(season)}`,
    method: "get",
    useAuth: true,
  });
  selectedShowId.value = defaultShowId();
};

const matchesSearch = (sale) => {
  if (!search.value) return true;
  const q = search.value.toLowerCase();
  return (
    `${sale.first_name} ${sale.last_name}`.toLowerCase().includes(q) ||
    (sale.email ?? "").toLowerCase().includes(q)
  );
};

// While searching, narrow each performance of the selected show to the
// matching patrons and drop performances left with nothing, so box office
// can find one patron's redemptions quickly. Null if nothing matches. The
// totals on the card stay the unfiltered ones.
const filteredShow = computed(() => {
  const show = selectedShow.value;
  if (!show || !search.value) return show;

  const performances = show.performances
    .map((performance) => ({
      ...performance,
      sales: performance.sales.filter(matchesSearch),
    }))
    .filter((performance) => performance.sales.length > 0);

  return performances.length ? { ...show, performances } : null;
});

// Auto-expand every matching performance when a search is entered.
watch(search, (val) => {
  if (val) setAllExpanded(true);
});

const setAllExpanded = (open) => {
  const next = {};
  filteredShow.value?.performances.forEach((performance) => {
    if (performance.sales.length) next[performance.id] = open;
  });
  expanded.value = next;
};

// Same quoting/escaping recipe as AdminFlexPurchases.vue's CSV export.
const wrapCsvValue = (val) => {
  const formatted = val === undefined || val === null ? "" : String(val);
  return `"${formatted.split('"').join('""')}"`;
};

const exportCsv = () => {
  const show = filteredShow.value;
  if (!show) return;

  const header = ["Show", "Date", "Time", "Last Name", "First Name", "Email", "Tickets", "No-shows"];
  const lines = [header.map(wrapCsvValue).join(",")];

  show.performances.forEach((performance) =>
    performance.sales.forEach((sale) =>
      lines.push(
        [
          show.name,
          performance.formatted_date,
          performance.formatted_time,
          sale.last_name,
          sale.first_name,
          sale.email,
          sale.quantity,
          sale.no_show,
        ]
          .map(wrapCsvValue)
          .join(","),
      ),
    ),
  );

  const slug = show.name.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "");
  const status = exportFile(`flex-usage-${slug}.csv`, lines.join("\r\n"), "text/csv");

  if (status !== true) {
    Notify.create({
      message: "Browser denied file download — please allow popups/downloads for this site",
      color: "negative",
    });
  }
};
</script>
