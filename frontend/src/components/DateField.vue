<template>
  <!-- YYYY-MM-DD text field with a calendar popup; emptying it emits null. -->
  <q-input
    :model-value="modelValue"
    @update:model-value="(v) => emit('update:modelValue', v || null)"
    :label="label"
    :disable="disable"
    mask="####-##-##"
    placeholder="YYYY-MM-DD"
    outlined
    dense
  >
    <template #append>
      <q-icon :name="matEvent" class="cursor-pointer">
        <q-popup-proxy v-if="!disable" cover transition-show="scale" transition-hide="scale">
          <q-date
            :model-value="modelValue"
            @update:model-value="(v) => emit('update:modelValue', v || null)"
            mask="YYYY-MM-DD"
          >
            <div class="row items-center justify-end">
              <q-btn v-close-popup label="Close" color="primary" flat />
            </div>
          </q-date>
        </q-popup-proxy>
      </q-icon>
    </template>
  </q-input>
</template>

<script setup>
import { matEvent } from "@quasar/extras/material-icons";

defineProps({
  modelValue: String,
  label: String,
  disable: Boolean,
});
const emit = defineEmits(["update:modelValue"]);
</script>
