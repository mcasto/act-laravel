<template>
  <div>
    <q-separator></q-separator>
    <q-list dense separator class="admin-nav">
      <q-item
        v-for="item of routes"
        :key="item.path"
        clickable
        :to="item.path"
        active-class="bg-blue-grey-2"
      >
        <q-item-section>
          <q-item-label>
            {{ item.name }}
          </q-item-label>
        </q-item-section>
        <q-item-section side v-if="item.permissionLevel == 'read-only'">
          <q-icon :name="mdiEyeLock"></q-icon>
        </q-item-section>
      </q-item>
    </q-list>
  </div>
</template>

<script setup>
import { mdiEyeLock } from "@quasar/extras/mdi-v7";
import { useStore } from "src/stores/store";
import { computed } from "vue";
import getPermissionLevel from "src/assets/get-permission-level";

const store = useStore();

const routes = computed(() =>
  store.router
    .getRoutes()
    .sort((a, b) => a.meta.order - b.meta.order)
    .filter(({ meta, aliasOf }) => meta.nav && meta.admin && !aliasOf)
    .map(({ meta, path }) => {
      const key = path.replace("/admin/", "");
      const permissionLevel =
        key === "dashboard" || key === "users"
          ? "full"
          : getPermissionLevel(store.admin.user, key);

      return {
        name: meta.label,
        path,
        permissionLevel,
      };
    })
    .filter((item) => item.permissionLevel !== "none"),
);
</script>
