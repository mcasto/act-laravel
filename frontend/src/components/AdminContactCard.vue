<template>
  <div>
    <q-card class="q-ma-xs" flat bordered :class="deleteFlag ? 'bg-red-2' : ''">
      <q-card-section class="column q-gutter-y-xs">
        <div>
          <span class="text-subtitle2 text-uppercase text-grey">
            Date Sent
          </span>
          <span class="text-subtitle2 q-ml-md">
            {{ format(parseISO(contact.created_at), "PPpp") }}
          </span>
        </div>
        <div>
          <span class="text-subtitle2 text-uppercase text-grey">
            Name
          </span>
          <span class="text-subtitle2 q-ml-md">
            {{ contact.name }}
          </span>
        </div>
        <div>
          <span class="text-subtitle2 text-uppercase text-grey">
            Email
          </span>
          <span class="text-subtitle2 q-ml-md">
            {{ contact.email }}
          </span>
        </div>
        <div>
          <span class="text-subtitle2 text-uppercase text-grey">
            Phone
          </span>
          <span class="text-subtitle2 q-ml-md">
            {{ contact.phone }}
          </span>
        </div>
        <div>
          <span class="text-subtitle2 text-uppercase text-grey">
            Subject
          </span>
          <span class="text-subtitle2 q-ml-md">
            {{ contact.subject }}
          </span>
        </div>
      </q-card-section>
      <q-card-actions class="justify-between">
        <q-btn
          icon="delete"
          round
          flat
          color="negative"
          @click="deleteContact"
        ></q-btn>
        <q-btn
          icon="message"
          flat
          round
          @click="showDialog = { visible: true, message: contact.body }"
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
import { useStore } from "src/stores/store";
import AdminContactMessageDialog from "src/components/AdminContactMessageDialog.vue";
import { ref } from "vue";
import { format, parseISO } from "date-fns";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { remove } from "lodash-es";

const props = defineProps(["contact"]);

const store = useStore();

const deleteFlag = ref(false);

const showDialog = ref({
  visible: false,
  message: "",
});

const deleteContact = async () => {
  deleteFlag.value = true;
  Notify.create({
    type: "warning",
    message: "Are you sure you want to delete this contact?",
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
            path: `/contacts/${props.contact.id}`,
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

          remove(store.admin.contacts, ({ id }) => id == props.contact.id);
        },
      },
    ],
  });
};
</script>
