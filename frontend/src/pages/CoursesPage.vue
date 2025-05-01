<template>
  <div class="q-pa-md">
    <div class="row">
      <div
        class="col-12 col-sm-6 col-md-4"
        v-for="course of store.courses"
        :key="`course-${course.id}`"
      >
        <course-card :course="course"></course-card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import CourseCard from "src/components/CourseCard.vue";

const store = useStore();
</script>
