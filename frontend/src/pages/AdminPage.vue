<template>
  <div>
    <q-splitter :model-value="20">
      <template #before>
        <admin-nav></admin-nav>
      </template>
      <template #after>
        <div class="q-pa-md">
          <router-view
            :permission-level="permissionLevel"
            v-if="!!permissionLevel"
          />
          <div v-else>
            You do not have permission to access this route.
          </div>
        </div>
      </template>
    </q-splitter>
  </div>
</template>

<script setup>
import AdminNav from "src/components/AdminNav.vue";
import { useStore } from "src/stores/store";
import { ref, watch } from "vue";
import { useRoute } from "vue-router";
const route = useRoute();
const store = useStore();

const permissionLevel = ref(null);

watch(
  () => route.path, // Watch the path specifically
  (newPath) => {
    const permissionLevelRequired = newPath.match(/\/admin\/?([^\/]*).*/)[1];

    if (
      permissionLevelRequired == "dashboard" ||
      permissionLevelRequired == "/admin" ||
      permissionLevelRequired == ""
    ) {
      permissionLevel.value = "full";
      return;
    }

    const level = store.admin.user.permissions.find(
      ({ permission_level }) =>
        permission_level.value == permissionLevelRequired
    )?.access;

    permissionLevel.value = level;
  },
  { immediate: true } // Run immediately on component mount
);
</script>
