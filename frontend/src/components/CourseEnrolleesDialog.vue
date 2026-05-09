<template>
  <q-dialog v-model="model" full-width>
    <q-card>
      <q-card-actions class="justify-between">
        Enrollees
        <q-btn icon="close" round flat @click="model = false" size="sm"></q-btn>
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
import { ref } from "vue";
import EnrolleeQuestionsDialog from "./EnrolleeQuestionsDialog.vue";

const model = defineModel();
const props = defineProps(["contacts"]);

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
];

const openQuestions = (questions) => {
  enrolleeQuestions.value = {
    visible: true,
    questions,
  };
};
</script>
