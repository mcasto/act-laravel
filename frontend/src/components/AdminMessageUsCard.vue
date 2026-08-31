<template>
  <div>
    <q-card class="q-ma-xs" flat bordered :class="deleteFlag ? 'bg-red-2' : ''">
      <q-card-section class="column q-gutter-y-xs">
        <div>
          <span class="text-subtitle2 text-uppercase text-grey">
            Date Sent
          </span>
          <span class="text-subtitle2 q-ml-md">
            {{ format(parseISO(submission.created_at), "PPpp") }}
          </span>
        </div>
        <div>
          <span class="text-subtitle2 text-uppercase text-grey">
            Email
          </span>
          <span class="text-subtitle2 q-ml-md">
            {{ submission.email }}
          </span>
        </div>
      </q-card-section>
      <q-card-actions class="justify-between">
        <q-btn
          :icon="matDelete"
          round
          flat
          color="negative"
          :disable="isReadOnly"
          @click="deleteSubmission"
        ></q-btn>
        <q-btn
          :icon="matMessage"
          flat
          round
          @click="showDialog = { visible: true, message: submission.body }"
        ></q-btn>
      </q-card-actions>
    </q-card>
    <admin-contact-message-dialog
      v-model="showDialog.visible"
      :message="showDialog.message"
    ></admin-contact-message-dialog>
  </div>
</template>

<script setup>
import { matDelete, matMessage } from "@quasar/extras/material-icons";
import { useStore } from "src/stores/store";
import AdminContactMessageDialog from "src/components/AdminContactMessageDialog.vue";
import { computed, ref } from "vue";
import { format, parseISO } from "date-fns";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import getPermissionLevel from "src/assets/get-permission-level";
import { remove } from "lodash-es";

const props = defineProps(["submission"]);

const store = useStore();

const isReadOnly = computed(
  () => getPermissionLevel(store.admin.user, "quick-messages") === "read-only",
);

const deleteFlag = ref(false);

const showDialog = ref({
  visible: false,
  message: "",
});

const deleteSubmission = async () => {
  deleteFlag.value = true;
  Notify.create({
    type: "warning",
    message: "Are you sure you want to delete this message?",
    actions: [
      {
        label: "No",
        handler: () => {
          deleteFlag.value = false;
        },
      },
      {
        label: "Yes",
        handler: async () => {
          const response = await callApi({
            path: `/message-us/${props.submission.id}`,
            method: "delete",
            useAuth: true,
          });

          deleteFlag.value = false;

          if (response.status == "error") {
            Notify.create({
              type: "negative",
              message: response.message,
            });

            return;
          }

          remove(
            store.admin.message_us_submissions,
            ({ id }) => id == props.submission.id,
          );
        },
      },
    ],
  });
};
</script>
