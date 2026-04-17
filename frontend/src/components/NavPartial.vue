<template>
  <q-toolbar class="bg-primary text-white shadow-4">
    <q-tabs v-if="Screen.gt.md">
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
      label="Navigation Menu"
      flat
      @click="drawer = !drawer"
    ></q-btn>
  </q-toolbar>

  <q-drawer
    v-if="Screen.lt.lg"
    v-model="drawer"
    class="bg-primary text-white"
    overlay
  >
    <nav-drawer :routes="routes"></nav-drawer>
  </q-drawer>
</template>

<script setup>
import { Screen } from "quasar";
import { useStore } from "src/stores/store";
import { computed, ref, watch } from "vue";
import NavDrawer from "./NavDrawer.vue";
import { useRoute } from "vue-router";

const route = useRoute();

const store = useStore();
const routes = computed(() =>
  store.router
    .getRoutes()
    .filter(({ meta }) => {
      if (meta.admin) return false;
      if (meta.nav) return true;
      if (meta.global) return meta.display;
      return false;
    })
    .map(({ meta, path }) => ({ name: meta.label, path }))
);

const drawer = ref(false);

watch(route, () => {
  drawer.value = false;
});
</script>
