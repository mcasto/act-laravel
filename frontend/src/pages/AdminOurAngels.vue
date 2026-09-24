<template>
  <div>
    <div class="row justify-end q-pa-sm">
      <q-btn
        color="primary"
        outline
        :icon="matHistoryEdu"
        label="Angels By Season"
        @click="openSeasonDialog()"
      />
    </div>

    <q-splitter :model-value="30">
      <template #before>
        <q-page class="q-pa-md">
          <div class="text-h6 q-mb-md">Angel Levels</div>

          <div class="q-mb-md">
            <q-btn
              color="primary"
              :icon="matAdd"
              label="Add Level"
              :disable="isReadOnly"
              @click="openLevelDialog()"
            />
          </div>

          <q-list separator>
            <q-item
              v-for="level in angelLevels"
              :key="`level-${level.id}`"
              clickable
              :active="selectedLevel?.id === level.id"
              @click="selectLevel(level)"
            >
              <q-item-section>
                <q-item-label>{{ level.label }}</q-item-label>
                <q-item-label caption>
                  {{ level.min_amount_formatted }}+ ({{
                    level.angels?.length || 0
                  }}
                  angels)
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <div class="row q-gutter-x-xs">
                  <q-btn
                    :icon="matEdit"
                    flat
                    dense
                    size="sm"
                    :disable="isReadOnly"
                    @click.stop="openLevelDialog(level)"
                  />
                  <q-btn
                    :icon="matDelete"
                    flat
                    dense
                    size="sm"
                    color="negative"
                    :disable="isReadOnly"
                    @click.stop="deleteLevel(level)"
                  />
                </div>
              </q-item-section>
            </q-item>
          </q-list>

          <div
            v-if="angelLevels.length === 0"
            class="text-center q-mt-md text-grey"
          >
            No angel levels yet. Add one to get started.
          </div>
        </q-page>
      </template>

      <template #after>
        <q-page class="q-pa-md">
          <div v-if="selectedLevel">
            <div class="q-mb-lg">
              <div class="text-subtitle1 text-weight-bold q-mb-xs">
                {{ selectedLevel.label }} Benefits
              </div>

              <q-list v-if="selectedLevel.benefits?.length" dense bordered separator>
                <q-item v-for="(benefit, index) in selectedLevel.benefits" :key="index">
                  <q-item-section>{{ benefit.text }}</q-item-section>
                  <q-item-section side v-if="benefit.concession">
                    <q-badge color="teal" text-color="white">Concession</q-badge>
                  </q-item-section>
                </q-item>
              </q-list>
              <div v-else class="text-grey text-caption">
                No benefits configured for this level.
              </div>
            </div>

            <q-separator class="q-mb-md" />

            <div class="row justify-between items-center q-mb-md">
              <div class="text-h6">{{ selectedLevel.label }} Angels</div>
              <q-btn
                color="primary"
                :icon="matAdd"
                label="Add Angel"
                :disable="isReadOnly"
                @click="openAngelDialog()"
              />
            </div>

            <q-list separator>
              <q-item
                v-for="angel in selectedLevel.angels"
                :key="`angel-${angel.id}`"
              >
                <q-item-section>
                  <q-item-label
                    >{{ angel.first_name }} {{ angel.last_name }}</q-item-label
                  >
                  <q-item-label caption v-if="angel.founding_angel">
                    <q-badge color="amber" text-color="black"
                      >Founding Angel</q-badge
                    >
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="row q-gutter-x-xs">
                    <q-btn
                      :icon="matEdit"
                      flat
                      dense
                      size="sm"
                      :disable="isReadOnly"
                      @click="openAngelDialog(angel)"
                    />
                    <q-btn
                      :icon="matDelete"
                      flat
                      dense
                      size="sm"
                      color="negative"
                      :disable="isReadOnly"
                      @click="deleteAngel(angel)"
                    />
                  </div>
                </q-item-section>
              </q-item>
            </q-list>

            <div
              v-if="!selectedLevel.angels || selectedLevel.angels.length === 0"
              class="text-center q-mt-md text-grey"
            >
              No angels in this level yet.
            </div>
          </div>

          <div v-else class="text-center q-mt-xl text-grey">
            Select an angel level to view and manage angels
          </div>
        </q-page>
      </template>
    </q-splitter>

    <!-- Angel Level Dialog -->
    <q-dialog v-model="levelDialog" persistent full-width>
      <q-card style="min-width: 400px;">
        <q-card-section>
          <div class="text-h6">
            {{ levelForm.id ? "Edit" : "Add" }} Angel Level
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
            v-model="levelForm.label"
            label="Level Name"
            outlined
            dense
            class="q-mb-md"
          />
          <q-input
            v-model.number="levelForm.min_amount"
            label="Minimum Amount"
            type="number"
            outlined
            dense
            prefix="$"
            class="q-mb-md"
          />
          <q-input
            v-model="levelForm.fixr_link"
            label="Fixr Link"
            outlined
            dense
            class="q-mb-md"
          >
            <template #after>
              <q-btn
                round
                size="sm"
                :icon="matLink"
                color="primary"
                :disable="!levelForm.fixr_link"
                @click="openLink(levelForm.fixr_link)"
              />
            </template>
          </q-input>

          <div class="text-caption text-grey-7 q-mb-xs">Benefits</div>
          <div
            v-for="(benefit, index) in levelForm.benefits"
            :key="index"
            class="row items-center q-gutter-x-xs q-mb-xs"
          >
            <q-input
              v-model="benefit.text"
              dense
              outlined
              class="col"
              :disable="isReadOnly"
            />
            <q-checkbox
              v-model="benefit.concession"
              label="Concession"
              dense
              :disable="isReadOnly"
            >
              <q-tooltip>Concession-related (e.g. free drink, free snack)</q-tooltip>
            </q-checkbox>
            <q-btn
              :icon="matDelete"
              flat
              dense
              round
              size="sm"
              color="negative"
              :disable="isReadOnly"
              @click="levelForm.benefits.splice(index, 1)"
            />
          </div>
          <q-btn
            flat
            dense
            size="sm"
            color="primary"
            :icon="matAdd"
            label="Add Benefit"
            :disable="isReadOnly"
            @click="levelForm.benefits.push({ text: '', concession: false })"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            @click="saveLevel"
            :disable="isReadOnly || !levelForm.label || !levelForm.min_amount"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Angel Dialog -->
    <q-dialog v-model="angelDialog" persistent>
      <q-card style="min-width: 400px;">
        <q-card-section>
          <div class="text-h6">{{ angelForm.id ? "Edit" : "Add" }} Angel</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <template v-if="!angelForm.id">
            <q-btn-toggle
              v-model="patronMode"
              spread
              no-caps
              toggle-color="primary"
              outline
              :options="[
                { label: 'Existing Patron', value: 'existing' },
                { label: 'New Patron', value: 'new' },
              ]"
              class="q-mb-md full-width"
            />

            <template v-if="patronMode === 'existing'">
              <q-select
                v-model="selectedPatron"
                :options="patronSearchResults"
                use-input
                hide-selected
                fill-input
                input-debounce="300"
                label="Search by name or email"
                outlined
                dense
                class="q-mb-md"
                :option-label="(p) => `${p.first_name} ${p.last_name} — ${p.email}`"
                @filter="onPatronFilter"
                @update:model-value="onPatronSelected"
              >
                <template #no-option>
                  <q-item>
                    <q-item-section class="text-grey">
                      Type at least 2 characters to search
                    </q-item-section>
                  </q-item>
                </template>
              </q-select>
              <div v-if="selectedPatron" class="text-caption text-grey-7 q-mb-md">
                Selected: {{ selectedPatron.first_name }} {{ selectedPatron.last_name }} ({{ selectedPatron.email }})
              </div>
            </template>

            <template v-else>
              <q-input
                v-model="angelForm.email"
                type="email"
                label="Email"
                outlined
                dense
                class="q-mb-md"
                @blur="getPatron"
                hint="If this email already exists, we'll use that patron instead of creating a duplicate."
              />
            </template>
          </template>

          <q-input
            v-model="angelForm.first_name"
            label="First Name"
            outlined
            dense
            class="q-mb-md"
          />
          <q-input
            v-model="angelForm.last_name"
            label="Last Name"
            outlined
            dense
            class="q-mb-md"
          />
          <q-input
            v-model="recognitionName"
            label="Recognition Name"
            hint="How do you want the name(s) displayed?"
            outlined
            dense
            class="q-mb-md"
          />
          <q-input
            v-model.number="angelForm.donation_amount"
            type="number"
            label="Donation Amount"
            prefix="$"
            outlined
            dense
            class="q-mb-md"
          />
          <q-select
            v-model="angelForm.payment_method_value"
            :options="paymentMethodOptions"
            label="Payment Method"
            emit-value
            map-options
            outlined
            dense
            class="q-mb-md"
          />
          <q-select
            v-model="angelForm.season"
            :options="seasonOptions"
            label="Season"
            outlined
            dense
            class="q-mb-md"
          />
          <q-checkbox
            v-model="angelForm.founding_angel"
            label="Founding Angel"
            :true-value="true"
            :false-value="false"
            :disable="foundingAngelLocked"
          />
          <div v-if="foundingAngelLocked" class="text-caption text-grey-7">
            This patron is already a Founding Angel — status is permanent.
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            @click="saveAngel"
            :disabled="isReadOnly || !canSaveAngel"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Angels By Season Dialog -->
    <q-dialog v-model="seasonDialog">
      <q-card style="min-width: 600px; max-width: 95vw;">
        <q-card-section>
          <div class="text-h6">Angels By Season</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="row q-gutter-md q-mb-md">
            <q-select
              v-model="selectedSeason"
              :options="seasons"
              label="Season"
              outlined
              dense
              style="max-width: 200px;"
            />
            <q-input
              v-model="seasonAngelsFilter"
              label="Search by name"
              outlined
              dense
              clearable
              class="col"
            >
              <template #prepend>
                <q-icon :name="matSearch" />
              </template>
            </q-input>
            <q-btn
              flat
              round
              :icon="matFileDownload"
              :disable="seasonAngels.length === 0"
              @click="exportSeasonAngelsCsv"
            >
              <q-tooltip>Download CSV</q-tooltip>
            </q-btn>
          </div>

          <q-table
            ref="seasonTableRef"
            :rows="seasonAngels"
            :columns="seasonColumns"
            :filter="seasonAngelsFilter"
            :filter-method="filterSeasonAngelsByName"
            row-key="id"
            :loading="seasonAngelsLoading"
            flat
            bordered
          >
            <template #no-data>
              <div class="full-width text-center text-grey q-pa-md">
                No angels recorded for this season.
              </div>
            </template>
          </q-table>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Close" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import {
  matAdd,
  matDelete,
  matEdit,
  matFileDownload,
  matHistoryEdu,
  matLink,
  matSearch,
} from "@quasar/extras/material-icons";
import { computed, onMounted, ref, watch } from "vue";
import { cloneDeep } from "lodash-es";
import { exportFile, Notify } from "quasar";
import { format, parseISO } from "date-fns";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "our-angels") === "read-only",
);

