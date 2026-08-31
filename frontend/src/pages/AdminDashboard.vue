<template>
  <div>
    <!-- <div class="text-h6 q-pl-sm">
      Dashboard
    </div>
    <div class="text-subtitle1 q-ml-xl">
      <div class="text-bold">
        If current show has active ticket sales:
      </div>
      <div class="q-ml-xl">
        Summary of ticket sales for current show
      </div>
      <div class="text-bold">
        Else:
      </div>
      <div class="q-ml-xl">
        Default text
      </div>
    </div> -->

    <div class="row">
      <div class="col-12 col-md-4" v-for="route of routes" :key="route.name">
        <q-card
          class="q-ma-sm cursor-pointer"
          @click="store.router.push(route.path)"
        >
          <q-toolbar>
            <q-toolbar-title>
              {{ route.name }}
            </q-toolbar-title>
          </q-toolbar>
          <q-card-section>
            <q-img :src="route.icon" height="30vh" fit="contain"> </q-img>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useStore } from "src/stores/store";
import { computed } from "vue";
import getPermissionLevel from "src/assets/get-permission-level";

const store = useStore();

// Same set the sidebar shows (none-permission sections dropped), further
// narrowed to routes opted into the dashboard tile grid via meta.dash.
const routes = computed(() =>
  store.router
    .getRoutes()
    .sort((a, b) => a.meta.order - b.meta.order)
    .filter(({ meta, aliasOf }) => meta.nav && meta.admin && !aliasOf)
    .filter(({ meta }) => meta.dash)
    .map(({ meta, path }) => {
      const key = path.replace("/admin/", "");
      const permissionLevel =
        key === "dashboard" || key === "users"
          ? "full"
          : getPermissionLevel(store.admin.user, key);

      const icon = path.split("/").pop();

      return {
        name: meta.label,
        path,
        permissionLevel,
        icon: `/images/admin-dashboard/${icon}.png`,
      };
    })
    .filter((route) => route.permissionLevel !== "none"),
);
</script>
