<template>
  <div class="q-pa-md">
    <div class="row items-center q-gutter-sm q-mb-md">
      <div class="text-h6 q-mr-md">Box Office Checklist</div>
      <q-select
        v-model="showId"
        :options="showOptions"
        emit-value
        map-options
        dense
        outlined
        label="Show"
        style="min-width: 280px;"
      >
        <template #option="scope">
          <q-item v-bind="scope.itemProps">
            <q-item-section>{{ scope.opt.label }}</q-item-section>
            <q-item-section v-if="scope.opt.overdue" side>
              <q-badge color="negative">{{ scope.opt.overdue }} overdue</q-badge>
            </q-item-section>
          </q-item>
        </template>
      </q-select>
      <q-space />
      <q-btn
        v-if="!isReadOnly"
        outline
        color="primary"
        :icon="matTune"
        label="Edit checklist"
        no-caps
        @click="openItems"
      />
    </div>

    <div v-if="loading" class="text-center q-pa-xl">
      <q-spinner size="lg" color="primary" />
    </div>

    <template v-else-if="rows.length">
      <div class="row q-gutter-sm q-mb-md">
        <q-chip v-if="counts.overdue" color="negative" text-color="white" :icon="matWarning">
          {{ counts.overdue }} overdue
        </q-chip>
        <q-chip v-if="counts.soon" color="warning" text-color="black" :icon="matSchedule">
          {{ counts.soon }} due within 24 hours
        </q-chip>
        <q-chip color="positive" text-color="white" :icon="matCheckCircle">
          {{ counts.done }} of {{ rows.length }} done
        </q-chip>
      </div>

      <q-list bordered separator class="rounded-borders">
        <q-item
          v-for="row in sortedRows"
          :key="`${row.checklist_item_id}:${row.performance_id ?? ''}`"
          :class="rowClass(row)"
        >
          <q-item-section side top>
            <q-checkbox
              :model-value="row.checked"
              :disable="isReadOnly"
              color="positive"
              @update:model-value="(value) => toggle(row, value)"
            />
          </q-item-section>
          <q-item-section>
            <q-item-label :class="{ 'text-strike text-grey-7': row.checked }">
              {{ row.label }}
              <span v-if="row.performance" class="text-grey-8"> — {{ row.performance }}</span>
              <q-badge v-if="row.removed" color="grey-6" class="q-ml-xs">removed from list</q-badge>
            </q-item-label>
            <q-item-label v-if="row.notes" caption>{{ row.notes }}</q-item-label>
            <q-item-label v-if="row.checked" caption class="text-positive">
              Done {{ row.checked_at }}<span v-if="row.checked_by"> by {{ row.checked_by }}</span>
            </q-item-label>
          </q-item-section>
          <q-item-section side top class="text-right">
            <q-badge v-if="row.overdue" color="negative" class="q-mb-xs">Overdue</q-badge>
            <q-badge v-else-if="row.due_soon" color="warning" text-color="black" class="q-mb-xs">
              Due soon
            </q-badge>
            <q-item-label caption :class="{ 'text-negative text-weight-bold': row.overdue }">
              {{ row.due_label ? `Due ${row.due_label}` : "No due date yet" }}
            </q-item-label>
            <q-item-label caption>{{ row.rule }}</q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </template>

    <div v-else-if="showId" class="text-grey-7 q-pa-lg text-center">
      Nothing on the checklist yet — add items with Edit checklist.
    </div>

    <!-- Edit the list and its alert rules -->
    <q-dialog v-model="itemsDialog">
      <q-card style="width: 760px; max-width: 95vw;">
        <q-card-section class="row items-center bg-secondary text-white">
          <div class="text-h6">Edit Checklist</div>
          <q-space />
          <q-btn :icon="matClose" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section class="text-caption text-grey-7 q-pb-none">
          Every show gets these items. An item turns red when it isn't checked
          off by its due time. "Each performance" items are a separate to-do
          for every performance. Removing an item keeps it on shows where it
          was already checked off.
        </q-card-section>
        <q-card-section>
          <q-list separator>
            <q-item v-for="(item, i) in items" :key="item.id">
              <q-item-section side>
                <div class="column">
                  <q-btn flat dense size="sm" :icon="matArrowUpward" :disable="i === 0" @click="move(i, -1)" />
                  <q-btn flat dense size="sm" :icon="matArrowDownward" :disable="i === items.length - 1" @click="move(i, 1)" />
                </div>
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ item.label }}</q-item-label>
                <q-item-label caption>Alert: {{ item.rule }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <div class="row no-wrap">
                  <q-btn flat round dense color="primary" :icon="matEdit" @click="editItem(item)" />
                  <q-btn flat round dense color="negative" :icon="matDelete" @click="removeItem(item)" />
                </div>
              </q-item-section>
            </q-item>
          </q-list>
          <q-btn class="q-mt-md" color="primary" outline :icon="matAdd" label="Add item" no-caps @click="editItem(null)" />
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="itemForm.open" persistent>
      <q-card style="width: 560px; max-width: 95vw;">
        <q-card-section>
          <div class="text-h6">{{ itemForm.id ? "Edit item" : "Add item" }}</div>
        </q-card-section>
        <q-card-section class="q-gutter-md">
          <q-input v-model="itemForm.label" label="To-do" outlined dense autofocus />
          <q-input v-model="itemForm.notes" label="Notes (optional)" type="textarea" autogrow outlined dense />
          <div>
            <div class="text-caption text-grey-7 q-mb-xs">Alert (turns red) if not done by</div>
            <div class="row q-col-gutter-sm items-center">
              <div class="col-3">
                <q-input v-model.number="itemForm.amount" type="number" min="0" outlined dense />
              </div>
              <div class="col-3">
                <q-select v-model="itemForm.unit" :options="units" emit-value map-options outlined dense />
              </div>
              <div class="col-2">
                <q-select v-model="itemForm.direction" :options="directions" emit-value map-options outlined dense />
              </div>
              <div class="col-4">
                <q-select v-model="itemForm.anchor" :options="anchorOptions" emit-value map-options outlined dense />
              </div>
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            color="primary"
            label="Save"
            :disable="!itemForm.label || itemForm.amount === '' || itemForm.amount == null"
            @click="saveItem"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import {
  matAdd,
  matArrowDownward,
  matArrowUpward,
  matCheckCircle,
  matClose,
  matDelete,
  matEdit,
  matSchedule,
  matTune,
  matWarning,
} from "@quasar/extras/material-icons";
import { Dialog, Notify } from "quasar";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import { computed, onMounted, ref, watch } from "vue";

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "box-office-checklist") === "read-only",
);