const angelLevels = ref([]);
const selectedLevel = ref(null);
const levelDialog = ref(false);
const angelDialog = ref(false);
const seasonDialog = ref(false);
const seasons = ref([]);
const selectedSeason = ref(null);
const seasonAngels = ref([]);
const seasonAngelsLoading = ref(false);
const seasonAngelsFilter = ref("");
const seasonTableRef = ref(null);

const filterSeasonAngelsByName = (rows, terms) => {
  const needle = terms.toLowerCase();
  return rows.filter((row) =>
    row.recognition_name?.toLowerCase().includes(needle),
  );
};

const seasonColumns = [
  {
    name: "donated_at",
    label: "Date",
    field: "donated_at",
    align: "left",
    sortable: true,
    format: (val) => (val ? format(parseISO(val), "PP") : ""),
  },
  {
    name: "recognition_name",
    label: "Name",
    field: "recognition_name",
    align: "left",
    sortable: true,
  },
  {
    name: "angel_level",
    label: "Angel Level",
    field: "angel_level",
    align: "left",
    sortable: true,
  },
  {
    name: "donation_amount",
    label: "Donation Amount",
    field: "donation_amount",
    align: "left",
    sortable: true,
    format: (val) => (val != null ? `$${Number(val).toFixed(2)}` : ""),
  },
  {
    name: "payment_method",
    label: "Payment Method",
    field: "payment_method",
    align: "left",
    sortable: true,
  },
];

