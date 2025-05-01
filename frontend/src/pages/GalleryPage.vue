<template>
  <div>
    <q-table :rows="store.gallery" grid>
      <template #item="{row}">
        <div class="q-pa-sm">
          <gallery-card :show="row"></gallery-card>
        </div>
      </template>
    </q-table>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import GalleryCard from "src/components/GalleryCard.vue";

const store = useStore();
</script>
