<template>
  <q-card>
    <q-img :src="`/api/storage/posters/${course.poster}`"></q-img>
    <q-card-section>
      <div class="text-h6">
        {{ course.name }}
      </div>
      <div class="text-subtitle1">Taught by: {{ course.instructor_name }}</div>
      <div class="text-subtitle2">Enrollment Period: {{ enrollment }}</div>
      <div class="text-subtitle2 q-mt-sm">{{ sessions.count }} Sessions</div>
      <ul class="q-ma-none">
        <li>
          <span class="text-bold">First Session: </span>
          {{ sessions.start }}
        </li>

        <li>
          <span class="text-bold">Last Session: </span>
          {{ sessions.end }}
        </li>
      </ul>
    </q-card-section>
    <q-card-actions class="justify-end">
      <q-btn
        label="Info"
        color="primary"
        :to="`/class-details/${course.slug}`"
      ></q-btn>
    </q-card-actions>
  </q-card>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { cloneDeep } from "lodash-es";
import { computed } from "vue";

const props = defineProps(["course"]);

const enrollment = computed(() => {
  const start = format(parseISO(props.course.enrollment_start), "PP");
  const end = format(parseISO(props.course.enrollment_end), "PP");

  return `${start} - ${end}`;
});

const sessions = computed(() => {
  const sessionList = cloneDeep(props.course.sessions);
  const count = props.course.sessions.length;
  const first = sessionList.shift();
  const last = sessionList.length > 0 ? sessionList.pop() : first;

  return {
    count,
    start: format(parseISO(first.date), "PP"),
    end: format(parseISO(last.date), "PP"),
  };
});
</script>
