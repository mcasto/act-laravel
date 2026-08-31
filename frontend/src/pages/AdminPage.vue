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
            v-if="permissionLevel && permissionLevel !== 'none'"
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
import getPermissionLevel from "src/assets/get-permission-level";

const route = useRoute();
const store = useStore();

const permissionLevel = ref(null);

watch(
  () => route.path, // Watch the path specifically
  (newPath) => {
    const match = newPath.match(/\/admin\/?([^\/]*).*/);
    if (!match) return;
    const key = match[1];

    // Dashboard and Users are always accessible — Dashboard just mirrors
    // whatever the sidebar shows, and Users has its own owner/self rule
    // rather than a section-level permission (see UserController::isOwner).
    if (key === "dashboard" || key === "users" || key === "" || newPath === "/admin") {
      permissionLevel.value = "full";
      return;
    }

    // Sub-pages (edit-show, gallery, comp-config, etc.) live at path
    // segments that don't match their logical section — route.meta.section
    // is the source of truth when present; falling back to the path
    // segment only works for top-level nav routes, where it happens to
    // equal the section key.
    permissionLevel.value = getPermissionLevel(
      store.admin.user,
      route.meta.section ?? key,
    );
  },
  { immediate: true } // Run immediately on component mount
);
</script>