// Standard Quasar CSV-export recipe (see the Quasar docs' "Table -> CSV
// export" example) — wraps each value in quotes and escapes embedded
// quotes, running each column's own `format` so the CSV matches what's
// actually on screen (e.g. formatted dates/currency).
const wrapCsvValue = (val, formatFn, row) => {
  let formatted = formatFn !== undefined ? formatFn(val, row) : val;
  formatted = formatted === undefined || formatted === null ? "" : String(formatted);
  formatted = formatted.split('"').join('""');
  return `"${formatted}"`;
};

const exportSeasonAngelsCsv = () => {
  // filteredSortedRows reflects the current search filter/sort, so the
  // download always matches what's currently visible in the table.
  const rows = seasonTableRef.value?.filteredSortedRows ?? seasonAngels.value;

  const content = [seasonColumns.map((col) => wrapCsvValue(col.label))]
    .concat(
      rows.map((row) =>
        seasonColumns
          .map((col) =>
            wrapCsvValue(
              typeof col.field === "function" ? col.field(row) : row[col.field ?? col.name],
              col.format,
              row,
            ),
          )
          .join(","),
      ),
    )
    .join("\r\n");

  const status = exportFile(`angels-${selectedSeason.value ?? "season"}.csv`, content, "text/csv");

  if (status !== true) {
    Notify.create({
      message: "Browser denied file download — please allow popups/downloads for this site",
      color: "negative",
    });
  }
};

