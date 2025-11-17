<template>
  <div>
    <q-splitter :model-value="30">
      <template #before>
        <q-page class="q-pa-md">
          <div class="text-h6 q-mb-md">Angel Levels</div>

          <div class="q-mb-md">
            <q-btn
              color="primary"
              icon="add"
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
                    icon="edit"
                    flat
                    dense
                    size="sm"
                    @click.stop="openLevelDialog(level)"
                  />
                  <q-btn
                    icon="delete"
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
                icon="add"
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
                  <q-item-label>{{ angel.name }}</q-item-label>
                  <q-item-label caption v-if="angel.founding_angel">
                    <q-badge color="amber" text-color="black"
                      >Founding Angel</q-badge
                    >
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="row q-gutter-x-xs">
                    <q-btn
                      icon="edit"
                      flat
                      dense
                      size="sm"
                      @click="openAngelDialog(angel)"
                    />
                    <q-btn
                      icon="delete"
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
    <q-dialog v-model="levelDialog" persistent>
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
            v-model="angelForm.name"
            label="Name"
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
            :disable="!angelForm.name"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";

const angelLevels = ref([]);
const selectedLevel = ref(null);
const levelDialog = ref(false);
const angelDialog = ref(false);

const levelForm = ref({
  id: null,
  label: "",
  min_amount: null,
});

const angelForm = ref({
  id: null,
  name: "",
  founding_angel: false,
  angel_level_id: null,
});

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
        (l) => l.id === selectedLevel.value.id
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

const openLevelDialog = (level = null) => {
  if (level) {
    levelForm.value = {
      id: level.id,
      label: level.label,
      min_amount: level.min_amount,
    };
  } else {
    levelForm.value = {
      id: null,
      label: "",
      min_amount: null,
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
      name: angel.name,
      founding_angel: angel.founding_angel,
      angel_level_id: angel.angel_level_id,
    };
  } else {
    angelForm.value = {
      id: null,
      name: "",
      founding_angel: 0,
      angel_level_id: selectedLevel.value.id,
    };
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
