<template>
  <q-card flat bordered class="overflow-hidden">
    <div class="section-band bg-secondary text-white q-py-sm q-px-md">
      <q-icon :name="matSchool" />
      <div class="text-subtitle1 text-weight-bold">Your Instructor</div>
    </div>

    <div class="row q-col-gutter-md q-pa-md items-center">
      <div class="col-12 col-sm-4 text-center">
        <q-img
          :src="`/api/storage/images/${course.instructor_photo}`"
          ratio="1"
          fit="cover"
          class="instructor-photo rounded-borders shadow-2"
        />
      </div>
      <div class="col-12 col-sm-8">
        <div class="text-overline text-primary">Instructor</div>
        <div class="text-h6 text-weight-bold q-mb-sm">
          {{ course.instructor_name }}
        </div>
        <div v-html="course.instructor_info" class="text-body2" />
      </div>
    </div>
  </q-card>
</template>

<script setup>
import { matSchool } from "@quasar/extras/material-icons";

const props = defineProps(["course"]);
</script>

<style scoped>
.instructor-photo {
  max-width: 180px;
  margin: 0 auto;
}
</style>
