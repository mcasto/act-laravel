<template>
  <div class="q-px-xl">
    <q-toolbar>
      <q-toolbar-title shrink class="q-mr-xl">Gallery</q-toolbar-title>
      <q-pagination
        v-model="pagination.page"
        :max="totalPages"
        :max-pages="6"
        direction-links
      />
    </q-toolbar>

    <q-table
      :rows="paginatedGallery"
      grid
      hide-pagination
      :pagination="pagination"
    >
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
import { computed, ref, watch } from "vue";

const store = useStore();

// Pagination configuration
const pagination = ref({
  page: 1,
  rowsPerPage: 6,
});

// Calculate total pages
const totalPages = computed(() =>
  Math.ceil(store.gallery.length / pagination.value.rowsPerPage)
);

// Get paginated data
const paginatedGallery = computed(() => {
  const startIndex = (pagination.value.page - 1) * pagination.value.rowsPerPage;
  const endIndex = startIndex + pagination.value.rowsPerPage;
  return store.gallery.slice(startIndex, endIndex);
});

// Reset to page 1 when gallery data changes
watch(
  () => store.gallery,
  () => {
    pagination.value.page = 1;
  }
);
</script>
