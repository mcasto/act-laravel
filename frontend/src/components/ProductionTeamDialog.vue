<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" @show="load">
    <q-card style="width: 980px; max-width: 95vw;">
      <q-card-section class="row items-center bg-secondary text-white">
        <div class="text-h6">Production Team Access</div>
        <q-space />
        <q-btn :icon="matClose" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section class="text-caption text-grey-7 q-pb-none">
        These people can see {{ show?.name }} on the director page
        ({{ directorUrl }}) — seats
        reserved, sold-out status and the audition notice — between their start
        and end dates. Leave a date blank for no limit. Every role gets the same
        access; the role is just a label.
      </q-card-section>

      <q-card-section v-if="loading" class="text-center q-py-xl">
        <q-spinner size="2em" />
      </q-card-section>

      <q-card-section v-else>
        <div v-if="rows.length === 0" class="text-grey text-center q-py-md">
          No one has access to this show yet.
        </div>

        <div
          v-for="row in rows"
          :key="row.key"
          class="row q-col-gutter-sm items-center q-mb-sm"
          :class="{ 'text-grey-6': row.status === 'ended' }"
        >
          <div class="col-12 col-sm-3">
            <q-select
              v-if="row.id === null"
              v-model="row.user_id"
              :options="userOptions(row)"
              option-value="id"
              option-label="name"
              emit-value
              map-options
              label="Person"
              outlined
              dense
              :disable="isReadOnly"
            >
              <template #option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.name }}</q-item-label>
                    <q-item-label caption>{{ scope.opt.email }}</q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
            <template v-else>
              <div class="text-weight-medium">{{ row.user_name }}</div>
              <div class="text-caption text-grey-7">{{ row.user_email }}</div>
            </template>
          </div>

          <div class="col-12 col-sm-3">
            <q-select
              v-model="row.role"
              :options="roleOptions"
              label="Role"
              outlined
              dense
              use-input
              fill-input
              hide-selected
              input-debounce="0"
              new-value-mode="add"
              @input-value="(v) => (row.role = v || null)"
              :disable="isReadOnly"
            />
          </div>

          <div class="col-6 col-sm-2">
            <date-field v-model="row.starts_at" label="Start" :disable="isReadOnly" />
          </div>
          <div class="col-6 col-sm-2">
            <date-field v-model="row.ends_at" label="End" :disable="isReadOnly" />
          </div>

          <div class="col-12 col-sm-2 row items-center justify-end no-wrap">
            <q-badge
              v-if="row.id !== null"
              :color="statusColor[row.status]"
              :label="row.status"
              class="q-mr-xs"
            />
            <q-btn
              :icon="mdiContentSave"
              flat
              round
              dense
              color="primary"
              :disable="isReadOnly || !isDirty(row) || (row.id === null && !row.user_id)"
              :loading="row.saving"
              @click="save(row)"
            >
              <q-tooltip>Save</q-tooltip>
            </q-btn>
            <q-btn
              v-if="row.id === null"
              :icon="matClose"
              flat
              round
              dense
              @click="rows.splice(rows.indexOf(row), 1)"
            >
              <q-tooltip>Discard</q-tooltip>
            </q-btn>
            <q-btn
              v-else-if="row.status !== 'ended'"
              :icon="matPersonRemove"
              flat
              round
              dense
              color="negative"
              :disable="isReadOnly"
              @click="remove(row)"
            >
              <q-tooltip>
                {{ row.status === 'upcoming' ? 'Remove' : 'End access now' }}
              </q-tooltip>
            </q-btn>
          </div>
        </div>
      </q-card-section>

      <q-card-actions v-if="!loading" align="left" class="q-px-md q-pb-md">
        <q-btn
          :icon="matPersonAdd"
          label="Add person"
          color="primary"
          outline
          no-caps
          :disable="isReadOnly"
          @click="addRow"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { matClose, matPersonAdd, matPersonRemove } from "@quasar/extras/material-icons";
import { mdiContentSave } from "@quasar/extras/mdi-v7";
import { Dialog, Notify } from "quasar";
import { computed, ref } from "vue";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { useStore } from "src/stores/store";
import DateField from "src/components/DateField.vue";

