<template>
  <div>
    <div v-if="!hasShows" class="text-center q-pa-xl">
      <div class="text-h6">The current season is under construction.</div>
      <div class="text-body2 q-mt-sm">Please bear with us.</div>
    </div>

    <div v-else class="row">
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
import { computed } from "vue";

const store = useStore();

const hasShows = computed(() => (store.admin.shows?.length ?? 0) > 0);
</script>
