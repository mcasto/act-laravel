<template>
  <div>
    <q-toolbar>
      <q-toolbar-title>
        Comp Tickets for {{ store.admin.show.name }}
      </q-toolbar-title>
    </q-toolbar>

    <q-separator></q-separator>

    <div class="row">
      <div class="col-5 q-mt-sm">
        <q-card flat>
          <q-form
            ref="formRef"
            @submit.prevent="onSubmit"
            class="column q-gutter-y-sm"
          >
            <q-input
              dense
              outlined
              stack-label
              type="text"
              label="Name"
              :rules="[(val) => !!val || 'Name is required']"
              v-model="form.name"
              @blur="form.pickup_name = form.name"
            ></q-input>

            <q-input
              dense
              outlined
              stack-label
              type="email"
              label="Email"
              :rules="[(val) => !!val || 'Email is required']"
              v-model="form.email"
            ></q-input>

            <q-card-actions class="justify-end">
              <q-btn
                type="submit"
                label="Add"
                color="primary"
                :loading="loading"
              ></q-btn>
            </q-card-actions>
          </q-form>
        </q-card>
      </div>
      <div class="col q-ml-sm q-mt-sm">
        <q-table
          dense
          bordered
          flat
          :columns="columns"
          :rows="store.admin.compList"
          :pagination="{ rowsPerPage: 0 }"
          :hide-bottom="store.admin.compList.length > 0"
        >
          <template #header-cell-tools>
            <q-th>
              <q-btn
                label="Send Bulk"
                color="primary"
                @click="sendBulk"
              ></q-btn>
            </q-th>
          </template>
          <template #body-cell-tools="{row}">
            <q-td class="text-center">
              <q-btn
                icon="delete"
                round
                size="sm"
                color="negative"
                @click="onDelete(row)"
              ></q-btn>
              <q-btn
                icon="send"
                size="sm"
                round
                color="positive"
                class="q-ml-sm"
                :disabled="row.sent_at"
                @click="onSend(row)"
              ></q-btn>
            </q-td>
          </template>
        </q-table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { remove } from "lodash-es";
import { Dialog, Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const store = useStore();

const loading = ref(false);
const formRef = ref(null);

const columns = [
  {
    label: "Name",
    name: "name",
    field: "name",
  },
  {
    label: "Sent",
    name: "sent_at",
    field: (row) => (row.sent_at ? format(parseISO(row.sent_at), "PP") : "N/A"),
  },
  {
    label: "Redeemed",
    name: "redeemed_at",
    field: (row) =>
      row.redeemed_at ? format(parseISO(row.redeemed_at), "PP") : "N/A",
  },
  {
    name: "tools",
  },
];

const form = ref({
  name: null,
  email: null,
});

const onDelete = async (row) => {
  Dialog.create({
    title: "Remove Comp Ticket Assignment",
    html: true,
    message: `Are you sure you want to remove the assignment for <br /><br /> ${row.name} &lt;${row.email}&gt;`,
    ok: "Yes",
    cancel: "No",
  }).onOk(async () => {
    if (!row.sent_at) {
      remove(store.admin.compList, ({ email }) => email == row.email);

      return;
    } else {
      const response = await callApi({
        path: `/comp/${row.uid}`,
        method: "delete",
        useAuth: true,
      });

      remove(store.admin.compList, ({ id }) => id == response.id);
    }
  });
};

const onSubmit = async () => {
  loading.value = true;
  store.admin.comp = form.value;
  const response = await store.saveCompConfig();
  loading.value = false;

  if (response.status === "success") {
    Notify.create({
      color: "positive",
      message: "Comp Ticket Notification Sent",
    });

    form.value = { name: null, email: null };
    formRef.value.reset();

    store.admin.compList = response.list;
  }
};

const onSend = async (row) => {
  const response = await callApi({
    path: `/comp/send/${row.uid}`,
    method: "post",
    useAuth: true,
  });

  store.admin.compList = response.list;
};

const sendBulk = async () => {
  const rows = store.admin.compList.filter((rec) => !rec.sent_at);

  for (const row of rows) {
    await onSend(row);
  }
};
</script>