const levelForm = ref({
  id: null,
  label: "",
  min_amount: null,
  fixr_link: "",
  benefits: [],
});

const angelForm = ref({
  id: null,
  first_name: "",
  last_name: "",
  recognition_name: "",
  founding_angel: false,
  angel_level_id: null,
  email: "",
  donation_amount: null,
  payment_method_value: null,
  season: "",
});

// Keeps recognition_name in sync with first/last name until the admin
// deliberately edits that field directly (or it's an existing angel).
const recognitionNameEdited = ref(false);

const patronMode = ref("existing");
const selectedPatron = ref(null);
const patronSearchResults = ref([]);

// Snapshot of whether this patron was *already* a Founding Angel before
// this dialog session — drives the locked/disabled state. Kept separate
// from the live angelForm.founding_angel value so an admin can still
// uncheck a box they just checked by mistake, before saving.
const originalFoundingAngel = ref(false);
const foundingAngelLocked = computed(() => originalFoundingAngel.value === true);

const paymentMethods = ref([]);
const paymentMethodOptions = computed(() =>
  paymentMethods.value.map((pm) => ({ label: pm.label, value: pm.value })),
);

// The site's manually-overridable "active season" override (same one Flex
// purchases uses) — falls back to real calendar math for the brief window
// before the fetch below resolves.
const activeSeason = ref(null);

const calendarSeasonString = () => {
  const now = new Date();
  // getMonth() is 0-indexed, so 8 = September.
  const startYear = now.getMonth() >= 8 ? now.getFullYear() : now.getFullYear() - 1;
  return `${String(startYear).slice(-2)}-${String(startYear + 1).slice(-2)}`;
};

const currentSeasonString = () => activeSeason.value ?? calendarSeasonString();

const nextSeasonString = () => {
  const [startShort] = currentSeasonString().split("-");
  const startYear = 2000 + parseInt(startShort, 10) + 1;
  return `${String(startYear).slice(-2)}-${String(startYear + 1).slice(-2)}`;
};

const seasonOptions = computed(() => {
  const opts = [currentSeasonString(), nextSeasonString()];
  // Editing an older record shouldn't silently lose its actual season.
  if (angelForm.value.season && !opts.includes(angelForm.value.season)) {
    opts.unshift(angelForm.value.season);
  }
  return opts;
});

const canSaveAngel = computed(() => {
  if (
    !angelForm.value.first_name ||
    !angelForm.value.last_name ||
    !angelForm.value.recognition_name ||
    angelForm.value.donation_amount === null ||
    angelForm.value.donation_amount === "" ||
    !angelForm.value.payment_method_value ||
    !angelForm.value.season
  ) {
    return false;
  }

  if (!angelForm.value.id) {
    if (patronMode.value === "existing") return !!selectedPatron.value;
    return !!angelForm.value.email;
  }

  return true;
});

const onPatronFilter = (val, update) => {
  if (val.length < 2) {
    update(() => {
      patronSearchResults.value = [];
    });
    return;
  }

  update(async () => {
    patronSearchResults.value = await callApi({
      path: `/admin/patrons/search?q=${encodeURIComponent(val)}`,
      method: "get",
      useAuth: true,
      showError: false,
    }).catch(() => []);
  });
};

