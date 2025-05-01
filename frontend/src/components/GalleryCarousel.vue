<template>
  <q-card style="width: 80vw;">
    <q-card-section>
      <div class="text-h6 text-center">
        {{ show.name }}
      </div>
      <div class="text-caption text-center">
        {{ date }}
      </div>
      <div class="absolute-top flex justify-end">
        <q-btn icon="close" flat round v-close-popup></q-btn>
      </div>
      <q-carousel
        v-model="slide"
        transition-prev="slide-right"
        transition-next="slide-left"
        animated
        control-color="primary"
        class="rounded-borders"
        style="height: 70vh;"
      >
        <q-carousel-slide
          v-for="image of show.gallery_images"
          :key="image.id"
          :name="image.id"
        >
          <q-img
            :src="`/api/storage/images/${image.image}`"
            fit="contain"
            height="65vh"
          ></q-img>
        </q-carousel-slide>
      </q-carousel>

      <div class="row justify-center">
        <q-btn-toggle glossy v-model="slide" :options="options" />
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { computed } from "vue";

const props = defineProps(["show"]);
const slide = defineModel();

const options = computed(() => {
  return props.show.gallery_images.map((image, idx) => {
    return {
      label: idx + 1,
      value: image.id,
    };
  });
});

const date = computed(() => {
  const performance = props.show.performances[0];
  return format(parseISO(performance.date), "MMM y");
});
</script>
