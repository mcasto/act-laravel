<template>
  <div class="q-px-xl q-mt-md">
    <div class="text-h6" v-html="config.title"></div>

    <p v-html="config.header"></p>

    <div class="q-mb-md" v-html="config.thanks"></div>

    <div
      class="angel-level"
      id="act-angel"
      v-for="level of config.levels"
      :key="level.id"
    >
      <div class="text-bold q-mt-lg">
        {{ level.label }} ({{ level.min_amount_formatted }})
      </div>

      <q-list dense bordered separator class="q-mx-lg">
        <q-item
          v-for="(item, index) of level.benefits"
          :key="`${level.id}-benefit-${index}`"
        >
          <q-item-section>
            <q-item-label v-html="item"> </q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";

const store = useStore();

const config = store.angelConfig;
</script>