const props = defineProps({
  modelValue: Boolean,
  show: Object,
});
const emit = defineEmits(["update:modelValue"]);

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "shows") === "read-only",
);

const directorUrl = `${window.location.host}/director`;

const roleOptions = ["Director", "Assistant Director", "Stage Manager"];
const statusColor = { active: "positive", upcoming: "info", ended: "grey" };

const loading = ref(false);
const rows = ref([]);
const users = ref([]);
const defaultWindow = ref({ starts_at: null, ends_at: null });

let nextKey = 0;
const toRow = (grant) => ({
  ...grant,
  key: `g${grant.id}`,
  saving: false,
  original: { role: grant.role, starts_at: grant.starts_at, ends_at: grant.ends_at },
});

const isDirty = (row) =>
  row.id === null ||
  row.role !== row.original.role ||
  row.starts_at !== row.original.starts_at ||
  row.ends_at !== row.original.ends_at;

// A person can only have one row per show (ended rows included — re-open
// those by editing their dates), so hide anyone already listed.
const userOptions = (row) => {
  const taken = new Set(rows.value.filter((r) => r !== row && r.user_id).map((r) => r.user_id));
  return users.value
    .filter((u) => !taken.has(u.id))
    .map((u) => ({ id: u.id, name: u.name, email: u.email }));
};

const errorMessage = (error) =>
  error?.json?.message || error?.message || "Something went wrong.";

const replaceSaved = (grants) => {
  const unsaved = rows.value.filter((r) => r.id === null);
  rows.value = [...grants.map(toRow), ...unsaved];
};

const load = async () => {
  if (!props.show?.id) return;
  loading.value = true;
  try {
    const response = await callApi({
      path: `/shows/${props.show.id}/access-grants`,
      method: "get",
      useAuth: true,
    });
    users.value = response.users;
    defaultWindow.value = response.default_window;
    rows.value = response.grants.map(toRow);
  } catch (error) {
    Notify.create({ type: "negative", message: errorMessage(error) });
  } finally {
    loading.value = false;
  }
};

// Dates are pre-filled from the show's default window but are the row's own
// values from here on — whatever is saved is what's enforced.
const addRow = () => {
  rows.value.push({
    id: null,
    key: `new${nextKey++}`,
    user_id: null,
    role: null,
    starts_at: defaultWindow.value.starts_at,
    ends_at: defaultWindow.value.ends_at,
    saving: false,
  });
};

const payloadFor = (row) => ({
  role: row.role || null,
  starts_at: row.starts_at || null,
  ends_at: row.ends_at || null,
});

const save = async (row) => {
  row.saving = true;
  try {
    const isNew = row.id === null;
    const response = await callApi({
      path: isNew
        ? `/shows/${props.show.id}/access-grants`
        : `/shows/${props.show.id}/access-grants/${row.id}`,
      method: isNew ? "post" : "put",
      payload: isNew ? { user_id: row.user_id, ...payloadFor(row) } : payloadFor(row),
      useAuth: true,
    });
    if (!response) return;
    if (isNew) rows.value.splice(rows.value.indexOf(row), 1);
    replaceSaved(response.grants);
    Notify.create({ type: "positive", message: "Access saved" });
  } catch (error) {
    Notify.create({ type: "negative", message: errorMessage(error) });
  } finally {
    row.saving = false;
  }
};

const remove = (row) => {
  const upcoming = row.status === "upcoming";
  Dialog.create({
    title: upcoming ? "Remove access?" : "End access now?",
    message: upcoming
      ? `${row.user_name}'s access hasn't started yet, so it will be removed.`
      : `${row.user_name} will lose access to ${props.show.name} on the director page right away. The record is kept, and you can re-open it later by changing the end date.`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const response = await callApi({
        path: `/shows/${props.show.id}/access-grants/${row.id}`,
        method: "delete",
        useAuth: true,
      });
      if (!response) return;
      replaceSaved(response.grants);
      Notify.create({
        type: "positive",
        message: response.outcome === "deleted" ? "Access removed" : "Access ended",
      });
    } catch (error) {
      Notify.create({ type: "negative", message: errorMessage(error) });
    }
  });
};
</script>
