<template>
  <q-card flat bordered class="overflow-hidden">
    <div class="section-band bg-secondary text-white q-py-sm q-px-md">
      <q-icon :name="matEvent" />
      <div class="text-subtitle1 text-weight-bold">Class Sessions</div>
    </div>

    <q-list separator>
      <q-item v-for="(session, index) in course.sessions" :key="session.id">
        <q-item-section avatar>
          <q-avatar color="primary" text-color="white" size="36px">
            {{ index + 1 }}
          </q-avatar>
        </q-item-section>
        <q-item-section>
          <q-item-label class="text-weight-medium">
            {{ formatDate(session.date) }}
          </q-item-label>
          <q-item-label caption>
            {{ formatTime(session.start) }} – {{ formatTime(session.end) }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </q-card>
</template>

<script setup>
import { matEvent } from "@quasar/extras/material-icons";
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
