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
            icon="mdi-plus-circle"
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
        <q-td v-if="props.row.id == 1 || props.row.id == store.admin.user.id">
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
            :disable="row.id == 1 || row.id == store.admin.user.id"
            @click="changePasswordDialog = { visible: true, row }"
          ></q-btn>
        </q-td>
      </template>

      <template #body-cell-tools="{row}">
        <q-td class="text-right">
          <q-btn
            icon="delete"
            flat
            round
            color="negative"
            @click="store.deleteUser(row)"
            :disable="row.id == 1 || row.id == store.admin.user.id"
          ></q-btn>
          <q-btn
            icon="mdi-content-save"
            color="primary"
            flat
            round
            :disable="row.id == 1 || row.id == store.admin.user.id"
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
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { ref } from "vue";
import NewUserDialog from "src/components/NewUserDialog.vue";
import callApi from "src/assets/call-api";
import { cloneDeep } from "lodash-es";
import { Loading, Notify } from "quasar";
import AdminUserChangePassword from "src/components/AdminUserChangePassword.vue";

const store = useStore();

const createUserDialog = ref({ visible: false, user: null });
const changePasswordDialog = ref({
  visible: false,
  row: null,
});

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
