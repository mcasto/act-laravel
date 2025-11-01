<template>
  <div>
    <div
      class="bg-warning flex justify-center items-center"
      style="height: 3rem;"
    >
      In order to change a user's password, you must delete their current record
      and create a new one with the new password.
    </div>
    <q-table
      :rows="store.admin.users"
      :columns="columns"
      :pagination="{ rowsPerPage: 0 }"
      hide-bottom
      dense
    >
      <template #header-cell-name>
        <q-th class="text-left" style="height: 3rem;">
          Name
          <q-btn
            icon="add"
            round
            color="primary"
            size="xs"
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
      <template #header-cell-tools>
        <q-th class="text-left items-center flex" style="height: 3rem;">
          &nbsp;
        </q-th>
      </template>

      <template #body-cell-tools="{row}">
        <q-td class="text-right">
          <q-btn
            icon="edit"
            flat
            round
            color="primary"
            size="small"
            :disable="row.id == 1 && store.admin.user.id != 1"
            :to="`/admin/edit-user/${row.id}`"
          ></q-btn>
          <q-btn
            icon="delete"
            flat
            round
            color="primary"
            size="small"
            @click="store.deleteUser(row)"
            :disable="row.id == 1 || row.id == store.admin.user.id"
          ></q-btn>
        </q-td>
      </template>
    </q-table>

    <new-user-dialog
      v-model="createUserDialog"
      :user="createUserDialog.user"
      @create="store.createUser"
    ></new-user-dialog>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { ref } from "vue";
import NewUserDialog from "src/components/NewUserDialog.vue";

const store = useStore();

const createUserDialog = ref({ visible: false, user: null });

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
    label: "Tools",
    name: "tools",
    align: "left",
  },
];
</script>
