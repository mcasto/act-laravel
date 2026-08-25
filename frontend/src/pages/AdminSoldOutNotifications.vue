<template>
  <div class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h6">Sold Out Notifications</div>
        <div class="text-caption text-grey-7">
          Everyone on this list gets emailed when a performance is marked sold out.
        </div>
      </div>
      <q-btn
        color="primary"
        :icon="matAdd"
        label="Add Recipient"
        @click="openDialog()"
      />
    </div>

    <q-table
      :rows="recipients"
      :columns="columns"
      row-key="id"
      dense
      :pagination="{ rowsPerPage: 0 }"
      hide-bottom
    >
      <template #body-cell-actions="props">
        <q-td :props="props" class="text-right">
          <q-btn
            :icon="matEdit"
            flat
            round
            dense
            size="sm"
            color="primary"
            @click="openDialog(props.row)"
          />
          <q-btn
            :icon="matDelete"
            flat
            round
            dense
            size="sm"
            color="negative"
            @click="deleteRecipient(props.row)"
          />
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialog" persistent>
      <q-card style="min-width: 350px;">
        <q-card-section>
          <div class="text-h6">
            {{ form.id ? "Edit" : "Add" }} Recipient
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
            v-model="form.name"
            label="Name"
            outlined
            dense
            class="q-mb-md"
          />
          <q-input
            v-model="form.email"
            type="email"
            label="Email"
            outlined
            dense
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn
            flat
            label="Save"
            color="primary"
            @click="save"
            :disable="!form.name || !form.email"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { matAdd, matDelete, matEdit } from "@quasar/extras/material-icons";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { ref } from "vue";

const store = useStore();

const recipients = ref(store.admin.sold_out_notification_recipients ?? []);

const dialog = ref(false);
const form = ref({ id: null, name: "", email: "" });

const columns = [
  { name: "name", label: "Name", field: "name", align: "left" },
  { name: "email", label: "Email", field: "email", align: "left" },
  { name: "actions", label: "", field: "", align: "right" },
];

const openDialog = (recipient = null) => {
  form.value = recipient
    ? { id: recipient.id, name: recipient.name, email: recipient.email }
    : { id: null, name: "", email: "" };
  dialog.value = true;
};

const reload = async () => {
  recipients.value = await callApi({
    path: "/sold-out-notification-recipients",
    method: "get",
    useAuth: true,
  });
  store.admin.sold_out_notification_recipients = recipients.value;
};

const save = async () => {
  const isEdit = !!form.value.id;
  const response = await callApi({
    path: isEdit
      ? `/sold-out-notification-recipients/${form.value.id}`
      : "/sold-out-notification-recipients",
    method: isEdit ? "put" : "post",
    payload: form.value,
    useAuth: true,
  });

  if (!response || response.status !== "success") {
    Notify.create({
      type: "negative",
      message: response?.message || "Something went wrong.",
    });
    return;
  }

  Notify.create({
    type: "positive",
    message: `Recipient ${isEdit ? "updated" : "added"}.`,
  });
  dialog.value = false;
  await reload();
};

const deleteRecipient = (recipient) => {
  Notify.create({
    type: "warning",
    position: "center",
    message: `Remove "${recipient.name}" from sold-out notifications?`,
    actions: [
      { label: "No" },
      {
        label: "Yes",
        handler: async () => {
          const response = await callApi({
            path: `/sold-out-notification-recipients/${recipient.id}`,
            method: "delete",
            useAuth: true,
          });

          if (!response || response.status !== "success") {
            Notify.create({
              type: "negative",
              message: response?.message || "Delete failed.",
            });
            return;
          }

          await reload();
        },
      },
    ],
  });
};
</script>