const onPatronSelected = (patron) => {
  if (!patron) return;
  angelForm.value.email = patron.email;
  angelForm.value.first_name = patron.first_name;
  angelForm.value.last_name = patron.last_name;
  originalFoundingAngel.value = !!patron.founding_angel;
  angelForm.value.founding_angel = !!patron.founding_angel;
};

const getPatron = async () => {
  if (!angelForm.value.email) return;

  const patron = await callApi({
    path: `/patrons/lookup?email=${angelForm.value.email}`,
    method: "get",
    useAuth: true,
    showError: false,
  }).catch(() => null);

  originalFoundingAngel.value = !!patron?.founding_angel;
  angelForm.value.founding_angel = !!patron?.founding_angel;

  if (!patron) return;

  angelForm.value.first_name = patron.first_name;
  angelForm.value.last_name = patron.last_name;
};

const recognitionName = computed({
  get: () => angelForm.value.recognition_name,
  set: (val) => {
    angelForm.value.recognition_name = val;
    recognitionNameEdited.value = true;
  },
});

watch(
  [() => angelForm.value.first_name, () => angelForm.value.last_name],
  () => {
    if (!recognitionNameEdited.value) {
      angelForm.value.recognition_name = `${angelForm.value.first_name || ""} ${
        angelForm.value.last_name || ""
      }`.trim();
    }
  },
);

// Switching Existing/New Patron mid-dialog shouldn't leave stale
// selections or a locked founding-angel status from the other mode behind.
// Guarded to skip edit mode — openAngelDialog() sets patronMode *before*
// populating angelForm for an existing angel, and this watcher runs on the
// next tick, after angelForm.id is already set, so it would otherwise wipe
// out the just-loaded first/last name and founding status.
watch(patronMode, () => {
  if (angelForm.value.id) return;

  selectedPatron.value = null;
  patronSearchResults.value = [];
  angelForm.value.email = "";
  angelForm.value.first_name = "";
  angelForm.value.last_name = "";
  originalFoundingAngel.value = false;
  angelForm.value.founding_angel = false;
});

onMounted(async () => {
  await loadAngelLevels();

  paymentMethods.value = await callApi({
    path: "/payment-methods",
    method: "get",
    useAuth: true,
  });

  const response = await callApi({
    path: "/active-season",
    method: "get",
    useAuth: true,
    showError: false,
  }).catch(() => null);
  activeSeason.value = response?.season ?? null;
});

const loadAngelLevels = async () => {
  const response = await callApi({
    path: "/angels",
    method: "get",
    useAuth: true,
  });

  if (response && response.levels) {
    angelLevels.value = response.levels;

    // Reselect the current level if it exists
    if (selectedLevel.value) {
      const updatedLevel = angelLevels.value.find(
        (l) => l.id === selectedLevel.value.id,
      );
      if (updatedLevel) {
        selectedLevel.value = updatedLevel;
      }
    }
  }
};

const selectLevel = (level) => {
  selectedLevel.value = level;
};

const openLink = (url) => {
  window.open(url);
};

const openLevelDialog = (level = null) => {
  if (level) {
    levelForm.value = {
      id: level.id,
      label: level.label,
      min_amount: level.min_amount,
      fixr_link: level.fixr_link ?? "",
      // Deep clone — each benefit is now {text, concession}, so a shallow
      // spread would still alias the same objects the list display reads
      // from, letting in-progress (or cancelled) edits leak into it.
      benefits: cloneDeep(level.benefits ?? []),
    };
  } else {
    levelForm.value = {
      id: null,
      label: "",
      min_amount: null,
      fixr_link: "",
      benefits: [],
    };
  }
  levelDialog.value = true;
};

const saveLevel = async () => {
  const isEdit = !!levelForm.value.id;
  const response = await callApi({
    path: isEdit ? `/angel-levels/${levelForm.value.id}` : "/angel-levels",
    method: isEdit ? "put" : "post",
    payload: levelForm.value,
    useAuth: true,
  });

  if (response && response.status === "success") {
    Notify.create({
      type: "positive",
      message: `Angel level ${isEdit ? "updated" : "created"} successfully`,
    });

    levelDialog.value = false;
    await loadAngelLevels();
  }
};

