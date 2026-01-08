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
        <q-item-section side v-if="item.permissionLevel == 'read'">
          <q-icon name="mdi-eye-lock"></q-icon>
        </q-item-section>
      </q-item>
      <!-- <q-item
        v-for="item of routes"
        :key="item.path"
        clickable
        :to="item.path"
        active-class="bg-blue-grey-2"
        :disable="!item.permissionLevel"
      >
        <q-item-section>
          <q-item-label>
            {{ item.name }}
          </q-item-label>
        </q-item-section>
        <q-item-section side v-if="item.permissionLevel == 'read'">
          <q-icon name="mdi-eye-lock"></q-icon>
        </q-item-section>
      </q-item> -->
    </q-list>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";

const store = useStore();

const routes = store.router
  .getRoutes()
  .sort((a, b) => {
    return a.meta.order - b.meta.order;
  })
  .filter(({ meta, aliasOf }) => meta.nav && meta.admin && !aliasOf)
  .map(({ meta, path }) => {
    const permissionLevelRequired = path.replace("/admin/", "");
    let permissionLevel = store.admin.user.permissions.find(
      ({ permission_level }) =>
        permission_level.value == permissionLevelRequired
    )?.access;

    if (permissionLevelRequired == "dashboard") {
      permissionLevel = "full";
    }

    return {
      name: meta.label,
      path,
      permissionLevel,
    };
  });
</script>
