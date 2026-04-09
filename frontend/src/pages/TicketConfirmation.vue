<template>
  <q-page class="flex flex-center">
    <div class="text-center q-pa-xl">
      <q-icon name="check_circle" color="positive" size="64px" />
      <h5 class="q-mt-md q-mb-sm">Purchase Confirmed</h5>
      <p class="text-body1">Thank you! Your booking has been received.</p>
      <p class="text-caption text-grey">
        Reference: {{ $route.params.uuid }}
      </p>
      <q-btn flat color="primary" label="Back to Home" to="/" class="q-mt-md" />
    </div>
  </q-page>
</template>

<script setup>
import { useRoute } from "vue-router";
const $route = useRoute();
</script>