const deleteLevel = async (level) => {
  Notify.create({
    type: "warning",
    position: "center",
    message: `Are you sure you want to delete "${level.label}"? This will also delete all angels in this level.`,
    actions: [
      { label: "No" },
      {
        label: "Yes",
        handler: async () => {
          const response = await callApi({
            path: `/angel-levels/${level.id}`,
            method: "delete",
            useAuth: true,
          });

          if (response && response.status === "success") {
            Notify.create({
              type: "positive",
              message: "Angel level deleted successfully",
            });

            if (selectedLevel.value?.id === level.id) {
              selectedLevel.value = null;
            }

            await loadAngelLevels();
          }
        },
      },
    ],
  });
};

const openAngelDialog = (angel = null) => {
  patronMode.value = "existing";
  selectedPatron.value = null;
  patronSearchResults.value = [];

  if (angel) {
    angelForm.value = {
      id: angel.id,
      first_name: angel.first_name,
      last_name: angel.last_name,
      recognition_name: angel.recognition_name,
      founding_angel: !!angel.founding_angel,
      angel_level_id: angel.angel_level_id,
      email: "",
      donation_amount: angel.donation_amount,
      payment_method_value: angel.payment_method?.value ?? null,
      season: angel.season ?? currentSeasonString(),
    };
    originalFoundingAngel.value = !!angel.founding_angel;
    // Existing angels already have a (possibly custom) recognition_name —
    // don't overwrite it just because first/last name gets edited.
    recognitionNameEdited.value = true;
  } else {
    angelForm.value = {
      id: null,
      first_name: "",
      last_name: "",
      recognition_name: "",
      founding_angel: false,
      angel_level_id: selectedLevel.value.id,
      email: "",
      donation_amount: selectedLevel.value.min_amount,
      payment_method_value: null,
      season: currentSeasonString(),
    };
    originalFoundingAngel.value = false;
    recognitionNameEdited.value = false;
  }
  angelDialog.value = true;
};

const saveAngel = async () => {
  const isEdit = !!angelForm.value.id;

  const payload = {
    first_name: angelForm.value.first_name,
    last_name: angelForm.value.last_name,
    recognition_name: angelForm.value.recognition_name,
    angel_level_id: angelForm.value.angel_level_id,
    donation_amount: angelForm.value.donation_amount,
    payment_method_value: angelForm.value.payment_method_value,
    season: angelForm.value.season,
    founding_angel: angelForm.value.founding_angel,
  };
  if (!isEdit) {
    payload.email = angelForm.value.email;
  }

  const response = await callApi({
    path: isEdit ? `/angels/${angelForm.value.id}` : "/angels",
    method: isEdit ? "put" : "post",
    payload,
    useAuth: true,
  });

  if (response && response.status === "success") {
    Notify.create({
      type: "positive",
      message: `Angel ${isEdit ? "updated" : "created"} successfully`,
    });

    angelDialog.value = false;
    await loadAngelLevels();
  }
};

const openSeasonDialog = async () => {
  seasonDialog.value = true;

  if (seasons.value.length === 0) {
    const response = await callApi({
      path: "/angels/seasons",
      method: "get",
      useAuth: true,
    });

    seasons.value = response || [];
    selectedSeason.value = seasons.value[0] ?? null;
  }
};

watch(selectedSeason, async (season) => {
  seasonAngelsFilter.value = "";

  if (!season) {
    seasonAngels.value = [];
    return;
  }

  seasonAngelsLoading.value = true;
  const response = await callApi({
    path: "/angels/by-season",
    method: "get",
    payload: season,
    useAuth: true,
  });
  seasonAngels.value = response || [];
  seasonAngelsLoading.value = false;
});

const deleteAngel = async (angel) => {
  Notify.create({
    type: "warning",
    position: "center",
    message: `Are you sure you want to delete "${angel.name}"?`,
    actions: [
      { label: "No" },
      {
        label: "Yes",
        handler: async () => {
          const response = await callApi({
            path: `/angels/${angel.id}`,
            method: "delete",
            useAuth: true,
          });

          if (response && response.status === "success") {
            Notify.create({
              type: "positive",
              message: "Angel deleted successfully",
            });

            await loadAngelLevels();
          }
        },
      },
    ],
  });
};
</script>