const shows = ref([]);
const showId = ref(null);
const rows = ref([]);
const loading = ref(false);

const showOptions = computed(() =>
  shows.value.map((s) => ({ label: s.name, value: s.id, overdue: s.overdue })),
);

const loadShows = async () => {
  const response = await callApi({ path: "/checklist/shows", method: "get", useAuth: true }).catch(() => null);
  shows.value = response?.shows ?? [];
  if (!showId.value) showId.value = response?.current_show_id ?? null;
};

const loadRows = async () => {
  if (!showId.value) return;
  loading.value = true;
  const response = await callApi({
    path: `/checklist/shows/${showId.value}`,
    method: "get",
    useAuth: true,
  }).catch(() => null);
  rows.value = response?.rows ?? [];
  loading.value = false;
};

watch(showId, loadRows);
onMounted(loadShows);

// Due order; items with no due date yet last.
const sortedRows = computed(() =>
  [...rows.value].sort((a, b) => (a.due_at ?? "9999").localeCompare(b.due_at ?? "9999")),
);

const counts = computed(() => ({
  overdue: rows.value.filter((r) => r.overdue).length,
  soon: rows.value.filter((r) => r.due_soon).length,
  done: rows.value.filter((r) => r.checked).length,
}));

const rowClass = (row) => {
  if (row.overdue) return "row-overdue";
  if (row.due_soon) return "row-soon";
  return "";
};

