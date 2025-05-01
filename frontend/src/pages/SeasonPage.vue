<template>
  <div>
    <div class="row">
      <div
        class="col-12 col-sm-6 col-md-4 q-pa-xs"
        v-for="show of store.admin.shows"
        :key="`season-show-${show.id}`"
      >
        <season-show :show="show"></season-show>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import SeasonShow from "src/components/SeasonShow.vue";

const store = useStore();
</script>
