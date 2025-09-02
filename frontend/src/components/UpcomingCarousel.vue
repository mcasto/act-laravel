<template>
  <div>
    <q-toolbar class="q-py-none">
      <q-toolbar-title class="text-center">
        Upcoming Shows
      </q-toolbar-title>
    </q-toolbar>
    <q-separator></q-separator>

    <div class="text-center q-mt-md">
      {{ performanceDates }}
    </div>

    <q-carousel
      v-model="slide"
      swipeable
      animated
      navigation
      padding
      arrows
      height="52vh"
      class="rounded-borders"
      ref="carousel"
    >
      <template v-slot:control>
        <q-carousel-control
          position="bottom-right"
          :offset="[18, 18]"
          class="q-gutter-xs"
        >
          <q-btn
            push
            round
            dense
            color="orange"
            text-color="black"
            icon="arrow_left"
            @click="$refs.carousel.previous()"
            :disable="firstSlide"
          />
          <q-btn
            push
            round
            dense
            color="orange"
            text-color="black"
            icon="arrow_right"
            @click="$refs.carousel.next()"
            :disable="lastSlide"
          />
        </q-carousel-control>
      </template>

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
      <div v-if="ticketsStart && !curShow?.tentative">
        <span class="text-bold">
          Tickets On Sale:
        </span>
        {{ ticketsStart() }}
      </div>
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

const carousel = ref(null);
const slide = ref(null);

const curShow = computed(() => {
  return store.home.upcomingShows.find(({ id }) => id == slide.value);
});

const firstSlide = computed(() => {
  const first = [...store.home.upcomingShows].shift();
  return first.id == slide.value;
});

const lastSlide = computed(() => {
  const last = [...store.home.upcomingShows].pop();
  return last.id == slide.value;
});

const performanceDates = computed(() => {
  if (!curShow.value) {
    return false;
  }

  const performances = curShow.value.performances
    .map(({ date }) => date)
    .sort();
  if (performances.length == 0) {
    return false;
  }

  let first = performances.shift();
  let last = performances.length > 0 ? performances.pop() : first;

  if (curShow.value.tentative) {
    return format(parseISO(first), "MMM y");
  }

  first = format(parseISO(first), "PP");
  last = format(parseISO(last), "PP");

  return `${first} - ${last}`;
});

const ticketsStart = () => {
  if (!slide.value) return "";

  const show = curShow.value;

  return format(parseISO(show.ticket_sales_start), "MMM y");
};

onMounted(() => {
  const shows = store.home.upcomingShows;
  shows.value = shows;
  slide.value = shows.length > 0 ? shows[0].id : 0;
});
</script>
