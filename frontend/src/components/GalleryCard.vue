<template>
  <q-card bordered flat class="cursor-pointer" @click="openAlbum">
    <q-card-section class="text-center">
      <div class="text-subtitle1">
        {{ show.name }}
      </div>
      <div class="text-caption">
        {{ date }}
      </div>
      <poster-with-banner
        :src="POSTER_BASE_URL + show.poster"
        max-height="40vh"
        :sold-out="allSoldOut"
      />
    </q-card-section>
    <q-dialog v-model="album">
      <gallery-carousel
        v-model="slide"
        :images="show.gallery_images"
        :show="show"
      ></gallery-carousel>
    </q-dialog>
  </q-card>
</template>

<script setup>
import { POSTER_BASE_URL } from "src/assets/constants";
import { format, parseISO } from "date-fns";
import { computed, ref } from "vue";
import { Screen } from "quasar";
import GalleryCarousel from "./GalleryCarousel.vue";
import PosterWithBanner from "./PosterWithBanner.vue";

const album = ref(false);
const slide = ref(null);

const props = defineProps(["show"]);

const allSoldOut = computed(() => {
  const perfs = props.show?.performances ?? [];
  return perfs.length > 0 && perfs.every((p) => p.sold_out);
});

const date = computed(() => {
  const date = props.show.performances
    .map(({ date }) => date)
    .sort()
    .shift();

  return format(parseISO(date), "MMM y");
});

const openAlbum = () => {
  slide.value = props.show.gallery_images[0].id;
  album.value = true;
};
</script>
