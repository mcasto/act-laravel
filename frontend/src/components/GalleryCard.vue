<template>
  <q-card bordered flat class="cursor-pointer" @click="openAlbum">
    <q-card-section class="text-center">
      <div class="text-subtitle1">
        {{ show.name }}
      </div>
      <div class="text-caption">
        {{ date }}
      </div>
      <q-img
        :src="`/api/storage/images/${show.poster}`"
        :width="Screen.lt.md ? '85vw' : '25vw'"
        height="40vh"
        fit="contain"
      ></q-img>
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
import { format, parseISO } from "date-fns";
import { computed, ref } from "vue";
import { Screen } from "quasar";
import GalleryCarousel from "./GalleryCarousel.vue";

const album = ref(false);
const slide = ref(null);

const props = defineProps(["show"]);

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
