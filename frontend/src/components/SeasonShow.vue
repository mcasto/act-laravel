<template>
  <q-card bordered flat class="bg-grey-2">
    <router-link :to="`/show-details/${show.slug}`">
      <q-img
        :src="`/api/storage/images/${show.poster}`"
        fit="contain"
        height="40vh"
        :alt="`Poster for ${show.name}`"
      ></q-img>
    </router-link>
    <div class="text-center text-bold text-black">
      {{ show.name }}
    </div>
    <div class="text-center text-caption text-black">
      {{ displayDate }}
    </div>
  </q-card>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { computed } from "vue";

const props = defineProps(["show"]);

const displayDate = computed(() => {
  const performances = props.show.performances;
  return format(parseISO(performances[0].date), "MMM y");
});
</script>
