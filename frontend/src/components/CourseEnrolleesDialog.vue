<template>
  <q-dialog v-model="model" full-width>
    <q-card>
      <q-card-actions class="justify-between">
        Enrollees
        <q-btn :icon="matClose" round flat @click="model = false" size="sm"></q-btn>
      </q-card-actions>
      <q-card-section>
        <q-table
          dense
          flat
          bordered
          :rows="contacts"
          :columns="columns"
          hide-bottom
        >
          <template #body-cell-email="props">
            <q-td class="text-left">
              {{ props.value }}
            </q-td>
          </template>

          <template #body-cell-questions="props">
            <q-td class="text-center">
              <div v-if="!!!props.row.questions">
                N/A
              </div>
              <div v-else>
                <q-btn
                  size="sm"
                  color="primary"
                  label="View"
                  @click="openQuestions(props.row.questions)"
                ></q-btn>
              </div>
            </q-td>
          </template>

          <template #body-cell-payment_method="props">
            <q-td class="text-left">
              {{ props.row.payment_method?.label ?? "Free" }}
            </q-td>
          </template>

          <template #body-cell-transfer_date="props">
            <q-td class="text-center">
              {{ props.row.transfer_date ?? "—" }}
            </q-td>
          </template>

          <template #body-cell-confirmed="props">
            <q-td class="text-center">
              <q-toggle
                :model-value="!!props.row.confirmed"
                :disable="isReadOnly"
                @update:model-value="(val) => updateConfirmed(props.row, val)"
              ></q-toggle>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>
    <EnrolleeQuestionsDialog
      v-model="enrolleeQuestions.visible"
      :questions="enrolleeQuestions.questions"
    ></EnrolleeQuestionsDialog>
  </q-dialog>
</template>

<script setup>
import { matClose } from "@quasar/extras/material-icons";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { ref } from "vue";
import EnrolleeQuestionsDialog from "./EnrolleeQuestionsDialog.vue";

const model = defineModel();
const props = defineProps(["contacts", "isReadOnly"]);

const enrolleeQuestions = ref({
  visible: false,
  questions: null,
});

const columns = [
  {
    label: "Name",
    name: "name",
    field: (row) => `${row.first_name} ${row.last_name}`,
    align: "left",
  },
  {
    label: "Email",
    name: "email",
    field: "email",
    align: "left",
  },
  {
    label: "Phone",
    name: "phone",
    field: "phone",
    align: "left",
  },
  {
    label: "Questions",
    name: "questions",
    field: "questions",
    align: "center",
  },
  {
    label: "Payment Method",
    name: "payment_method",
    field: (row) => row.payment_method?.label ?? "Free",
    align: "left",
  },
  {
    label: "Transfer Date",
    name: "transfer_date",
    field: "transfer_date",
    align: "center",
  },
  {
    label: "Confirmed",
    name: "confirmed",
    field: "confirmed",
    align: "center",
  },
];

const openQuestions = (questions) => {
  enrolleeQuestions.value = {
    visible: true,
    questions,
  };
};

const updateConfirmed = async (row, confirmed) => {
  const response = await callApi({
    path: `/admin/course-contacts/${row.id}/confirmed`,
    method: "put",
    payload: { confirmed },
    useAuth: true,
  });

  if (!response || response.status !== "success") {
    Notify.create({ type: "negative", message: "Failed to update confirmation status." });
    return;
  }

  row.confirmed = response.confirmed;
};
</script>
