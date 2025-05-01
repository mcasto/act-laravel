<template>
  <!-- Course title -->
  <div class="text-h5 text-primary text-center">
    {{ course.name }}
  </div>

  <div class="row">
    <div class="col-12 col-sm-6">
      <!-- Poster image with tagline overlay -->
      <q-img
        :src="`/api/storage/images/${course.poster}`"
        class="rounded-borders"
      >
        <div
          class="absolute-bottom text-white text-h6 text-center bg-black bg-opacity-60 q-pa-sm"
        >
          {{ course.tagline }}
        </div>
      </q-img>
    </div>
    <div class="col-12 col-sm-6 q-pl-md">
      <div class="q-my-md q-gutter-y-sm">
        <div class="q-gutter-sm flex items-center no-wrap">
          <q-icon name="place" color="primary" />
          <span class="text-body1">{{ course.location }}</span>
        </div>
        <div class="q-gutter-sm flex items-center">
          <q-icon name="event" color="primary" />
          <span class="text-body1">
            Enrollment: {{ formatDate(course.enrollment_start) }} –
            {{ formatDate(course.enrollment_end) }}
          </span>
        </div>
        <div class="q-gutter-sm flex items-center">
          <q-icon name="payments" color="primary" />
          <span class="text-body1">${{ course.cost }}</span>
        </div>
      </div>

      <!-- Sessions -->
      <q-separator />

      <div class="q-mt-md">
        <div class="text-subtitle1 text-weight-bold q-mb-sm">
          Class Sessions
        </div>
        <q-list dense bordered class="rounded-borders">
          <q-item v-for="session in course.sessions" :key="session.id">
            <q-item-section>
              <q-item-label>
                {{ formatDate(session.date) }} —
                {{ formatTime(session.start) }} to
                {{ formatTime(session.end) }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </div>
    </div>
  </div>
</template>

<script setup>
import { format, formatISO9075, parseISO } from "date-fns";

const props = defineProps(["course"]);

const formatDate = (date) => {
  return format(parseISO(date), "PP");
};

const formatTime = (time) => {
  return format(
    parseISO(
      `${formatISO9075(new Date(), { representation: "date" })} ${time}`
    ),
    "pp"
  );
};
</script>
