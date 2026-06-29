<template>
  <q-toolbar class="bg-primary text-white shadow-4">
    <q-tabs v-if="Screen.gt.md">
      <q-route-tab
        v-for="route of routes"
        :key="route.path"
        :label="route.fbIcon ? undefined : route.name"
        :to="route.path"
        exact
      >
        <template v-if="route.fbIcon">
          <span class="flex items-center gap-1">
            {{ route.name }}
            <svg
              class="q-ml-sm"
              xmlns="http://www.w3.org/2000/svg"
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="currentColor"
              style="vertical-align: middle; flex-shrink: 0;"
            >
              <path
                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
              />
            </svg>
          </span>
        </template>
      </q-route-tab>
    </q-tabs>
    <q-btn
      :icon="matMenu"
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
import { matMenu } from "@quasar/extras/material-icons";
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
    .map(({ meta, path }) => ({ name: meta.label, path, fbIcon: meta.fbIcon })),
);

const drawer = ref(false);

watch(route, () => {
  drawer.value = false;
});
</script>
