<template>
  <div class="flex justify-center q-mt-md">
    <div class="text-center">
      <!-- <q-img
        src="/images/logo-home-2025.png"
        width="180px"
        class="q-mb-md"
      ></q-img> -->
      <div class="text-h5 text-bold">
        {{ store.ourAngels.config.title }}
      </div>

      <div
        v-for="level of store.ourAngels.levels"
        :key="`angel-level-${level.id}`"
        class="text-left q-mt-xl"
        style="width: 50vw; max-width: 600px;"
      >
        <div class="text-h6 text-grey-8">
          {{ level.label }} ({{ level.min_amount_formatted }})
        </div>
        <q-separator spaced></q-separator>
        <div class="row">
          <div
            class="col-12 col-md-6"
            v-for="angel of level.angels"
            :key="`angel-donor-${angel.id}`"
          >
            {{ angel.name }} {{ angel.founding_angel ? "*" : "" }}
          </div>
        </div>
      </div>

      <div class="text-left q-mt-lg">
        <div>
          {{ store.ourAngels.config.note }}
        </div>
        <div class="text-italic q-mt-sm">
          List Current As Of {{ store.ourAngels.mostRecent }}
        </div>
        <div class="q-mt-sm">
          Please <a href="mailto:actseats.com">contact</a> us if there are
          errors or omissions so we can make corrections.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";

const store = useStore();
</script>
