<template>
  <div class="text-center q-pa-xl">
    <q-icon :name="matEventBusy" size="3em" color="grey-6" />
    <div class="text-h6 q-mt-md">
      No Current or Upcoming Show
    </div>
    <div class="text-caption text-grey-7">
      Volunteer needs are managed per-show and only apply to a show that's
      currently running or coming up next. Add a show with future
      performance dates to manage its volunteer needs here.
    </div>
  </div>
</template>

<script setup>
import { matEventBusy } from "@quasar/extras/material-icons";
</script>
