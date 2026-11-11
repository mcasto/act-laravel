<template>
  <q-layout view="hHh lpR fFf">
    <q-page-container>
      <q-header>
        <!-- Admin gets a plain toolbar instead of the 200px logo banner, so
             the work area starts near the top of the screen. -->
        <q-toolbar v-if="store.adminRoute" class="admin-toolbar text-white">
          <q-toolbar-title class="admin-toolbar-title">
            Azuay Community Theater
          </q-toolbar-title>
        </q-toolbar>
        <template v-else>
          <header-partial></header-partial>
          <nav-partial></nav-partial>
        </template>
      </q-header>
      <q-page>
        <router-view />
        <global-items v-if="!adminPath"></global-items>
        <show-details-dialog v-if="!adminPath"></show-details-dialog>
      </q-page>
      <q-footer class="bg-secondary text-white flex items-center q-pr-sm">
        <q-btn flat :icon="fasToolbox" to="/admin" v-if="!adminPath">
          <q-tooltip>Admin</q-tooltip>
        </q-btn>
        <q-btn flat :icon="matHome" v-else to="/"></q-btn>
        <q-btn
          flat
          :icon="matLogout"
          @click="store.signOut()"
          v-if="store.admin?.user"
        ></q-btn>
        <q-space></q-space>

        <a href="https://castoware.com" target="_blank" class="text-white">
          <q-icon :name="matInfo" size="sm" v-if="Screen.xs"> </q-icon>
          <span v-else>Website by CastoWare Development</span></a
        >
      </q-footer>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { matHome, matInfo, matLogout } from "@quasar/extras/material-icons";
import { fasToolbox } from "@quasar/extras/fontawesome-v6";
import HeaderPartial from "components/HeaderPartial.vue";
import NavPartial from "components/NavPartial.vue";
import GlobalItems from "src/components/GlobalItems.vue";
import ShowDetailsDialog from "src/components/ShowDetailsDialog.vue";
import { useStore } from "src/stores/store";
import { computed, onMounted } from "vue";
import { Screen } from "quasar";

const store = useStore();

const adminPath = computed(() => {
  return store.router.currentRoute.value.path.includes("admin");
});

onMounted(async () => {
  await store.currentAudition();
  await store.openCourses();
  await store.flexshowPurchaseConfig();
});
</script>
