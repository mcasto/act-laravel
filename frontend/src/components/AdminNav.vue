<template>
  <div>
    <q-separator></q-separator>
    <q-list dense separator class="admin-nav">
      <q-item
        v-for="item of routes"
        :key="item.path"
        clickable
        :to="item.path"
        active-class="bg-blue-grey-2 "
      >
        <q-item-section>
          <q-item-label>
            {{ item.name }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>

    <!-- <q-toolbar class="bg-primary text-white shadow-4">
    <q-tabs>
      <q-route-tab
        v-for="tab of routes"
        :key="tab.path"
        :to="tab.path"
        :label="tab.name"
      ></q-route-tab>
    </q-tabs>
  </q-toolbar> -->
  </div>
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