const toggle = async (row, value) => {
  const response = await callApi({
    path: `/checklist/shows/${showId.value}/check`,
    method: "put",
    payload: {
      checklist_item_id: row.checklist_item_id,
      performance_id: row.performance_id,
      checked: value,
    },
    useAuth: true,
    showError: false,
  }).catch(() => null);

  if (!response || response.status !== "success") {
    Notify.create({ type: "negative", message: response?.message || "Couldn't save that." });
    return;
  }

  Object.assign(row, response.row);
  const s = shows.value.find((x) => x.id === showId.value);
  if (s) s.overdue = counts.value.overdue;
};

// ── Editing the list ──────────────────────────────────────────────────────

const itemsDialog = ref(false);
const items = ref([]);
const anchors = ref({});

const anchorOptions = computed(() =>
  Object.entries(anchors.value).map(([value, label]) => ({ value, label })),
);
const units = [
  { label: "minutes", value: 1 },
  { label: "hours", value: 60 },
  { label: "days", value: 1440 },
];
const directions = [
  { label: "before", value: -1 },
  { label: "after", value: 1 },
];

const loadItems = async () => {
  const response = await callApi({ path: "/checklist/items", method: "get", useAuth: true }).catch(() => null);
  items.value = response?.items ?? [];
  anchors.value = response?.anchors ?? {};
};

const openItems = async () => {
  await loadItems();
  itemsDialog.value = true;
};

const itemForm = ref({ open: false });

// Splits offset_minutes into amount + unit + before/after for the form.
const editItem = (item) => {
  const minutes = item?.offset_minutes ?? -1440;
  const abs = Math.abs(minutes);
  const unit = abs % 1440 === 0 && abs ? 1440 : abs % 60 === 0 && abs ? 60 : 1;
  itemForm.value = {
    open: true,
    id: item?.id ?? null,
    label: item?.label ?? "",
    notes: item?.notes ?? "",
    anchor: item?.anchor ?? "first_performance",
    amount: abs / unit,
    unit,
    direction: minutes > 0 ? 1 : -1,
  };
};

const saveItem = async () => {
  const f = itemForm.value;
  const payload = {
    label: f.label,
    notes: f.notes || null,
    anchor: f.anchor,
    offset_minutes: Math.round(Number(f.amount) * f.unit) * f.direction,
  };

  const response = await callApi({
    path: f.id ? `/checklist/items/${f.id}` : "/checklist/items",
    method: f.id ? "put" : "post",
    payload,
    useAuth: true,
  }).catch(() => null);
  if (!response || response.status !== "success") return;

  itemForm.value = { open: false };
  await Promise.all([loadItems(), loadRows(), loadShows()]);
};

const removeItem = (item) => {
  Dialog.create({
    title: "Remove item?",
    message: `Remove "${item.label}" from the checklist? Shows where it was already checked off keep it for the record.`,
    cancel: { label: "Cancel", flat: true },
    ok: { label: "Remove", color: "negative", flat: true },
  }).onOk(async () => {
    const response = await callApi({
      path: `/checklist/items/${item.id}`,
      method: "delete",
      useAuth: true,
    }).catch(() => null);
    if (!response || response.status !== "success") return;
    await Promise.all([loadItems(), loadRows(), loadShows()]);
  });
};

const move = async (i, delta) => {
  const list = [...items.value];
  [list[i], list[i + delta]] = [list[i + delta], list[i]];
  items.value = list;
  await callApi({
    path: "/checklist/items/reorder",
    method: "put",
    payload: { ids: list.map((x) => x.id) },
    useAuth: true,
  }).catch(() => null);
};
</script>

<style scoped>
.row-overdue {
  background: #ffebee;
  border-left: 4px solid #c62828;
}
.row-soon {
  background: #fff8e1;
  border-left: 4px solid #f9a825;
}
</style>
