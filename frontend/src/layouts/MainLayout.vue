<template>
  <q-layout view="hHh lpR fFf">
    <q-page-container>
      <q-header>
        <header-partial></header-partial>
        <nav-partial v-if="!store.adminRoute"></nav-partial>
      </q-header>
      <q-page>
        <router-view />
        <global-items v-if="!adminPath"></global-items>
      </q-page>
      <q-footer class="flex items-center q-pr-sm">
        <q-btn flat icon="fa-solid fa-toolbox" to="/admin" v-if="!adminPath">
          <q-tooltip>Admin</q-tooltip>
        </q-btn>
        <q-btn flat icon="home" v-else to="/"></q-btn>
        <q-btn
          flat
          icon="logout"
          @click="store.signOut()"
          v-if="store.admin?.user"
        ></q-btn>
        <q-space></q-space>

        <a href="https://castoware.com" target="_blank" class="text-white">
          <q-icon name="info" size="sm" v-if="Screen.xs"> </q-icon>
          <span v-else>Website by CastoWare Development</span></a
        >
      </q-footer>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import HeaderPartial from "components/HeaderPartial.vue";
import NavPartial from "components/NavPartial.vue";
import GlobalItems from "src/components/GlobalItems.vue";
import { useStore } from "src/stores/store";
import { computed, onMounted } from "vue";
import { Screen } from "quasar";

const store = useStore();

const adminPath = computed(() => {
  return store.router.currentRoute.value.path.includes("admin");
});

onMounted(async () => {
  await store.currentAudition();
  await store.getOpenClasses();
});
</script>
