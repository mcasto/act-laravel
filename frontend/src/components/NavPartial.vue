<template>
  <q-toolbar class="bg-primary text-white shadow-4">
    <q-tabs v-if="Screen.gt.xs">
      <q-route-tab
        v-for="route of routes"
        :key="route.path"
        :label="route.name"
        :to="route.path"
        exact
      >
      </q-route-tab>
    </q-tabs>
    <q-btn
      icon="menu"
      v-else
      size="md"
      label="More Info"
      flat
      @click="drawer = !drawer"
    ></q-btn>
  </q-toolbar>

  <q-drawer v-if="Screen.xs" v-model="drawer" class="bg-primary text-white">
    Drawer
  </q-drawer>
</template>

<script setup>
import { Screen } from "quasar";
import { useStore } from "src/stores/store";
import { ref } from "vue";

const store = useStore();
const routes = store.router
  .getRoutes()
  .filter(({ meta }) => meta.nav && !meta.admin)
  .map(({ name, path }) => {
    return {
      name,
      path,
    };
  });

const drawer = ref(false);
</script>
