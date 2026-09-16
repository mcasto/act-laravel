<template>
  <q-field
    v-if="label || (rules && rules.length)"
    :model-value="modelValue"
    :label="label"
    :rules="rules"
    outlined
    filled
    dense
    stack-label
  >
    <template v-slot:control>
      <div class="row q-gutter-xs items-center full-width no-wrap">
        <q-select
          :model-value="hour"
          @update:model-value="onHourChange"
          :options="hourOptions"
          dense
          outlined
          style="width: 64px;"
          :disable="disable"
        />
        <div class="text-grey-7">:</div>
        <q-select
          :model-value="minute"
          @update:model-value="onMinuteChange"
          :options="minuteOptions"
          dense
          outlined
          style="width: 64px;"
          :disable="disable"
        />
        <q-btn-toggle
          :model-value="meridiem"
          @update:model-value="onMeridiemChange"
          :options="meridiemOptions"
          dense
          no-caps
          outline
          size="sm"
          toggle-color="primary"
          :disable="disable"
        />
      </div>
    </template>
  </q-field>

  <div v-else class="row q-gutter-xs items-center no-wrap">
    <q-select
      :model-value="hour"
      @update:model-value="onHourChange"
      :options="hourOptions"
      dense
      outlined
      style="width: 64px;"
      :disable="disable"
    />
    <div class="text-grey-7">:</div>
    <q-select
      :model-value="minute"
      @update:model-value="onMinuteChange"
      :options="minuteOptions"
      dense
      outlined
      style="width: 64px;"
      :disable="disable"
    />
    <q-btn-toggle
      :model-value="meridiem"
      @update:model-value="onMeridiemChange"
      :options="meridiemOptions"
      dense
      no-caps
      outline
      size="sm"
      toggle-color="primary"
      :disable="disable"
    />
  </div>
</template>

<script setup>
import { computed } from "vue";

// v-model is always a 24-hour "HH:mm:00" string (matching what
// performances/course sessions already store) — this component only
// changes how that value is entered, never its underlying format.
const props = defineProps({
  modelValue: { type: String, default: "" },
  label: { type: String, default: "" },
  rules: { type: Array, default: () => [] },
  disable: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);

const hourOptions = Array.from({ length: 12 }, (_, i) => String(i + 1).padStart(2, "0"));
const minuteOptions = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, "0"));
const meridiemOptions = [
  { label: "AM", value: "AM" },
  { label: "PM", value: "PM" },
];

const parsed = computed(() => {
  const [hStr, mStr] = (props.modelValue || "").split(":");
  const h24 = hStr !== undefined ? parseInt(hStr, 10) : NaN;

  if (Number.isNaN(h24)) {
    return { hour: null, minute: null, meridiem: null };
  }

  const meridiem = h24 >= 12 ? "PM" : "AM";
  let hour12 = h24 % 12;
  if (hour12 === 0) hour12 = 12;

  return {
    hour: String(hour12).padStart(2, "0"),
    minute: mStr ?? "00",
    meridiem,
  };
});

const hour = computed(() => parsed.value.hour);
const minute = computed(() => parsed.value.minute);
const meridiem = computed(() => parsed.value.meridiem);

const emitTime = (hour12Str, minuteStr, meridiemVal) => {
  let h = parseInt(hour12Str, 10) % 12;
  if (meridiemVal === "PM") h += 12;
  emit("update:modelValue", `${String(h).padStart(2, "0")}:${minuteStr}:00`);
};

// Any one dropdown can be the first ever touched on a blank value, so the
// other two fall back to sensible defaults (noon) rather than no-op'ing.
const onHourChange = (val) => emitTime(val, minute.value ?? "00", meridiem.value ?? "PM");
const onMinuteChange = (val) => emitTime(hour.value ?? "12", val, meridiem.value ?? "PM");
const onMeridiemChange = (val) => emitTime(hour.value ?? "12", minute.value ?? "00", val);
</script>
