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
        style="height: 70vh;"
        swipeable
        arrows
        class="custom-carousel"
      >
        <q-carousel-slide
          v-for="image of show.gallery_images"
          :key="image.id"
          :name="image.id"
        >
          <q-img
            :src="`/api/storage/${image.image}`"
            fit="contain"
            height="65vh"
          ></q-img>
        </q-carousel-slide>
      </q-carousel>
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

<style scoped>
.custom-carousel :deep(.q-carousel__arrow) {
  background-color: var(--q-primary);
  border-radius: 50%;
  width: 48px;
  height: 48px;
  min-width: 48px;
  min-height: 48px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Make icon white to contrast with primary background */
.custom-carousel :deep(.q-carousel__arrow .q-icon) {
  color: white;
}
</style>
