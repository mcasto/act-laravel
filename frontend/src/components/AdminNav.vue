<template>
  <q-toolbar class="bg-primary text-white shadow-4">
    <q-tabs>
      <q-route-tab
        v-for="tab of routes"
        :key="tab.path"
        :to="tab.path"
        :label="tab.name"
      ></q-route-tab>
    </q-tabs>
  </q-toolbar>
</template>

<script setup>
import { useStore } from "src/stores/store";

const store = useStore();

const routes = store.router
  .getRoutes()
  .filter(({ meta, aliasOf }) => meta.nav && meta.admin && !aliasOf)
  .map(({ name, path }) => {
    return {
      name,
      path,
    };
  });
</script>
