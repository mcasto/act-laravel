<template>
  <!-- Instructor section -->
  <div class="row q-my-md q-col-gutter-md items-start">
    <div class="col-12 col-sm-4">
      <q-img
        :src="`/api/storage/images/${course.instructor_photo}`"
        class="rounded-borders"
        style="width: 100%;"
      />
    </div>
    <div class="col-12 col-sm-8">
      <div class="text-subtitle1 text-weight-bold">
        Instructor: {{ course.instructor_name }}
      </div>
      <div v-html="course.instructor_info" class="text-body2 q-mt-sm" />
    </div>
  </div>
</template>

<script setup>
const props = defineProps(["course"]);
</script>
