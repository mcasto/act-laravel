<template>
  <q-card bordered flat class="bg-grey-2">
    <poster-with-banner
      :src="POSTER_BASE_URL + show.poster"
      max-height="40vh"
      :sold-out="allSoldOut"
    />

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
import { POSTER_BASE_URL } from "src/assets/constants";
import { format, parseISO } from "date-fns";
import { cloneDeep } from "lodash-es";
import { computed } from "vue";
import PosterWithBanner from "src/components/PosterWithBanner.vue";

const props = defineProps(["show"]);

const allSoldOut = computed(() => {
  const perfs = props.show?.performances ?? [];
  return perfs.length > 0 && perfs.every((p) => p.sold_out);
});

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
