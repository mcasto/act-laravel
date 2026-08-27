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
                    @click.stop="openLevelDialog(level)"
                  />
                  <q-btn
                    :icon="matDelete"
                    flat
                    dense
                    size="sm"
                    color="negative"
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
            <div class="row justify-between items-center q-mb-md">
              <div class="text-h6">{{ selectedLevel.label }} Angels</div>
              <q-btn
                color="primary"
                :icon="matAdd"
                label="Add Angel"
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
                      @click="openAngelDialog(angel)"
                    />
                    <q-btn
                      :icon="matDelete"
                      flat
                      dense
                      size="sm"
                      color="negative"
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
              v-model="levelForm.benefits[index]"
              dense
              outlined
              class="col"
            />
            <q-btn
              :icon="matDelete"
              flat
              dense
              round
              size="sm"
              color="negative"
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
            @click="levelForm.benefits.push('')"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            @click="saveLevel"
            :disable="!levelForm.label || !levelForm.min_amount"
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
          <q-checkbox
            v-model="angelForm.founding_angel"
            label="Founding Angel"
            :true-value="1"
            :false-value="0"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            @click="saveAngel"
            :disabled="
              !angelForm.first_name ||
              !angelForm.last_name ||
              !angelForm.recognition_name
            "
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
          </div>

          <q-table
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
  matHistoryEdu,
  matLink,
  matSearch,
} from "@quasar/extras/material-icons";
import { computed, onMounted, ref, watch } from "vue";
import { Notify } from "quasar";
import { format, parseISO } from "date-fns";
import callApi from "src/assets/call-api";

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
});

// Keeps recognition_name in sync with first/last name until the admin
// deliberately edits that field directly (or it's an existing angel).
const recognitionNameEdited = ref(false);

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

onMounted(async () => {
  await loadAngelLevels();
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
      benefits: [...(level.benefits ?? [])],
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
  if (angel) {
    angelForm.value = {
      id: angel.id,
      first_name: angel.first_name,
      last_name: angel.last_name,
      recognition_name: angel.recognition_name,
      founding_angel: angel.founding_angel,
      angel_level_id: angel.angel_level_id,
    };
    // Existing angels already have a (possibly custom) recognition_name —
    // don't overwrite it just because first/last name gets edited.
    recognitionNameEdited.value = true;
  } else {
    angelForm.value = {
      id: null,
      first_name: "",
      last_name: "",
      recognition_name: "",
      founding_angel: 0,
      angel_level_id: selectedLevel.value.id,
    };
    recognitionNameEdited.value = false;
  }
  angelDialog.value = true;
};

const saveAngel = async () => {
  const isEdit = !!angelForm.value.id;
  const response = await callApi({
    path: isEdit ? `/angels/${angelForm.value.id}` : "/angels",
    method: isEdit ? "put" : "post",
    payload: angelForm.value,
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
