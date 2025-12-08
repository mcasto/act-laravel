<template>
  <div>
    <q-table
      :rows="store.admin.courses"
      :columns="columns"
      flat
      bordered
      :pagination="{ rowsPerPage: 0 }"
      :hide-bottom="store.admin.courses.length > 0"
    >
      <template #header-cell-actions>
        <q-th align="right">
          <q-btn
            color="primary"
            icon="add"
            round
            size="sm"
            to="/admin/edit-course/new"
          />
        </q-th>
      </template>
      <template #body-cell-contacts="props">
        <q-td align="center">
          {{ props.row.contacts.length }}
        </q-td>
      </template>
      <template #body-cell-actions="props">
        <q-td align="right">
          <q-btn color="negative" icon="delete" round flat />
          <q-btn color="primary" icon="edit" round flat />
        </q-td>
      </template>
    </q-table>
  </div>
</template>

<script setup>
import { format } from "date-fns";
import { useStore } from "src/stores/store";
import { ref } from "vue";

const store = useStore();

const columns = [
  { name: "name", label: "Class Name", field: "name", align: "left" },
  {
    name: "instructor_name",
    label: "Instructor",
    field: "instructor_name",
    align: "left",
  },
  {
    name: "enrollment_start",
    label: "Enrollment Start",
    field: (row) => format(new Date(row.enrollment_start), "PP"),
    align: "center",
  },
  {
    name: "enrollment_end",
    label: "Enrollment End",
    field: (row) => format(new Date(row.enrollment_end), "PP"),
    align: "center",
  },
  {
    name: "contacts",
    label: "Contacts",
    align: "center",
  },
  {
    name: "actions",
    field: "actions",
    align: "right",
  },
];

const editing = ref(null);

console.log(store.admin.courses);
</script>
