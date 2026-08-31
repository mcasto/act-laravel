<template>
  <div>
    <q-table
      :rows="store.admin.users"
      :columns="columns"
      :pagination="{ rowsPerPage: 0 }"
      hide-bottom
      dense
    >
      <template #header-cell-tools>
        <q-th class="text-right">
          <q-btn
            v-if="hasFullUsersAccess"
            :icon="mdiPlusCircle"
            flat
            round
            color="primary"
            class="q-ml-md"
            @click="
              createUserDialog = {
                visible: true,
                user: { name: null, password: null },
              }
            "
          ></q-btn>
        </q-th>
      </template>

      <template v-slot:body-cell="props">
        <q-td v-if="!isEditable(props.row)">
          {{ props.row[props.col.name] }}
        </q-td>
        <q-td :props="props" class="text-left" v-else>
          <q-input
            v-model="props.row[props.col.name]"
            type="text"
            dense
            outlined
          />
        </q-td>
      </template>

      <template #body-cell-password="{row}">
        <q-td class="text-center">
          <q-btn
            label="Change Password"
            color="primary"
            size="sm"
            :disable="!isEditable(row)"
            @click="changePasswordDialog = { visible: true, row }"
          ></q-btn>
        </q-td>
      </template>

      <template #body-cell-tools="{row}">
        <q-td class="text-right">
          <q-btn
            :icon="matVpnKey"
            flat
            round
            color="primary"
            @click="openPermissions(row)"
            :disable="!hasFullUsersAccess || row.is_owner || row.id === store.admin.user.id"
          >
            <q-tooltip v-if="row.is_owner">
              The owner always has full access — this can't be changed.
            </q-tooltip>
            <q-tooltip v-else-if="row.id === store.admin.user.id">
              You can't change your own permissions — have another user with
              full Users access do it.
            </q-tooltip>
          </q-btn>
          <q-btn
            :icon="matDelete"
            flat
            round
            color="negative"
            @click="store.deleteUser(row)"
            :disable="!hasFullUsersAccess || row.is_owner"
          ></q-btn>
          <q-btn
            :icon="mdiContentSave"
            color="primary"
            flat
            round
            :disable="!isEditable(row)"
            @click="onSave(row)"
          ></q-btn>
        </q-td>
      </template>
    </q-table>

    <new-user-dialog
      v-model="createUserDialog"
      :user="createUserDialog.user"
      @create="store.createUser"
    ></new-user-dialog>

    <admin-user-change-password
      v-model="changePasswordDialog.visible"
      :row="changePasswordDialog.row"
    ></admin-user-change-password>

    <q-dialog v-model="permissionsDialog.visible">
      <q-card style="min-width: 420px; max-width: 90vw;">
        <q-card-section>
          <div class="text-h6">Permissions</div>
          <div class="text-caption text-grey-7">
            {{ permissionsDialog.user?.name }} ({{ permissionsDialog.user?.email }})
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div
            v-for="section in adminSections"
            :key="section.key"
            class="row items-center q-mb-md"
          >
            <div class="col-4">{{ section.label }}</div>
            <div class="col-8">
              <q-btn-toggle
                v-model="permissionsDialog.values[section.key]"
                :options="section.key === 'users' ? usersAccessOptions : accessOptions"
                dense
                no-caps
                spread
                toggle-color="primary"
              />
            </div>
            <div v-if="section.key === 'users'" class="col-12 text-caption text-grey-7 q-mt-xs">
              Read Only: can edit their own name/email/password only. Full:
              can add, edit, and delete any user except the owner.
            </div>
          </div>

          <div v-if="adminSections.length === 0" class="text-grey text-caption">
            No admin sections found.
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            @click="savePermissions"
            :loading="permissionsDialog.saving"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { matDelete, matVpnKey } from "@quasar/extras/material-icons";
import { mdiContentSave, mdiPlusCircle } from "@quasar/extras/mdi-v7";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";
import NewUserDialog from "src/components/NewUserDialog.vue";
import callApi from "src/assets/call-api";
import { cloneDeep } from "lodash-es";
import { Loading, Notify } from "quasar";
import AdminUserChangePassword from "src/components/AdminUserChangePassword.vue";
import getAdminSections from "src/assets/admin-sections";
import getPermissionLevel from "src/assets/get-permission-level";

const store = useStore();

const createUserDialog = ref({ visible: false, user: null });
const changePasswordDialog = ref({
  visible: false,
  row: null,
});

const adminSections = getAdminSections(store.router);

const accessOptions = [
  { label: "Full", value: "full" },
  { label: "Read Only", value: "read-only" },
  { label: "None", value: "none" },
];

// Users has no "None" — it's always at least self-editable (see
// UserController::canManageTarget), so only Full/Read Only make sense.
const usersAccessOptions = [
  { label: "Full", value: "full" },
  { label: "Read Only", value: "read-only" },
];

// Can this viewer add/edit/delete *other* users? Mirrors
// UserController::hasFullUsersAccess — owner, or granted 'full' on 'users'.
const hasFullUsersAccess = computed(
  () => getPermissionLevel(store.admin.user, "users") === "full",
);

const permissionsDialog = ref({
  visible: false,
  user: null,
  values: {},
  saving: false,
});

const openPermissions = (row) => {
  const values = {};
  for (const section of adminSections) {
    const existing = row.permissions?.find((p) => p.section === section.key);
    // Users defaults to read-only (self-edit), never "none" — see
    // usersAccessOptions above.
    const fallback = section.key === "users" ? "read-only" : "none";
    values[section.key] = existing?.access ?? fallback;
  }
  permissionsDialog.value = { visible: true, user: row, values, saving: false };
};

const savePermissions = async () => {
  permissionsDialog.value.saving = true;

  const response = await callApi({
    path: `/users/${permissionsDialog.value.user.id}/permissions`,
    method: "put",
    payload: { permissions: permissionsDialog.value.values },
    useAuth: true,
  });

  permissionsDialog.value.saving = false;

  if (!response || response.status !== "success") {
    Notify.create({
      type: "negative",
      message: response?.message || "Something went wrong.",
    });
    return;
  }

  // Update the row in place so the change is reflected without a full reload.
  permissionsDialog.value.user.permissions = response.permissions;

  Notify.create({ type: "positive", message: "Permissions updated." });
  permissionsDialog.value.visible = false;
};

const columns = [
  {
    label: "Name",
    name: "name",
    field: "name",
    align: "left",
  },
  {
    label: "Email",
    name: "email",
    field: "email",
    align: "left",
  },
  {
    label: "Password",
    name: "password",
    align: "center",
  },
  {
    label: "Tools",
    name: "tools",
    align: "left",
  },
];

// Full access on Users can edit anyone except the owner; everyone else can
// only edit their own entry (see UserController::canManageTarget, which
// enforces this server-side too).
const isEditable = (row) =>
  row.id === store.admin.user.id || (hasFullUsersAccess.value && !row.is_owner);

const onSave = async (row) => {
  Loading.show({ delay: 300 });

  const response = await callApi({
    path: `/users/${row.id}`,
    method: "put",
    payload: cloneDeep(row),
    useAuth: true,
  });

  if (response.status != "success") {
    Loading.hide();
    Notify.create({ type: "negative", message: response.message });
    return;
  }

  Notify.create({ type: "positive", message: "User updated" });
  Loading.hide();
};
</script>
