<template>
  <!-- Repeatable audition/callback sessions for AdminAuditionConfig.vue:
       date (weekday shown, never typed), start, end, optional note. -->
  <div>
    <div
      v-for="(row, i) in modelValue"
      :key="row.id ?? `new-${i}`"
      class="row q-col-gutter-sm items-center q-mb-sm"
    >
      <div class="col-12 col-sm-4 col-md-3">
        <DateField v-model="row.date" :label="`${noun} date`" :disable="disable" />
        <div class="text-caption text-grey-7 q-pl-sm" style="min-height: 1.4em">
          {{ weekday(row.date) }}
        </div>
      </div>
      <div class="col-auto">
        <TimeSelect v-model="row.start_time" label="Start" :disable="disable" />
      </div>
      <div class="col-auto">
        <TimeSelect v-model="row.end_time" label="End" :disable="disable" />
      </div>
      <div class="col">
        <q-input
          v-model="row.note"
          label="Note (optional)"
          :placeholder="notePlaceholder"
          outlined
          dense
          :disable="disable"
        />
      </div>
      <div class="col-auto">
        <q-btn
          flat
          round
          dense
          :icon="matDelete"
          color="negative"
          :disable="disable"
          @click="modelValue.splice(i, 1)"
        >
          <q-tooltip>Remove</q-tooltip>
        </q-btn>
      </div>
    </div>

    <q-btn
      :icon="matAdd"
      :label="`Add ${noun.toLowerCase()}`"
      color="primary"
      flat
      dense
      no-caps
      :disable="disable"
      @click="add"
    />
  </div>
</template>

<script setup>
import { matAdd, matDelete } from "@quasar/extras/material-icons";
import DateField from "src/components/DateField.vue";
import TimeSelect from "src/components/TimeSelect.vue";
import { weekday } from "src/assets/audition-form";

const props = defineProps({
  modelValue: { type: Array, required: true },
  noun: { type: String, default: "Session" },
  notePlaceholder: { type: String, default: "" },
  disable: Boolean,
});

// A new row copies the previous one's times (sessions usually match).
const add = () => {
  const last = props.modelValue[props.modelValue.length - 1];
  props.modelValue.push({
    date: null,
    start_time: last?.start_time ?? "",
    end_time: last?.end_time ?? "",
    note: "",
  });
};
</script>
