<template>
  <div>
    <q-toolbar class="q-py-none">
      <q-toolbar-title class="text-center">
        Upcoming Shows
      </q-toolbar-title>
    </q-toolbar>
    <q-separator></q-separator>
    <q-carousel
      v-model="slide"
      swipeable
      animated
      control-type="outline"
      control-color="purple"
      navigation
      padding
      arrows
      height="52vh"
      class="rounded-borders"
    >
      <q-carousel-slide
        :name="show.id"
        v-for="show of shows"
        :key="`upcoming-show-${show.id}`"
      >
        <router-link :to="`/show-details/${show.slug}`">
          <q-img
            :src="`/api/storage/images/${show.poster}`"
            fit="contain"
            height="40vh"
          ></q-img>
        </router-link>
      </q-carousel-slide>
    </q-carousel>

    <div class="text-center">
      <span class="text-bold">Tickets on Sale:</span> {{ ticketsStart() }}
    </div>
  </div>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { useStore } from "src/stores/store";
import { computed, onMounted, ref } from "vue";

const store = useStore();

const shows = computed(() => {
  return store.home.upcomingShows;
});

const slide = ref(null);

const ticketsStart = () => {
  if (!slide.value) return "";

  const show = store.home.upcomingShows.find(({ id }) => id == slide.value);
  return format(parseISO(show.ticket_sales_start), "MMM y");
};

onMounted(() => {
  const shows = store.home.upcomingShows;
  shows.value = shows;
  slide.value = shows.length > 0 ? shows[0].id : 0;
});
</script>
