<template>
  <div>
    <q-table :rows="store.admin.courses" :columns="columns"></q-table>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";

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
    field: "enrollment_start",
    align: "left",
  },
  {
    name: "enrollment_end",
    label: "Enrollment End",
    field: "enrollment_end",
    align: "left",
  },
  {
    name: "num_sessions",
    label: "Number of Sessions",
    field: (row) => row.sessions.length,
  },
];

console.log(store.admin.courses);
</script>
