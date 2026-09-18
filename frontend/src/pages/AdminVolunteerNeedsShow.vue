<template>
  <div class="row q-col-gutter-lg">
    <div class="col-12 col-md-4">
      <show-poster :show="store.admin.show" height="40vh"></show-poster>
    </div>
    <div class="col-12 col-md-8">
      <div class="text-h5 q-mb-md">
        Volunteer Needs for {{ store.admin.show.name }}
      </div>
      <q-banner class="bg-grey-2">
        <template #avatar>
          <q-icon :name="matConstruction" color="grey-7" />
        </template>
        This section is coming soon. It'll let volunteer coordinators and
        hospitality track what help is needed for this show.
      </q-banner>
    </div>
  </div>
</template>

<script setup>
import { matConstruction } from "@quasar/extras/material-icons";
import ShowPoster from "src/components/ShowPoster.vue";
import { useStore } from "src/stores/store";

const store = useStore();
</script>
