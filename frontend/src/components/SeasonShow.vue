<template>
  <q-card bordered flat class="bg-grey-2">
    <q-img
      :src="`/api/storage/posters/${show.poster}`"
      fit="contain"
      height="40vh"
      :alt="`Poster for ${show.name}`"
    ></q-img>

    <div class="text-center text-bold text-black">
      {{ show.name }}
    </div>
    <div class="text-center text-caption text-black">
      {{ displayDate }}
    </div>
    <q-separator spaced></q-separator>
    <div class="flex justify-center q-mb-md">
      <q-btn
        label="View Details"
        color="primary"
        :to="`/show-details/${show.slug}`"
        flat
      ></q-btn>

      <q-btn
        label="Gallery"
        color="primary"
        :to="`/show-details/${show.slug}`"
        class="q-ml-sm"
        flat
        v-if="hasGallery"
      ></q-btn>
    </div>
  </q-card>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { cloneDeep } from "lodash-es";
import { computed } from "vue";

const props = defineProps(["show"]);

const hasGallery = computed(() => {
  return props.show.gallery_images?.length > 0;
});

const displayDate = computed(() => {
  const performances = cloneDeep(props.show.performances);
  const first = performances.shift();
  const last = performances.pop();

  return `${format(parseISO(first.date), "MMM d y")} - ${format(
    parseISO(last.date),
    "MMM d y"
  )}`;
});
</script>
